<?php

namespace TeaminmediasPluswerk\KeSearch\Indexer\Types;

/***************************************************************
 *  Copyright notice
 *  (c) 2013 Christian Bülter (kennziffer.com) <buelter@kennziffer.com>
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

use TeaminmediasPluswerk\KeSearch\Indexer\IndexerBase;
use TeaminmediasPluswerk\KeSearch\Lib\Db;
use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
use TYPO3\CMS\Core\Resource\FileReference;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\FileRepository;

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 * @author    Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 * @author    Stefan Frömken
 * @author    Christian Bülter (kennziffer.com) <buelter@kennziffer.com>
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class News extends IndexerBase
{

    /** @var FileRepository $fileRepository  */
    protected $fileRepository = NULL;

    /** @var int $fileCounter */
    protected $fileCounter = 0;

    /**
     * Initializes indexer for news
     *
     * @param \TeaminmediasPluswerk\KeSearch\Indexer\IndexerRunner $pObj
     */
    public function __construct($pObj)
    {
        parent::__construct($pObj);
        $this->pObj = $pObj;
        $this->fileRepository = GeneralUtility::makeInstance(FileRepository::class);
    }

    /**
     * This function was called from indexer object and saves content to index table
     * @return string content which will be displayed in backend
     */
    public function startIndexing()
    {
        $content = '';
        $table = 'tx_news_domain_model_news';

        // get the pages from where to index the news
        $indexPids = $this->getPidList(
            $this->indexerConfig['startingpoints_recursive'],
            $this->indexerConfig['sysfolder'],
            $table
        );

        // add the tags of each page to the global page array
        if ($this->indexerConfig['index_use_page_tags']) {
            $this->pageRecords = $this->getPageRecords($indexPids);
            $this->addTagsToRecords($indexPids);
        }

        // get all the news entries to index, don't index hidden or
        // deleted news, BUT  get the news with frontend user group
        // access restrictions or time (start / stop) restrictions.
        // Copy those restrictions to the index.
        $fields = '*';
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_index');
        $where = [];
        $where[] = $queryBuilder->expr()->in('pid', implode(',', $indexPids));

        // index archived news
        // 0: index all news
        // 1: index only active (not archived) news
        // 2: index only archived news
        if ($this->indexerConfig['index_news_archived'] == 1) {
            $where[] = $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq(
                    'archive',
                    $queryBuilder->quote(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    'archive',
                    $queryBuilder->quote(time(), \PDO::PARAM_INT)
                )
            );
        } elseif ($this->indexerConfig['index_news_archived'] == 2) {
            $where[] = $queryBuilder->expr()->andX(
                $queryBuilder->expr()->gt(
                    'archive',
                    $queryBuilder->quote(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->lt(
                    'archive',
                    $queryBuilder->quote(time(), \PDO::PARAM_INT)
                )
            );
        }

        $res = $queryBuilder
            ->select($fields)
            ->from($table)
            ->where(...$where)
            ->execute();

        $indexedNewsCounter = 0;
        $resCount = $res->rowCount();

        if ($resCount) {
            while (($newsRecord = $res->fetch())) {

                $this->pObj->logger->debug('Indexing news record "' . $newsRecord['title'] .'"', [
                    'uid' => $newsRecord['uid'],
                    'pid' => $newsRecord['pid']
                ]);

                // get category data for this news record (list of
                // assigned categories and single view from category, if it exists)
                $categoryData = $this->getCategoryData($newsRecord);

                // If mode equals 2 ('choose categories for indexing')
                // check if the current news record has one of the categories
                // assigned that should be indexed.
                // mode 1 means 'index all news no matter what category
                // they have'
                if ($this->indexerConfig['index_news_category_mode'] == '2') {
                    $isInList = false;
                    foreach ($categoryData['uid_list'] as $catUid) {
                        // if category was found in list, set isInList
                        // to true and break further processing.
                        if (GeneralUtility::inList(
                            $this->indexerConfig['index_extnews_category_selection'],
                            $catUid
                        )
                        ) {
                            $isInList = true;
                            break;
                        }
                    }

                    // if category was not fount stop further processing
                    // and continue with next news record
                    if (!$isInList) {
                        continue;
                    }
                }

                // compile the information which should go into the index:
                // title, teaser, bodytext
                $type = 'news';
                $title = strip_tags($newsRecord['title']);
                $abstract = strip_tags($newsRecord['teaser']);
                $content = strip_tags($newsRecord['bodytext']);

                // add additional fields to the content:
                // alternative_title, author, author_email, keywords
                if (isset($newsRecord['author'])) {
                    $content .= "\n" . strip_tags($newsRecord['author']);
                }
                if (isset($newsRecord['author_email'])) {
                    $content .= "\n" . strip_tags($newsRecord['author_email']);
                }
                if (!empty($newsRecord['keywords'])) {
                    $content .= "\n" . $newsRecord['keywords'];
                }

                // index attached content elements
                $contentElements = $this->getAttachedContentElements($newsRecord);
                $content .= $this->getContentFromContentElements($contentElements);

                // get related files if fileext is configured
                if (!empty($this->indexerConfig['fileext'])) {
                    $relatedFiles = $this->getRelatedFiles($newsRecord);
                    if (!empty($relatedFiles)) {
                        if ($this->indexerConfig['index_news_files_mode'] === 1) {
                            // add file content to news index record
                            $content .= $this->getContentFromRelatedFiles(
                                $relatedFiles,
                                $newsRecord['uid']
                            );
                        } else {
                            // index file as separate index record
                            $this->indexFilesAsSeparateResults($relatedFiles, $newsRecord);
                        }
                    }
                }

                // create content
                $fullContent = '';
                if (isset($abstract)) {
                    $fullContent .= $abstract . "\n";
                }
                $fullContent .= $content;

                // make it possible to modify the indexerConfig via hook
                $indexerConfig = $this->indexerConfig;

                // create params and custom single view page:
                // if it is a default news (type = 0), add params
                // if it is an internal page (type = 1), put that into the "targetpid" field
                // if it is an external url (type = 2), put that into the "params" field
                if ($newsRecord['type'] == 1) {
                    $indexerConfig['targetpid'] = $newsRecord['internalurl'];
                    $params = '';
                } else {
                    if ($newsRecord['type'] == 2) {
                        $type = 'external:news';
                        $params = $newsRecord['externalurl'];
                    } else {
                        // overwrite the targetpid if there is a category assigned
                        // which has its own single view page
                        if ($categoryData['single_pid']) {
                            $indexerConfig['targetpid'] = $categoryData['single_pid'];
                        }

                        // create params for news single view, example:
                        // index.php?id=123&tx_news_pi1[news]=9&tx_news_pi1[controller]=News&tx_news_pi1[action]=detail
                        // NOTE that translated news records are linked by their l10n_parent uid (and overlaid later)
                        $paramsSingleView['tx_news_pi1']['news'] = $newsRecord['l10n_parent']
                            ? $newsRecord['l10n_parent']
                            : $newsRecord['uid'];
                        $paramsSingleView['tx_news_pi1']['controller'] = 'News';
                        $paramsSingleView['tx_news_pi1']['action'] = 'detail';
                        $params = '&' . http_build_query($paramsSingleView, null, '&');
                        $params = rawurldecode($params);
                    }
                }

                // add tags from pages
                if ($indexerConfig['index_use_page_tags']) {
                    $tags = $this->pageRecords[intval($newsRecord['pid'])]['tags'];
                } else {
                    $tags = '';
                }

                // add keywords from ext:news as tags
                $tags = $this->addTagsFromNewsKeywords($tags, $newsRecord);

                // add tags from ext:news as tags
                $tags = $this->addTagsFromNewsTags($tags, $newsRecord);

                // add categories from from ext:news as tags
                $tags = $this->addTagsFromNewsCategories($tags, $categoryData);

                // add system categories as tags
                SearchHelper::makeSystemCategoryTags($tags, $newsRecord['uid'], $table);

                // set additional fields
                $additionalFields = array();
                $additionalFields['orig_uid'] = $newsRecord['uid'];
                $additionalFields['orig_pid'] = $newsRecord['pid'];
                $additionalFields['sortdate'] = $newsRecord['crdate'];
                if (isset($newsRecord['datetime']) && $newsRecord['datetime'] > 0) {
                    $additionalFields['sortdate'] = $newsRecord['datetime'];
                }

                // hook for custom modifications of the indexed data, e.g. the tags
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyExtNewsIndexEntry'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyExtNewsIndexEntry'] as
                             $_classRef) {
                        $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($_classRef);
                        $_procObj->modifyExtNewsIndexEntry(
                            $title,
                            $abstract,
                            $fullContent,
                            $params,
                            $tags,
                            $newsRecord,
                            $additionalFields,
                            $indexerConfig,
                            $categoryData,
                            $this
                        );
                    }
                }

                // store this record to the index
                $this->pObj->storeInIndex(
                    $indexerConfig['storagepid'],    // storage PID
                    $title,                          // page title
                    $type,                           // content type
                    $indexerConfig['targetpid'],     // target PID: where is the single view?
                    $fullContent,                    // indexed content, includes the title (linebreak after title)
                    $tags,                           // tags
                    $params,                         // typolink params for singleview
                    $abstract,                       // abstract
                    $newsRecord['sys_language_uid'], // language uid
                    $newsRecord['starttime'],        // starttime
                    $newsRecord['endtime'],          // endtime
                    $newsRecord['fe_group'],         // fe_group
                    false,                           // debug only?
                    $additionalFields                // additional fields added by hooks
                );
                $indexedNewsCounter++;
            }

            $logMessage = 'Indexer "' . $this->indexerConfig['title'] . '" finished'
                . ' ('.$indexedNewsCounter.' records processed)';
            $this->pObj->logger->info($logMessage);
            $content = '<p><b>Indexer "' . $this->indexerConfig['title'] . '":</b><br />' . "\n"
                . $indexedNewsCounter . ' News and ' . $this->fileCounter
                . ' related files have been indexed.</p> ' . "\n";

            $content .= $this->showErrors();
            $content .= $this->showTime();
        }
        return $content;
    }


    /**
     * checks if there is a category assigned to the $newsRecord which has
     * its own single view page and if yes, returns the uid of the page
     * in $catagoryData['single_pid'].
     * It also compiles a list of all assigned categories and returns
     * it as an array in $categoryData['uid_list']. The titles of the
     * categories are returned in $categoryData['title_list'] (array)
     *
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 26.06.13 14:34
     * @param array $newsRecord
     * @return array
     */
    private function getCategoryData($newsRecord)
    {
        $categoryData = array(
            'single_pid' => 0,
            'uid_list' => array(),
            'title_list' => array()
        );

        $queryBuilder = Db::getQueryBuilder('sys_category');

        $where = [];
        $where[] = $queryBuilder->expr()->eq(
            'sys_category.uid',
            $queryBuilder->quoteIdentifier('sys_category_record_mm.uid_local')
        );
        $where[] = $queryBuilder->expr()->eq(
            'tx_news_domain_model_news.uid',
            $queryBuilder->quoteIdentifier('sys_category_record_mm.uid_foreign')
        );
        $where[] = $queryBuilder->expr()->eq(
            'tx_news_domain_model_news.uid',
            $queryBuilder->createNamedParameter($newsRecord['uid'], \PDO::PARAM_INT)
        );

        $catRes = $queryBuilder
            ->select(
                'sys_category.uid',
                'sys_category.single_pid',
                'sys_category.title'
            )
            ->from('sys_category')
            ->from('sys_category_record_mm')
            ->from('tx_news_domain_model_news')
            ->orderBy('sys_category_record_mm.sorting')
            ->where(...$where)
            ->execute();

        while (($newsCat = $catRes->fetch())) {
            $categoryData['uid_list'][] = $newsCat['uid'];
            $categoryData['title_list'][] = $newsCat['title'];
            if ($newsCat['single_pid'] && !$categoryData['single_pid']) {
                $categoryData['single_pid'] = $newsCat['single_pid'];
            }
        }

        return $categoryData;
    }

    /**
     * adds tags from the ext:news "keywords" field to the index entry
     *
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 26.06.13 14:27
     * @param string $tags
     * @param array $newsRecord
     * @return string
     */
    private function addTagsFromNewsKeywords($tags, $newsRecord)
    {
        if (!empty($newsRecord['keywords'])) {
            $keywordsList = GeneralUtility::trimExplode(',', $newsRecord['keywords']);
            foreach ($keywordsList as $keyword) {
                SearchHelper::makeTags($tags, array($keyword));
            }
        }

        return $tags;
    }

    /**
     * Adds tags from the ext:news table "tags" as ke_search tags to the index entry
     *
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 26.06.13 14:25
     * @param string $tags
     * @param array $newsRecord
     * @return string comma-separated list of tags
     */
    private function addTagsFromNewsTags($tags, $newsRecord)
    {
        $queryBuilder = Db::getQueryBuilder('tx_news_domain_model_news');
        $resTag = $queryBuilder
            ->select('tag.title')
            ->from('tx_news_domain_model_news', 'news')
            ->from('tx_news_domain_model_news_tag_mm', 'mm')
            ->from('tx_news_domain_model_tag', 'tag')
            ->where(
                $queryBuilder->expr()->eq(
                    'news.uid',
                    $queryBuilder->quoteIdentifier('mm.uid_local')
                ),
                $queryBuilder->expr()->eq(
                    'tag.uid',
                    $queryBuilder->quoteIdentifier('mm.uid_foreign')
                ),
                $queryBuilder->expr()->eq(
                    'news.uid',
                    $queryBuilder->createNamedParameter($newsRecord['uid'], \PDO::PARAM_INT)
                )
            )
            ->execute();

        while (($newsTag = $resTag->fetch())) {
            SearchHelper::makeTags($tags, array($newsTag['title']));
        }

        return $tags;
    }

    /**
     * creates tags from category titles
     *
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 26.06.13 15:49
     * @param string $tags
     * @param array $categoryData
     * @return string
     */
    private function addTagsFromNewsCategories($tags, $categoryData)
    {
        SearchHelper::makeTags($tags, $categoryData['title_list']);
        return $tags;
    }

    /**
     * Fetches related content elements for a given news record.
     *
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 15.10.15
     * @param array $newsRecord
     * @return array
     */
    public function getAttachedContentElements($newsRecord)
    {
        $queryBuilder = Db::getQueryBuilder('tt_content');
        $res = $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_news_related_news',
                    $queryBuilder->createNamedParameter($newsRecord['uid'], \PDO::PARAM_INT)
                )
            )
            ->execute();

        $contentElements = array();
        if ($res->rowCount()) {
            while (($contentElement = $res->fetch())) {
                $contentElements[] = $contentElement;
            }
        }

        return $contentElements;
    }


    /**
     * fetches the bare text content of an array of content elements.
     * makes use of the already given functions the page indexer provides.
     *
     * @author Christian Bülter <christian.buelter@inmedias.de>
     * @since 15.10.15
     * @param array $contentElements
     * @return string
     */
    public function getContentFromContentElements($contentElements)
    {
        $content = '';

        // get content from content elements
        // NOTE: If the content elements contain links to files, those files will NOT be indexed.
        // NOTE: There's no restriction to certain content element types .
        // All attached content elements will be indexed. Only fields "header" and "bodytext" will be indexed.
        if (count($contentElements)) {
            /* @var $pageIndexerObject Page */
            $pageIndexerObject = GeneralUtility::makeInstance(Page::class, $this->pObj);

            foreach ($contentElements as $contentElement) {
                // index header, add header only if not set to "hidden"
                if ($contentElement['header_layout'] != 100) {
                    $content .= "\n" . strip_tags($contentElement['header']) . "\n";
                }

                // index bodytext (main content)
                $content .= "\n" . $pageIndexerObject->getContentFromContentElement($contentElement);
            }
        }

        return $content;
    }


    /**
     * get files related to current news record
     * @param array $newsRecord
     */
    protected function getRelatedFiles($newsRecord)
    {
        $relatedFiles = [];

        $queryBuilder = Db::getQueryBuilder('sys_file');
        $relatedFilesQuery = $queryBuilder
            ->select('ref.uid')
            ->from('sys_file', 'file')
            ->from('sys_file_reference', 'ref')
            ->where(
                $queryBuilder->expr()->eq(
                    'ref.tablenames',
                    $queryBuilder->createNamedParameter(
                        'tx_news_domain_model_news',
                        \PDO::PARAM_STR
                    )
                ),
                $queryBuilder->expr()->eq(
                    'ref.fieldname',
                    $queryBuilder->createNamedParameter(
                        'fal_related_files',
                        \PDO::PARAM_STR
                    )
                ),
                $queryBuilder->expr()->eq(
                    'ref.uid_foreign',
                    $queryBuilder->createNamedParameter(
                        $newsRecord['uid'],
                        \PDO::PARAM_INT
                    )
                ),
                $queryBuilder->expr()->eq(
                    'ref.uid_local',
                    $queryBuilder->quoteIdentifier('file.uid')
                )
            )
            ->orderBy('ref.sorting_foreign')
            ->execute();

        if ($relatedFilesQuery->rowCount()) {
            return $this->getFilesForIndexing($relatedFilesQuery->fetchAll(), $newsRecord['uid']);
        }

        return $relatedFiles;
    }

    /**
     * get files matching configured extensions
     * @param $relatedFiles
     * @param int $newsUid
     */
    protected function getFilesForIndexing($relatedFiles, $newsUid)
    {
        $filesToIndex = [];

        foreach ($relatedFiles as $key => $relatedFile) {
            $fileReference = $this->fileRepository->findFileReferenceByUid($relatedFile['uid']);
            if (GeneralUtility::inList(
                $this->indexerConfig['fileext'],
                $fileReference->getExtension()
            )) {
                $filesToIndex[] = $fileReference;
            }
        }

        return $filesToIndex;
    }


    /**
     * index related files as seperate file index records
     * @param array $files
     * @param array $newsRecord
     */
    protected function indexFilesAsSeparateResults($relatedFiles, $newsRecord)
    {
        /** @var FileReference $relatedFile */
        foreach ($relatedFiles as $relatedFile) {
            $filePath = $relatedFile->getForLocalProcessing(false);
            if (!file_exists($filePath)) {
                $errorMessage = 'Could not index file ' . $filePath;
                $errorMessage .= ' in news record #' . $newsUid . ' (file does not exist).';
                $this->pObj->logger->warning($errorMessage);
                $this->addError($errorMessage);
            } else {
                /* @var $fileIndexerObject File */
                $fileIndexerObject = GeneralUtility::makeInstance(File::class, $this->pObj);

                // add tag to identify this index record as file
                SearchHelper::makeTags($tags, ['file']);

                if ($fileIndexerObject->fileInfo->setFile($relatedFile)) {
                    if (($content = $fileIndexerObject->getFileContent($filePath))) {
                        $this->storeFileContentToIndex(
                            $relatedFile,
                            $content,
                            $fileIndexerObject,
                            $newsRecord['fe_group'],
                            $tags,
                            $newsRecord
                        );
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
     * Store the file content and additional information to the index
     * @param FileReference $fileReference File reference object
     * @param string $content file text content
     * @param File $fileIndexerObject
     * @param string $feGroups comma list of groups to assign
     * @param array $newsRecord the news row the file was assigned to
     */
    public function storeFileContentToIndex(
        $fileReference,
        $content,
        $fileIndexerObject,
        $feGroups,
        $tags,
        $newsRecord
    )
    {
        // get metadata
        $orig_uid = $fileReference->getOriginalFile()->getUid();
        $metadata = $fileReference->getOriginalFile()->_getMetaData();

        if ($metadata['fe_groups']) {
            if ($feGroups) {
                $feGroupsContentArray = GeneralUtility::intExplode(',', $feGroups);
                $feGroupsFileArray = GeneralUtility::intExplode(',', $metadata['fe_groups']);
                $feGroups = implode(',', array_intersect($feGroupsContentArray, $feGroupsFileArray));
            } else {
                $feGroups = $metadata['fe_groups'];
            }
        }

        // assign category titles as tags
        $categories = SearchHelper::getCategories($metadata['uid'], 'sys_file_metadata');
        SearchHelper::makeTags($tags, $categories['title_list']);

        // assign categories as generic tags
        SearchHelper::makeSystemCategoryTags($tags, $metadata['uid'], 'sys_file_metadata');

        if ($metadata['title']) {
            $content = $metadata['title'] . "\n" . $content;
        }

        $abstract = '';
        if ($metadata['description']) {
            $abstract = $metadata['description'];
            $content = $metadata['description'] . "\n" . $content;
        }

        if ($metadata['alternative']) {
            $content .= "\n" . $metadata['alternative'];
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
            $newsRecord['pid'],                         // target PID: where is the single view?
            $content,                                   // indexed content
            $tags,                                      // tags
            '',                                         // typolink params for singleview
            $abstract,                                  // abstract
            $newsRecord['sys_language_uid'],            // language uid
            $newsRecord['starttime'],                   // starttime
            $newsRecord['endtime'],                     // endtime
            $feGroups,                                  // fe_group
            FALSE,                                      // debug only?
            $additionalFields                           // additional fields added by hooks
        );

        $this->pObj->logger->debug(
            'related file was indexed for news #' . $newsRecord['uid'],
            [$fileReference->getPublicUrl()]
        );

        $this->fileCounter++;
    }


    /**
     * extract content from files to index
     * @param array $relatedFiles
     * @param int $newsUid
     * @return string
     */
    protected function getContentFromRelatedFiles($relatedFiles, $newsUid)
    {
        $fileContent = '';

        /** @var FileReference $relatedFile */
        foreach ($relatedFiles as $relatedFile) {

            /* @var $fileIndexerObject File */
            $fileIndexerObject = GeneralUtility::makeInstance(
                File::class,
                $this->pObj
            );

            if ($fileIndexerObject->fileInfo->setFile($relatedFile)) {
                $fileContent .= $fileIndexerObject->getFileContent(
                        $relatedFile->getForLocalProcessing(false)
                    ) . "\n";

                $this->pObj->logger->debug(
                    'related file was indexed for news #' . $newsUid,
                    [$relatedFile->getPublicUrl()]
                );

                $this->fileCounter++;
            }
        }

        return $fileContent;
    }

}
