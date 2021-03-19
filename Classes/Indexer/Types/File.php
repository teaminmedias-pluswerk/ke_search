<?php

namespace TeaminmediasPluswerk\KeSearch\Indexer\Types;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Stefan Froemken
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

use TeaminmediasPluswerk\KeSearch\Indexer\Filetypes\FileIndexerInterface;
use TeaminmediasPluswerk\KeSearch\Indexer\IndexerBase;
use TeaminmediasPluswerk\KeSearch\Lib\Fileinfo;
use TeaminmediasPluswerk\KeSearch\Lib\Db;
use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 * @author    Stefan Froemken
 * @author    Christian Bülter
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class File extends IndexerBase
{

    public $extConf = array(); // saves the configuration of extension ke_search_hooks
    public $app = array(); // saves the path to the executables
    public $isAppArraySet = false;

    /**
     * @var Fileinfo
     */
    public $fileInfo;

    /**
     * @var ResourceStorage
     */
    public $storage;

    /**
     * Initializes indexer for files
     *
     * @param \TeaminmediasPluswerk\KeSearch\Indexer\IndexerRunner $pObj
     */
    public function __construct($pObj)
    {
        parent::__construct($pObj);
        $this->pObj = $pObj;

        // get extension configuration of ke_search
        $this->extConf = SearchHelper::getExtConf();
        $this->fileInfo = GeneralUtility::makeInstance(Fileinfo::class);
    }

    /**
     * This function was called from indexer object and saves content to index table
     * @return string content which will be displayed in backend
     */
    public function startIndexing()
    {
        $directories = $this->indexerConfig['directories'];
        $directoryArray = GeneralUtility::trimExplode(',', $directories, true);

        if ($this->pObj->indexerConfig['fal_storage'] > 0) {
            /* @var $storageRepository StorageRepository */
            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            $this->storage = $storageRepository->findByUid($this->pObj->indexerConfig['fal_storage']);

            $files = array();
            $this->getFilesFromFal($files, $directoryArray);
        } else {
            $files = $this->getFilesFromDirectories($directoryArray);
        }

        $counter = $this->extractContentAndSaveToIndex($files);

        // show indexer content?
        return count($files) . ' files have been found for indexing.' . LF
            . $counter . ' files have been indexed.';
    }

    /** * fetches files recurively using FAL
     * @param array $files
     * @param array $directoryArray
     */
    public function getFilesFromFal(&$files, $directoryArray)
    {
        foreach ($directoryArray as $directory) {
            $folder = $this->storage->getFolder($directory);

            if ($folder->getName() != '_temp_') {
                $filesInFolder = $folder->getFiles();
                if (count($filesInFolder)) {
                    foreach ($filesInFolder as $file) {
                        if (GeneralUtility::inList($this->pObj->indexerConfig['fileext'], $file->getExtension())) {
                            $files[] = $file;
                        }
                    }
                }

                // do recursion
                $subfolders = $folder->getSubFolders();
                if (count($subfolders)) {
                    foreach ($subfolders as $subfolder) {
                        $this->getFilesFromFal($files, array($subfolder->getIdentifier()));
                    }
                }
            }
        }
    }

    /**
     * get files from given relative directory path array
     * @param array $directoryArray
     * @return array An Array containing all files of all valid directories
     */
    public function getFilesFromDirectories(array $directoryArray)
    {
        $directoryArray = $this->getAbsoluteDirectoryPath($directoryArray);
        if (is_array($directoryArray) && count($directoryArray)) {
            $files = array();
            foreach ($directoryArray as $directory) {
                $foundFiles = GeneralUtility::getAllFilesAndFoldersInPath(
                    array(),
                    $directory,
                    $this->indexerConfig['fileext']
                );

                if (is_array($foundFiles) && count($foundFiles)) {
                    foreach ($foundFiles as $file) {
                        $files[] = $file;
                    }
                }
            }
            return $files;
        } else {
            return array();
        }
    }

    /**
     * get absolute directory paths of given path in array
     * @param array $directoryArray
     * @return array An Array containing the absolute directory paths
     */
    public function getAbsoluteDirectoryPath(array $directoryArray)
    {
        if (is_array($directoryArray) && count($directoryArray)) {
            foreach ($directoryArray as $key => $directory) {
                $directory = rtrim($directory, '/');
                $directoryArray[$key] = Environment::getPublicPath() . '/' . $directory . '/';
            }
            return $directoryArray;
        } else {
            return array();
        }
    }

    /**
     * loops through an array of files an stores their content
     * to the index.
     * returns number of files indexed.
     * @param array $files
     * @return integer
     */
    public function extractContentAndSaveToIndex($files)
    {
        $counter = 0;
        if (is_array($files) && count($files)) {
            foreach ($files as $file) {
                if ($this->fileInfo->setFile($file)) {
                    // get file content, check if we have a FAL resource or a simple
                    // string containing the path to the file
                    if ($file instanceof \TYPO3\CMS\Core\Resource\File) {
                        $content = $this->getFileContent($file->getForLocalProcessing(false));
                    } else {
                        $content = $this->getFileContent($file);
                    }

                    if (!($content === false)) {
                        $this->storeToIndex($file, $content);
                        $counter++;
                    }
                }
            }
        }

        return $counter;
    }

    /**
     * get filecontent of allowed extensions
     * @param string $file
     * @return mixed false or fileinformations as array
     */
    public function getFileContent($file)
    {
        // we can continue only when given file is really file and not a directory
        if ($this->fileInfo->getIsFile()) {
            $className = 'TeaminmediasPluswerk\KeSearch\Indexer\Filetypes\\' . ucfirst($this->fileInfo->getExtension());

            // check if class exists
            if (class_exists($className)) {
                // make instance
                $fileObj = GeneralUtility::makeInstance($className, $this->pObj);

                // check if new object has interface implemented
                if ($fileObj instanceof FileIndexerInterface) {
                    // Do the check if a file has already been indexed at this early point in order
                    // to skip the time expensive "get content" process which includes calls to external tools
                    // fetch the file content directly from the index
                    $fileContent = $this->getFileContentFromIndex($this->getUniqueHashForFile());

                    // if there's no matching index entry, we execute the  "get file content" method of our new object
                    if (!$fileContent) {
                        $fileContent = $fileObj->getContent($file);
                        $this->addError($fileObj->getErrors());

                        // remove metadata separator if it appears in the content
                        $fileContent = str_replace(self::METADATASEPARATOR, ' ', $fileContent);
                    }

                    return $fileContent;
                } else {
                    return false;
                }
            } else {
                // if no indexer for this type of file exists, we do a fallback:
                // we return an empty content. Doing this at least the FAL metadata
                // can be indexed. So this makes only sense when using FAL.
                if ($this->pObj->indexerConfig['fal_storage'] > 0) {
                    return '';
                } else {
                    $errorMessage = 'No indexer for this type of file. (class ' . $className . ' does not exist).';
                    $this->pObj->logger->error($errorMessage);
                    $this->addError($errorMessage);
                    return false;
                }
            }
        } else {
            $errorMessage = $file . ' is not a file.';
            $this->pObj->logger->error($errorMessage);
            $this->addError($errorMessage);
            return false;
        }
    }

    /**
     * checks if there's an entry in the index for the given file hash. Returns the content of that entry.
     * @param string $hash
     * @return string/boolean returns false if no entry has been found, otherwise the content as string
     */
    public function getFileContentFromIndex($hash = "")
    {
        $fileContent = false;

        $queryBuilder = Db::getQueryBuilder('tx_kesearch_index');
        $queryBuilder->getRestrictions()->removeAll();
        $hashRow = $queryBuilder
            ->select('*')
            ->from('tx_kesearch_index')
            ->where(
                $queryBuilder->expr()->eq(
                    'hash',
                    $queryBuilder->quote($hash, \PDO::PARAM_STR)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if (is_array($hashRow)) {
            $fileContent = $hashRow['content'];
        }

        return $fileContent;
    }

    /**
     * get a unique hash for current file
     * this is needed for a faster check if record allready exists in indexer table
     * @return string A 25 digit MD5 hash value of current file and last modification time
     */
    public function getUniqueHashForFile()
    {
        $path = $this->fileInfo->getPath();
        $file = $this->fileInfo->getName();
        $mtime = $this->fileInfo->getModificationTime();

        return md5($path . $file . '-' . $mtime);
    }

    /**
     * creates a index entry for a given file
     * @param string $file
     * @param string $content
     */
    public function storeToIndex($file, $content)
    {
        $tags = '';

        // add tag "file" to all index records which represent a file
        SearchHelper::makeTags($tags, array('file'));

        // get data from FAL
        if ($file instanceof \TYPO3\CMS\Core\Resource\File) {
            // get file properties for this file, this information is merged from file record and meta information
            $fileProperties = $file->getProperties();
            $orig_uid = $file->getUid();
            $language_uid = $this->detectLanguage($fileProperties);

            // get raw metadata for this file
            $metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);
            $metaDataProperties = $metaDataRepository->findByFile($file);
        } else {
            $fileProperties = false;
            $orig_uid = 0;
            $language_uid = -1;
        }

        $indexRecordValues = array(
            'storagepid' => $this->indexerConfig['storagepid'],
            'title' => $this->fileInfo->getName(),
            'type' => 'file:' . $this->fileInfo->getExtension(),
            'targetpid' => 1,
            'tags' => $tags,
            'params' => '',
            'abstract' => '',
            'language_uid' => $language_uid,
            'starttime' => 0,
            'endtime' => 0,
            'fe_group' => 0,
            'debug' => false
        );

        $additionalFields = array(
            'sortdate' => $this->fileInfo->getModificationTime(),
            'orig_uid' => $orig_uid,
            'orig_pid' => 0,
            'directory' => $this->fileInfo->getRelativePath(),
            'hash' => $this->getUniqueHashForFile()
        );

        // add metadata content, frontend groups and catagory tags if FAL is used
        if ($this->pObj->indexerConfig['fal_storage'] > 0) {

            // index meta data from FAL: title, description, alternative
            $content = $this->addFileMetata($fileProperties, $content);

            // use file description as abstract
            if ($fileProperties['description']) {
                $indexRecordValues['abstract'] = $fileProperties['description'];
            }

            // respect groups from metadata
            if ($fileProperties['fe_groups']) {
                $indexRecordValues['fe_group'] = $fileProperties['fe_groups'];
            }

            // get list of assigned system categories
            $categories = SearchHelper::getCategories(
                $metaDataProperties['uid'],
                'sys_file_metadata'
            );

            // make Tags from category titles
            SearchHelper::makeTags(
                $indexRecordValues['tags'],
                $categories['title_list']
            );

            // assign categories as generic tags (eg. "syscat123")
            SearchHelper::makeSystemCategoryTags(
                $indexRecordValues['tags'],
                $metaDataProperties['uid'],
                'sys_file_metadata'
            );
        }

        // hook for custom modifications of the indexed data, e. g. the tags
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFileIndexEntry'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFileIndexEntry'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $_procObj->modifyFileIndexEntry($file, $content, $additionalFields, $indexRecordValues, $this);
            }
        }

        // store record in index table
        $this->pObj->storeInIndex(
            $indexRecordValues['storagepid'],   // storage PID
            $indexRecordValues['title'],        // page title
            $indexRecordValues['type'],         // content type
            $indexRecordValues['targetpid'],    // target PID: where is the single view?
            $content,                           // indexed content, includes the title (linebreak after title)
            $indexRecordValues['tags'],         // tags
            $indexRecordValues['params'],       // typolink params for singleview
            $indexRecordValues['abstract'],     // abstract
            $indexRecordValues['language_uid'], // language uid
            $indexRecordValues['starttime'],    // starttime
            $indexRecordValues['endtime'],      // endtime
            $indexRecordValues['fe_group'],     // fe_group
            $indexRecordValues['debug'],        // debug only?
            $additionalFields                    // additional fields added by hooks
        );
    }

    /**
     * Tries to detect the language of file from metadata field 'language' and returns the language_uid.
     * The field 'language' comes with the optional extension 'filemetadata'.
     * Returns -1 ("all languages") language could not be determined.
     *
     * @param array $fileProperties
     * @return int
     */
    protected function detectLanguage(array $fileProperties): int
    {
        $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
        $languages = [];
        /** @var Site $site */
        foreach ($sites as $site) {
            $siteLanguages = $site->getLanguages();
            foreach ($siteLanguages as $siteLanguageId => $siteLanguage) {
                if ($siteLanguage->getLocale()) {
                    $languages[strtolower($siteLanguage->getLocale())] = $siteLanguageId;
                }
                if ($siteLanguage->getTitle()) {
                    $languages[strtolower($siteLanguage->getTitle())] = $siteLanguageId;
                }
                if ($siteLanguage->getHreflang()) {
                    $languages[strtolower($siteLanguage->getHreflang())] = $siteLanguageId;
                }
                if ($siteLanguage->getTwoLetterIsoCode()) {
                    $languages[strtolower($siteLanguage->getTwoLetterIsoCode())] = $siteLanguageId;
                }
            }
        }

        if (array_key_exists($fileProperties['language'], $languages)) {
            $languageUid = $languages[$fileProperties['language']];
        } else {
            $languageUid = -1;
        }
        return $languageUid;
    }
}
