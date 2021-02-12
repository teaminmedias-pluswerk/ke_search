<?php
namespace TeaminmediasPluswerk\KeSearch\Indexer;

/***************************************************************
 *  Copyright notice
 *  (c) 2010 Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TeaminmediasPluswerk\KeSearch\Indexer\Types\File;
use TeaminmediasPluswerk\KeSearch\Lib\Db;
use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for indexer classes.
 *
 * @author    Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 * @author    Stefan Froemken
 * @author    Christian Bülter <christian.buelter@inmedias.de>
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class IndexerBase
{
    public $startMicrotime = 0;
    public $indexerConfig = array(); // current indexer configuration

    // string which separates metadata from file content in the index record
    const METADATASEPARATOR = "\n";

    /** @var int $fileCounter */
    protected $fileCounter = 0;

    /**
     * @var IndexerRunner
     */
    public $pObj;

    /**
     * needed to get all recursive pids
     */
    public $queryGen;

    /**
     * @var array
     */
    protected $errors = array();

    /**
     * @var array
     */
    public $pageRecords;

    /**
     * Constructor of this object
     * @param $pObj
     */
    public function __construct($pObj)
    {
        $this->startMicrotime = microtime(true);
        $this->pObj = $pObj;
        $this->indexerConfig = $this->pObj->indexerConfig;
        $this->queryGen = GeneralUtility::makeInstance(QueryGenerator::class);
    }


    /**
     * get all recursive contained pids of given Page-UID
     * regardless if we need them or if they are sysfolders, links or what ever
     * @param string $startingPointsRecursive comma-separated list of pids of recursive start-points
     * @param string $singlePages comma-separated list of pids of single pages
     * @return array List of page UIDs
     */
    public function getPagelist($startingPointsRecursive = '', $singlePages = '')
    {
        // make array from list
        $pidsRecursive = GeneralUtility::trimExplode(',', $startingPointsRecursive, true);
        $pidsNonRecursive = GeneralUtility::trimExplode(',', $singlePages, true);

        // add recursive pids
        $pageList = '';
        foreach ($pidsRecursive as $pid) {
            $pageList .= $this->queryGen->getTreeList($pid, 99, 0, '1=1') . ',';
        }

        // add non-recursive pids
        foreach ($pidsNonRecursive as $pid) {
            $pageList .= $pid . ',';
        }

        // convert to array
        $pageUidArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $pageList, true);

        return $pageUidArray;
    }


    /**
     * get array with all pages
     * but remove all pages we don't want to have
     * @param array $uids Array with all page uids
     * @param string $whereClause Additional where clause for the query
     * @param string $table The table to select the fields from
     * @param string $fields The requested fields
     * @return array Array containing page records with all available fields
     */
    public function getPageRecords(array $uids, $whereClause = '', $table = 'pages', $fields = 'pages.*')
    {

        $queryBuilder = Db::getQueryBuilder($table);
        $queryBuilder->getRestrictions()->removeAll();
        $where = [];
        $where[] = $queryBuilder->expr()->in('pages.uid', implode(',', $uids));
        // index only pages which are searchable
        // index only page which are not hidden
        $where[] = $queryBuilder->expr()->neq(
            'pages.no_search',
            $queryBuilder->createNamedParameter(1,\PDO::PARAM_INT)
        );
        $where[] = $queryBuilder->expr()->eq(
            'pages.hidden',
            $queryBuilder->createNamedParameter(0,\PDO::PARAM_INT)
        );
        $where[] = $queryBuilder->expr()->eq(
            'pages.deleted',
            $queryBuilder->createNamedParameter(0,\PDO::PARAM_INT)
        );

        // add additional where clause
        if ($whereClause) {
            $where[] = $whereClause;
        }

        $tables = GeneralUtility::trimExplode(',',$table);
        $query = $queryBuilder
            ->select($fields);
        foreach ($tables as $table) {
            $query->from($table);
        }
        $query->where(...$where);

        $pageRows = $query->execute()->fetchAll();

        $pages = [];
        foreach ($pageRows as $row) {
            $pages[$row['uid']] = $row;
        }

        return $pages;
    }


    /**
     * get a list of pids
     * @param string $startingPointsRecursive
     * @param string $singlePages
     * @param string $table
     * @return array Array containing uids of pageRecords
     * Todo enable fields
     */
    public function getPidList($startingPointsRecursive = '', $singlePages = '', $table = 'pages')
    {
        // get all pages. Regardless if they are shortcut, sysfolder or external link
        $indexPids = $this->getPagelist($startingPointsRecursive, $singlePages);

        // add complete page record to list of pids in $indexPids
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_index');
        $where = $queryBuilder->quoteIdentifier($table)
            . '.' . $queryBuilder->quoteIdentifier('pid')
            . ' = ' . $queryBuilder->quoteIdentifier('pages')
            . '.' . $queryBuilder->quoteIdentifier('uid');
        $this->pageRecords = $this->getPageRecords($indexPids, $where, 'pages,' . $table, 'pages.*');
        if (count($this->pageRecords)) {
            // create a new list of allowed pids
            return array_keys($this->pageRecords);
        } else {
            return array('0' => 0);
        }
    }


    /**
     * Add Tags to records array
     *
     * @param array $uids Simple array with uids of pages
     * @param string $pageWhere additional where-clause
     * @return void
     */
    public function addTagsToRecords($uids, $pageWhere = '1=1')
    {

        $tagChar = $this->pObj->extConf['prePostTagChar'];

        // add tags which are defined by page properties
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_filteroptions');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $fields = 'pages.*, GROUP_CONCAT(CONCAT("'
            . $tagChar
            . '", tx_kesearch_filteroptions.tag, "'
            . $tagChar
            . '")) as tags';

        $where = 'pages.uid IN (' . implode(',', $uids) . ')';
        $where .= ' AND pages.tx_kesearch_tags <> "" ';
        $where .= ' AND FIND_IN_SET(tx_kesearch_filteroptions.uid, pages.tx_kesearch_tags)';

        $tagQuery = $queryBuilder
            ->add('select', $fields)
            ->from('pages')
            ->from('tx_kesearch_filteroptions')
            ->add('where', $where)
            ->groupBy('pages.uid')
            ->execute();

        while ($row = $tagQuery->fetch()) {
            $this->pageRecords[$row['uid']]['tags'] = $row['tags'];
        }

        // add system categories as tags
        foreach ($uids as $page_uid) {
            SearchHelper::makeSystemCategoryTags($this->pageRecords[$page_uid]['tags'], $page_uid, 'pages');
        }

        // add tags which are defined by filteroption records
        $table = 'tx_kesearch_filteroptions';
        $queryBuilder = Db::getQueryBuilder($table);
        $filterOptionsRows = $queryBuilder
            ->select('automated_tagging', 'automated_tagging_exclude', 'tag')
            ->from($table)
            ->where(
                $queryBuilder->expr()->neq(
                    'automated_tagging',
                    $queryBuilder->quote("", \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();

        $where = $pageWhere . ' AND no_search <> 1 ';

        foreach ($filterOptionsRows as $row) {
            if ($row['automated_tagging_exclude'] > '') {
                $whereRow = $where . 'AND FIND_IN_SET(pages.pid, "' . $row['automated_tagging_exclude'] . '") = 0';
            } else {
                $whereRow = $where;
            }

            $pageList = array();
            $automated_tagging_arr = explode(',', $row['automated_tagging']);
            foreach ($automated_tagging_arr as $key => $value) {
                $tmpPageList = GeneralUtility::trimExplode(
                    ',',
                    $this->queryGen->getTreeList($value, 99, 0, $whereRow)
                );
                $pageList = array_merge($tmpPageList, $pageList);
            }

            foreach ($pageList as $uid) {
                $this->pageRecords[$uid]['tags'] = SearchHelper::addTag($row['tag'], $this->pageRecords[$uid]['tags']);
            }
        }
    }

    /**
     * adds an error to the error array
     * @param string or array of strings $errorMessage
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 26.11.13
     */
    public function addError($errorMessage)
    {
        if (is_array($errorMessage)) {
            if (count($errorMessage)) {
                foreach ($errorMessage as $message) {
                    $this->errors[] = $message;
                }
            }
        } else {
            $this->errors[] = $errorMessage;
        }
    }

    /**
     * @return array
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 26.11.13
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return intval(ceil((microtime(true) - $this->startMicrotime) * 1000));
    }

    /**
     * compile file metadata from file properties and add it to already given file content
     *
     * @param array file properties (including metadata)
     * @param string content already prepared for this file index record
     * @author Christian Bülter <christian.buelter@pluswerk.ag>
     * @since 31.11.19
     * @return string metadata compiled into a string
     */
    public function addFileMetata($fileProperties, $fileContent)
    {
        // remove previously indexed metadata
        if (strpos($fileContent, self::METADATASEPARATOR)) {
            $fileContent = substr($fileContent, strrpos($fileContent, self::METADATASEPARATOR));
        }

        $metadataContent = '';

        if ($fileProperties['title']) {
            $metadataContent = $fileProperties['title'] . " ";
        }

        if ($fileProperties['description']) {
            $metadataContent .= $fileProperties['description'] . " ";
        }

        if ($fileProperties['alternative']) {
            $metadataContent .= $fileProperties['alternative'] . " ";
        }

        if ($metadataContent) {
            $fileContent = $metadataContent . self::METADATASEPARATOR . $fileContent;
        }

        return $fileContent;
    }

    /**
     * get the sys_category UIDs which are selected in the indexer configuration
     *
     * @param int $indexerConfigUid
     * @return array $selectedCategories Array of UIDs of selected categories
     */
    public function getSelectedCategoriesUidList(int $indexerConfigUid): array
    {
        $queryBuilder = Db::getQueryBuilder('sys_category_record_mm');
        $selectedCategories = $queryBuilder
            ->select('uid_local')
            ->from('sys_category_record_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($indexerConfigUid, \PDO::PARAM_INT)),
                $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tx_kesearch_indexerconfig'))
            )
            ->execute()
            ->fetchAll();
        
        if ($selectedCategories) {
            // flatten the multidimensional array to only contain the category UIDs
            $flattenSelectedCategories = [];
            array_walk_recursive($selectedCategories, function ($a) use (&$flattenSelectedCategories) {
                $flattenSelectedCategories[] = $a;
            });
            $selectedCategories = $flattenSelectedCategories;
        } else {
            // make sure to return an empty array in case the query returns NULL
            $selectedCategories = [];
        }
        
        return $selectedCategories;
    }

    /**
     * Returns a list of files to be indexed for the given table, uid and language.
     * Takes the "fileext" setting from the indexer configuration into account and returns only files with allowed
     * file extensions.
     *
     * @param $table
     * @param $fieldname
     * @param $uid
     * @param $language
     * @return array An array of file references.
     */
    protected function getFilesToIndex($table, $fieldname,  $uid, $language): array
    {
        /** @var FileRepository $fileRepository  */
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $filesToIndex = [];

        $queryBuilder = Db::getQueryBuilder('sys_file');
        $relatedFilesQuery = $queryBuilder
            ->select('ref.uid')
            ->from('sys_file', 'file')
            ->from('sys_file_reference', 'ref')
            ->where(
                $queryBuilder->expr()->eq(
                    'ref.tablenames',
                    $queryBuilder->createNamedParameter($table)
                ),
                $queryBuilder->expr()->eq(
                    'ref.fieldname',
                    $queryBuilder->createNamedParameter($fieldname)
                ),
                $queryBuilder->expr()->eq(
                    'ref.uid_foreign',
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'ref.uid_local',
                    $queryBuilder->quoteIdentifier('file.uid')
                ),
                $queryBuilder->expr()->eq(
                    'ref.sys_language_uid',
                    $queryBuilder->createNamedParameter($language, \PDO::PARAM_INT)
                )
            )
            ->orderBy('ref.sorting_foreign')
            ->execute();


        if ($relatedFilesQuery->rowCount()) {
            $relatedFiles = $relatedFilesQuery->fetchAll();
            foreach ($relatedFiles as $key => $relatedFile) {
                $fileReference = $fileRepository->findFileReferenceByUid($relatedFile['uid']);
                if (GeneralUtility::inList(
                    $this->indexerConfig['fileext'],
                    $fileReference->getExtension()
                )) {
                    $filesToIndex[] = $fileReference;
                }
            }
        }

        return $filesToIndex;
    }

    /**
     * extract content from files
     *
     * @param array $fileReferences
     * @return string
     */
    protected function getContentFromFiles(array $fileReferences): string
    {
        $fileContent = '';

        /** @var FileReference $fileReference */
        foreach ($fileReferences as $fileReference) {

            /* @var $fileIndexerObject File */
            $fileIndexerObject = GeneralUtility::makeInstance( File::class, $this->pObj);

            if ($fileIndexerObject->fileInfo->setFile($fileReference)) {
                $fileContent .= $fileIndexerObject->getFileContent($fileReference->getForLocalProcessing(false)) . "\n";
                $this->pObj->logger->debug('File content has been fetched', [$fileReference->getPublicUrl()]);
                $this->fileCounter++;
            }
        }

        return $fileContent;
    }

    /**
     * @param array $relatedFiles Array of file references
     * @param array $parentRecord Expects an array with uid, sys_language_uid, starttime, endtime, fe_group
     */
    protected function indexFilesAsSeparateResults(array $relatedFiles, array $parentRecord)
    {
        /** @var FileReference $relatedFile */
        foreach ($relatedFiles as $relatedFile) {
            $filePath = $relatedFile->getForLocalProcessing(false);
            if (!file_exists($filePath)) {
                $errorMessage = 'Could not index file ' . $filePath;
                $errorMessage .= ' from parent record #' . $parentRecord['uid'] . ' (file does not exist).';
                $this->pObj->logger->warning($errorMessage);
                $this->addError($errorMessage);
            } else {
                /* @var $fileIndexerObject File */
                $fileIndexerObject = GeneralUtility::makeInstance(File::class, $this->pObj);

                // add tag to identify this index record as file
                SearchHelper::makeTags($tags, ['file']);

                if ($fileIndexerObject->fileInfo->setFile($relatedFile)) {
                    if (($content = $fileIndexerObject->getFileContent($filePath))) {
                        $this->storeFileInIndex(
                            $relatedFile,
                            $content,
                            $tags,
                            $parentRecord['fe_group'],
                            $parentRecord['pid'],
                            $parentRecord['sys_language_uid'],
                            $parentRecord['starttime'],
                            $parentRecord['endtime']
                        );
                        $this->fileCounter++;
                    } else {
                        $this->addError($fileIndexerObject->getErrors());
                        $errorMessage = 'Could not index file ' . $filePath . '.';
                        $this->pObj->logger->warning($errorMessage);
                        $this->addError($errorMessage);
                    }
                }
            }
        }
        
    }

    /**
     * Stores file content to the index. This function should be used when a parent record (eg. a news record)
     * contains links to files and the content of these files should be stored in the index separately (not together
     * with the parent record).
     * Uses feGroups, starttime, enddtime, langauge and targetPage from the parent record.
     * 
     * @param FileReference $fileReference
     * @param string $content
     * @param string $tags
     * @param string $feGroups
     * @param int $targetPage
     * @param int $sys_langauge_uid
     * @param int $starttime
     * @param int $endtime
     * @param string $logMessage
     */
    protected function storeFileInIndex(
        FileReference $fileReference,
        string $content,
        string $tags = '',
        string $feGroups = '',
        int $targetPage = 0,
        int $sys_langauge_uid = 0,
        int $starttime = 0,
        int $endtime = 0,
        string $logMessage = ''
    )
    {
        /* @var $fileIndexerObject File */
        $fileIndexerObject = GeneralUtility::makeInstance(File::class, $this->pObj);
        $fileIndexerObject->fileInfo->setFile($fileReference);

            // get metadata
        $orig_uid = $fileReference->getOriginalFile()->getUid();
        $fileProperties = $fileReference->getOriginalFile()->getProperties();

        // respect given fe_groups from indexed record and from file metadata
        if ($fileProperties['fe_groups']) {
            if ($feGroups) {
                $feGroupsContentArray = GeneralUtility::intExplode(',', $feGroups);
                $feGroupsFileArray = GeneralUtility::intExplode(',', $fileProperties['fe_groups']);
                $feGroups = implode(',', array_intersect($feGroupsContentArray, $feGroupsFileArray));
            } else {
                $feGroups = $fileProperties['fe_groups'];
            }
        }

        // assign category titles as tags
        $categories = SearchHelper::getCategories($fileProperties['uid'], 'sys_file_metadata');
        SearchHelper::makeTags($tags, $categories['title_list']);

        // assign categories as generic tags
        SearchHelper::makeSystemCategoryTags($tags, $fileProperties['uid'], 'sys_file_metadata');

        // index meta data from FAL: title, description, alternative
        $content = $this->addFileMetata($fileProperties, $content);

        // use file description as abstract
        $abstract = '';
        if ($fileProperties['description']) {
            $abstract = $fileProperties['description'];
        }

        $additionalFields = [
            'sortdate' => $fileIndexerObject->fileInfo->getModificationTime(),
            'orig_uid' => $orig_uid,
            'orig_pid' => 0,
            'directory' => $fileIndexerObject->fileInfo->getRelativePath(),
            'hash' => $fileIndexerObject->getUniqueHashForFile()
        ];

        // Store record in index table
        $this->pObj->storeInIndex(
            $this->indexerConfig['storagepid'],         // storage PID
            $fileIndexerObject->fileInfo->getName(),    // file name
            'file:' . $fileReference->getExtension(),   // content type
            $targetPage,                                // target PID: where is the single view?
            $content,                                   // indexed content
            $tags,                                      // tags
            '',                                         // typolink params for singleview
            $abstract,                                  // abstract
            $sys_langauge_uid,                          // language uid
            $starttime,                                 // starttime
            $endtime,                                   // endtime
            $feGroups,                                  // fe_group
            FALSE,                                      // debug only?
            $additionalFields                           // additional fields added by hooks
        );

        $this->pObj->logger->debug(($logMessage ? $logMessage : 'File has been stored'),[$fileReference->getPublicUrl()]);
    }
}
