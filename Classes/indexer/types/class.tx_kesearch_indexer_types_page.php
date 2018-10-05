<?php

/* ***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
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
 * *************************************************************
 *
 * @author Andreas Kiefer <kiefer@kennziffer.com>
 * @author Christian Bülter <buelter@kennziffer.com>
 */

use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Backend\Utility\BackendUtility;
use \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use \TYPO3\CMS\Frontend\DataProcessing\FilesProcessor;
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use \TYPO3\CMS\Core\Resource\ResourceFactory;
use \TYPO3\CMS\Core\Resource\FileInterface;
use \TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;

define('DONOTINDEX', -3);


/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 * @author    Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 * @author    Stefan Froemken
 * @author    Christian Bülter <christian.buelter@inmedias.de>
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class tx_kesearch_indexer_types_page extends tx_kesearch_indexer_types
{

    /**
     * this array contains all data of all pages in the default language
     * @var array
     */
    public $pageRecords = array();

    /**
     * this array contains all data of all pages, but additionally with all available languages
     * @var array
     */
    public $cachedPageRecords = array(); //

    /**
     * this array contains the system languages
     * @var array
     */
    public $sysLanguages = array();

    /**
     * this array contains the definition of which content element types should be indexed
     * @var array
     */
    public $defaultIndexCTypes = array(
        'text',
        'textmedia',
        'textpic',
        'bullets',
        'table',
        'html',
        'header',
        'uploads'
    );

    /**
     * this array contains the definition of which file content element types should be indexed
     * @var array
     */
    public $fileCTypes = array('uploads');

    /**
     * this array contains the definition of which page
     * types (field doktype in pages table) should be indexed.
     * Standard = 1
     * Advanced = 2
     * External URL = 3
     * Shortcut = 4
     * Not in menu = 5
     * Backend User Section = 6
     * Mountpoint = 7
     * Spacer = 199
     * SysFolder = 254
     * Recycler = 255
     * @var array
     */
    public $indexDokTypes = array(1, 2, 5);

    /*
     * Name of indexed elements. Will be overwritten in content element indexer.
     */
    public $indexedElementsName = 'pages';

    /* @var $fileRepository \TYPO3\CMS\Core\Resource\FileRepository */
    public $fileRepository;

    /**
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * @var FilesProcessor
     */
    public $filesProcessor;

    /**
     * Files Processor configuration
     * @var array
     */
    public $filesProcessorConfiguration = [
        'references.' => [
            'fieldName' => 'media',
            'table' => 'tt_content'
        ],
        'collections.' => [
            'field' => 'file_collections'
        ],
        'sorting.' => [
            'field ' => 'filelink_sorting'
        ],
        'as' => 'files'
    ];

    /**
     * counter for how many pages we have indexed
     * @var integer
     */
    public $counter = 0;

    /**
     * counter for how many pages without content we found
     * @var integer
     */
    public $counterWithoutContent = 0;

    /**
     * counter for how many files we have indexed
     * @var integer
     */
    public $fileCounter = 0;

    /**
     * sql query for content types
     * @var string
     */
    public $whereClauseForCType = '';

    /**
     * tx_kesearch_indexer_types_page constructor.
     * @param $pObj
     */
    public function __construct($pObj)
    {
        parent::__construct($pObj);

        // set content types which should be index, fall back to default if not defined
        if (empty($this->indexerConfig['contenttypes'])) {
            $content_types_temp = $this->defaultIndexCTypes;
        } else {
            $content_types_temp = GeneralUtility::trimExplode(
                ',',
                $this->indexerConfig['contenttypes']
            );
        }

        if (!empty($this->indexerConfig['index_page_doctypes'])) {
            $this->indexDokTypes = GeneralUtility::trimExplode(
                ',',
                $this->indexerConfig['index_page_doctypes']
            );
        }

        // create a mysql WHERE clause for the content element types
        $cTypes = array();
        foreach ($content_types_temp as $value) {
            $cTypes[] = 'CType="' . $value . '"';
        }
        $this->whereClauseForCType = implode(' OR ', $cTypes);

        // get all available sys_language_uid records
        /** @var TranslationConfigurationProvider $translationProvider */
        $translationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $this->sysLanguages = $translationProvider->getSystemLanguages();

        // make file repository
        /* @var $this ->fileRepository \TYPO3\CMS\Core\Resource\FileRepository */
        $this->fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');

        // make cObj
        $this->cObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');

        // make filesProcessor
        $this->filesProcessor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\DataProcessing\\FilesProcessor');
    }

    /**
     * This function was called from indexer object and saves content to index table
     * @return string content which will be displayed in backend
     */
    public function startIndexing()
    {
        // get all pages. Regardeless if they are shortcut, sysfolder or external link
        $indexPids = $this->getPagelist(
            $this->indexerConfig['startingpoints_recursive'],
            $this->indexerConfig['single_pages']
        );

        // add complete page record to list of pids in $indexPids
        $this->pageRecords = $this->getPageRecords($indexPids);

        // create an array of cached page records which contains pages in
        // default and all other languages registered in the system
        foreach ($this->pageRecords as $pageRecord) {
            $this->addLocalizedPagesToCache($pageRecord);
        }

        // create a new list of allowed pids
        $indexPids = array_keys($this->pageRecords);

        // add tags to pages of doktype standard, advanced, shortcut and "not in menu"
        // add tags also to subpages of sysfolders (254), since we don't want them to be
        // excluded (see: http://forge.typo3.org/issues/49435)
        $where = ' (doktype = 1 OR doktype = 2 OR doktype = 4 OR doktype = 5 OR doktype = 254) ';

        // add the tags of each page to the global page array
        $this->addTagsToRecords($indexPids, $where);

        // loop through pids and collect page content and tags
        foreach ($indexPids as $uid) {
            if ($uid) {
                $this->getPageContent($uid);
            }
        }

        // show indexer content
        $content = '<p><b>Indexer "' . $this->indexerConfig['title'] . '": </b><br />'
            . count($indexPids)
            . ' pages in '
            . count($this->sysLanguages)
            . ' languages have been found for indexing.<br />' . "\n"
            . $this->counter
            . ' '
            . $this->indexedElementsName
            . ' have been indexed ('
            . $this->counterWithoutContent
            . ' more had no content). <br />'
            . "\n"
            . $this->fileCounter
            . ' files have been indexed.<br />'
            . "\n"
            . '</p>'
            . "\n";

        $content .= $this->showErrors();
        $content .= $this->showTime();

        return $content;
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
        $fields = '*';
        $table = 'pages';
        $where = 'uid IN (' . implode(',', $uids) . ') AND no_search <> 1';

        $pages = array();
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
        while ($pageRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $pages[$pageRow['uid']] = $pageRow;
        }
        return $pages;
    }

    /**
     * add localized page records to a cache/globalArray
     * This is much faster than requesting the DB for each tt_content-record
     * @param array $pageRow
     * @return void
     */
    public function addLocalizedPagesToCache($pageRow)
    {
        // create entry in cachedPageRecods for default language
        $this->cachedPageRecords[0][$pageRow['uid']] = $pageRow;

        // create entry in cachedPageRecods for additional languages, skip default language 0
        foreach ($this->sysLanguages as $sysLang) {
            if ($sysLang['uid'] > 0) {
                list($pageOverlay) = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordsByField(
                    'pages_language_overlay',
                    'pid',
                    $pageRow['uid'],
                    'AND sys_language_uid=' . (int)$sysLang['uid']
                );
                if ($pageOverlay) {
                    $this->cachedPageRecords[$sysLang['uid']][$pageRow['uid']] = $pageOverlay + $pageRow;
                }
            }
        }
    }

    /**
     * creates a rootline and searches for valid access restrictions
     * returns the access restrictions for the given page as an array:
     *    $accessRestrictions = array(
     *        'hidden' => 0,
     *        'fe_group' => ',
     *        'starttime' => 0,
     *        'endtime' => 0
     *    );
     * @param integer $currentPageUid
     * @return array
     */
    public function getInheritedAccessRestrictions($currentPageUid)
    {

        // get the rootline, start with the current page and go up
        $pageUid = $currentPageUid;
        $tempRootline = array(intval($this->cachedPageRecords[0][$currentPageUid]['pageUid']));
        while ($this->cachedPageRecords[0][$pageUid]['pid'] > 0) {
            $pageUid = intval($this->cachedPageRecords[0][$pageUid]['pid']);
            if (is_array($this->cachedPageRecords[0][$pageUid])) {
                $tempRootline[] = $pageUid;
            }
        }

        // revert the ordering of the rootline so it starts with the
        // page at the top of the tree
        krsort($tempRootline);
        $rootline = array();
        foreach ($tempRootline as $pageUid) {
            $rootline[] = $pageUid;
        }

        // access restrictions:
        // a) hidden field
        // b) frontend groups
        // c) publishing and expiration date
        $inheritedAccessRestrictions = array(
            'hidden' => 0,
            'fe_group' => '',
            'starttime' => 0,
            'endtime' => 0
        );

        // collect inherited access restrictions
        // since now we have a full rootline of the current page
        // (0 = level 0, 1 = level 1 and so on),
        // we can fetch the access restrictions from pages above
        foreach ($rootline as $pageUid) {
            if ($this->cachedPageRecords[0][$pageUid]['extendToSubpages']) {
                $inheritedAccessRestrictions['hidden'] = $this->cachedPageRecords[0][$pageUid]['hidden'];
                $inheritedAccessRestrictions['fe_group'] = $this->cachedPageRecords[0][$pageUid]['fe_group'];
                $inheritedAccessRestrictions['starttime'] = $this->cachedPageRecords[0][$pageUid]['starttime'];
                $inheritedAccessRestrictions['endtime'] = $this->cachedPageRecords[0][$pageUid]['endtime'];
            }
        }

        // use access restrictions of current page if set otherwise use
        // inherited access restrictions
        $accessRestrictions = array(
            'hidden' => $this->cachedPageRecords[0][$currentPageUid]['hidden']
                ? $this->cachedPageRecords[0][$currentPageUid]['hidden'] : $inheritedAccessRestrictions['hidden'],
            'fe_group' => $this->cachedPageRecords[0][$currentPageUid]['fe_group']
                ? $this->cachedPageRecords[0][$currentPageUid]['fe_group'] : $inheritedAccessRestrictions['fe_group'],
            'starttime' => $this->cachedPageRecords[0][$currentPageUid]['starttime']
                ? $this->cachedPageRecords[0][$currentPageUid]['starttime'] : $inheritedAccessRestrictions['starttime'],
            'endtime' => $this->cachedPageRecords[0][$currentPageUid]['endtime']
                ? $this->cachedPageRecords[0][$currentPageUid]['endtime'] : $inheritedAccessRestrictions['endtime'],
        );

        return $accessRestrictions;
    }

    /**
     * get content of current page and save data to db
     *
     * @param integer $uid page-UID that has to be indexed
     */
    public function getPageContent($uid)
    {
        // get content elements for this page
        $fields = 'uid, pid, header, bodytext, CType, sys_language_uid, header_layout, fe_group, file_collections, filelink_sorting';

        // hook to modify the page content fields
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPageContentFields'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPageContentFields'] as $_classRef) {
                $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                $_procObj->modifyPageContentFields(
                    $fields,
                    $this
                );
            }
        }

        $table = 'tt_content';
        $where = 'pid = ' . intval($uid);
        $where .= ' AND (' . $this->whereClauseForCType . ')';

        // add condition for not indexing gridelement columns with colPos = -2 (= invalid)
        if (ExtensionManagementUtility::isLoaded('gridelements')) {
            $where .= ' AND colPos <> -2 ';
            $fields .= ', (SELECT hidden FROM tt_content as t2 WHERE t2.uid = tt_content.tx_gridelements_container)' .
                ' as parentGridHidden';
        }

        $where .= BackendUtility::BEenableFields($table);
        $where .= BackendUtility::deleteClause($table);

        // Get access restrictions for this page, this access restrictions apply to all
        // content elements of this pages. Individual access restrictions
        // set for the content elements will be ignored. Use the content
        // element indexer if you need that feature!
        $pageAccessRestrictions = $this->getInheritedAccessRestrictions($uid);

        // add ke_search tags current page
        $tags = $this->pageRecords[intval($uid)]['tags'];

        // add system categories as tags
        tx_kesearch_helper::makeSystemCategoryTags($tags, $uid, $table);

        // Compile content for this page from individual content elements with
        // respect to the language.
        // While doing so, fetch also content from attached files and write
        // their content directly to the index.
        $ttContentRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, $where);
        $pageContent = array();
        if (count($ttContentRows)) {
            foreach ($ttContentRows as $ttContentRow) {
                if (ExtensionManagementUtility::isLoaded('gridelements') && $ttContentRow['parentGridHidden'] === '1') {
                    // If parent grid element is hidden, don't index this content element
                    continue;
                }

                $content = '';

                // index header
                // add header only if not set to "hidden"
                if ($ttContentRow['header_layout'] != 100) {
                    $content .= strip_tags($ttContentRow['header']) . "\n";
                }

                // index content of this content element and find attached or linked files.
                // Attached files are saved as file references, the RTE links directly to
                // a file, thus we get file objects.
                // Files go into the index no matter if "index_content_with_restrictions" is set
                // or not, that means even if protected content elements do not go into the index,
                // files do. Since each file gets it's own index entry with correct access
                // restrictons, that's no problem from a access permission perspective (in fact, it's a feature).
                if (in_array($ttContentRow['CType'], $this->fileCTypes)) {
                    $fileObjects = $this->findAttachedFiles($ttContentRow);
                } else {
                    $fileObjects = $this->findLinkedFilesInRte($ttContentRow);
                    $content .= $this->getContentFromContentElement($ttContentRow) . "\n";
                }

                // index the files found
                if (!$pageAccessRestrictions['hidden']) {
                    $this->indexFiles($fileObjects, $ttContentRow, $pageAccessRestrictions['fe_group'], $tags);
                }

                // add content from this content element to page content
                // ONLY if this content element is not access protected
                // or protected content elements should go into the index
                // by configuration.
                if ($this->indexerConfig['index_content_with_restrictions'] == 'yes'
                    || $ttContentRow['fe_group'] == ''
                    || $ttContentRow['fe_group'] == '0'
                ) {
                    $pageContent[$ttContentRow['sys_language_uid']] .= $content;
                }
            }
        } else {
            $this->counterWithoutContent++;
            return;
        }

        // make it possible to modify the indexerConfig via hook
        $additionalFields = array();
        $indexerConfig = $this->indexerConfig;

        // make it possible to modify the default values via hook
        $indexEntryDefaultValues = array(
            'type' => 'page',
            'uid' => $uid,
            'params' => '',
            'feGroupsPages' => $pageAccessRestrictions['fe_group'],
            'debugOnly' => false
        );

        // hook for custom modifications of the indexed data, e. g. the tags
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPagesIndexEntry'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPagesIndexEntry'] as $_classRef) {
                $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                $_procObj->modifyPagesIndexEntry(
                    $uid,
                    $pageContent,
                    $tags,
                    $this->cachedPageRecords,
                    $additionalFields,
                    $indexerConfig,
                    $indexEntryDefaultValues,
                    $this
                );
            }
        }


        // store record in index table
        if (count($pageContent)) {
            foreach ($pageContent as $language_uid => $content) {
                
                if (!$pageAccessRestrictions['hidden'] && $this->checkIfpageShouldBeIndexed($uid, $language_uid)) {
                    // overwrite access restrictions with language overlay values
                    $accessRestrictionsLanguageOverlay = $pageAccessRestrictions;
                    $pageAccessRestrictions['fe_group'] = $indexEntryDefaultValues['feGroupsPages'];
                    if ($language_uid > 0) {
                        if ($this->cachedPageRecords[$language_uid][$uid]['fe_group']) {
                            $accessRestrictionsLanguageOverlay['fe_group'] =
                                $this->cachedPageRecords[$language_uid][$uid]['fe_group'];
                        }
                        if ($this->cachedPageRecords[$language_uid][$uid]['starttime']) {
                            $accessRestrictionsLanguageOverlay['starttime'] =
                                $this->cachedPageRecords[$language_uid][$uid]['starttime'];
                        }
                        if ($this->cachedPageRecords[$language_uid][$uid]['endtime']) {
                            $accessRestrictionsLanguageOverlay['endtime'] =
                                $this->cachedPageRecords[$language_uid][$uid]['endtime'];
                        }
                    }

                    // use new "tx_kesearch_abstract" field instead of "abstract" if set
                    $abstract = $this->cachedPageRecords[$language_uid][$uid]['tx_kesearch_abstract'] ?
                        $this->cachedPageRecords[$language_uid][$uid]['tx_kesearch_abstract'] :
                        $this->cachedPageRecords[$language_uid][$uid]['abstract'];

                    $this->pObj->storeInIndex(
                        $indexerConfig['storagepid'],                               // storage PID
                        $this->cachedPageRecords[$language_uid][$uid]['title'],     // page title
                        $indexEntryDefaultValues['type'],                           // content type
                        $indexEntryDefaultValues['uid'],                            // target PID / single view
                        $content,                        // indexed content, includes the title (linebreak after title)
                        $tags,                                                      // tags
                        $indexEntryDefaultValues['params'],                         // typolink params for singleview
                        $abstract,                                                  // abstract
                        $language_uid,                                              // language uid
                        $accessRestrictionsLanguageOverlay['starttime'],            // starttime
                        $accessRestrictionsLanguageOverlay['endtime'],              // endtime
                        $accessRestrictionsLanguageOverlay['fe_group'],             // fe_group
                        $indexEntryDefaultValues['debugOnly'],                      // debug only?
                        $additionalFields                                           // additional fields added by hooks
                    );
                    $this->counter++;
                }
            }
        }

        return;
    }

    /**
     * checks wether the given page should go to the index.
     * Checks the doktype and wethe the "hidden" or "no_index" flags
     * are set.
     *
     * @param integer $uid
     * @param integer $language_uid
     * @return boolean
     */
    public function checkIfpageShouldBeIndexed($uid, $language_uid)
    {
        $index = true;

        if ($this->cachedPageRecords[$language_uid][$uid]['hidden']) {
            $index = false;
        }

        if ($this->cachedPageRecords[$language_uid][$uid]['no_search']) {
            $index = false;
        }

        if (!in_array($this->cachedPageRecords[$language_uid][$uid]['doktype'], $this->indexDokTypes)) {
            $index = false;
        }

        return $index;
    }

    /**
     * combine group access restrictons from page(s) and content element
     * @param string $feGroupsPages comma list
     * @param string $feGroupsContentElement comma list
     * @return type
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 26.09.13
     */
    public function getCombinedFeGroupsForContentElement($feGroupsPages, $feGroupsContentElement)
    {
        // combine frontend groups from page(s) and content elemenet as follows
        // 1. if page has no groups, but ce has groups, use ce groups
        // 2. if ce has no groups, but page has grooups, use page groups
        // 3. if page has "show at any login" (-2) and ce has groups, use ce groups
        // 4. if ce has "show at any login" (-2) and page has groups, use page groups
        // 5. if page and ce have explicit groups (not "hide at login" (-1), merge them (use only groups both have)
        // 6. if page or ce has "hide at login" and the other
        // has an expclicit group the element will never be shown and we must not index it.
        // So which group do we set here? Let's use a constant for that and check in the calling function for that.

        if (!$feGroupsPages && $feGroupsContentElement) {
            $feGroups = $feGroupsContentElement;
        }

        if ($feGroupsPages && !$feGroupsContentElement) {
            $feGroups = $feGroupsPages;
        }

        if ($feGroupsPages == '-2' && $feGroupsContentElement) {
            $feGroups = $feGroupsContentElement;
        }

        if ($feGroupsPages && $feGroupsContentElement == '-2') {
            $feGroups = $feGroupsPages;
        }

        if ($feGroupsPages && $feGroupsContentElement && $feGroupsPages != '-1' && $feGroupsContentElement != '-1') {
            $feGroupsContentElementArray = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(
                ',',
                $feGroupsContentElement
            );
            $feGroupsPagesArray = GeneralUtility::intExplode(',', $feGroupsPages);
            $feGroups = implode(',', array_intersect($feGroupsContentElementArray, $feGroupsPagesArray));
        }

        if (($feGroupsContentElement
                && $feGroupsContentElement != '-1'
                && $feGroupsContentElement != -2
                && $feGroupsPages == '-1')
            ||
            ($feGroupsPages && $feGroupsPages != '-1' && $feGroupsPages != -2 && $feGroupsContentElement == '-1')
        ) {
            $feGroups = DONOTINDEX;
        }

        return $feGroups;
    }

    /**
     * Extracts content from files given (as array of file objects or file reference objects)
     * and writes the content to the index
     * @param array $fileObjects
     * @param array $ttContentRow
     * @param string $feGroupsPages comma list
     * @param string $tags string
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 25.09.13
     */
    public function indexFiles($fileObjects, $ttContentRow, $feGroupsPages, $tags)
    {
        // combine group access restrictons from page(s) and content element
        $feGroups = $this->getCombinedFeGroupsForContentElement($feGroupsPages, $ttContentRow['fe_group']);

        if (count($fileObjects) && $feGroups != DONOTINDEX) {
            // loop through files
            foreach ($fileObjects as $fileObject) {
                if ($fileObject instanceof FileInterface) {
                    $isInList = \TYPO3\CMS\Core\Utility\GeneralUtility::inList(
                        $this->indexerConfig['fileext'],
                        $fileObject->getExtension()
                    );
                } else {
                    $this->addError('Could not index file in content element #' . $ttContentRow['uid'] . ' (no file object).');
                }

                // check if the file extension fits in the list of extensions
                // to index defined in the indexer configuration
                if ($isInList) {
                    // get file path and URI
                    $filePath = $fileObject->getForLocalProcessing(false);

                    /* @var $fileIndexerObject tx_kesearch_indexer_types_file */
                    $fileIndexerObject = GeneralUtility::makeInstance(
                        'tx_kesearch_indexer_types_file',
                        $this->pObj
                    );

                    // add tags from linking page to this index record?
                    if (!$this->indexerConfig['index_use_page_tags_for_files']) {
                        $tags = '';
                    }

                    // add tag to identify this index record as file
                    tx_kesearch_helper::makeTags($tags, array('file'));

                    // get file information and  file content (using external tools)
                    // write file data to the index as a seperate index entry
                    // count indexed files, add it to the indexer output
                    if (!file_exists($filePath)) {
                        $this->addError('Could not index file ' . $filePath . ' in content element #' . $ttContentRow['uid'] . ' (file does not exist).');
                    } else {
                        if ($fileIndexerObject->fileInfo->setFile($fileObject)) {
                            if (($content = $fileIndexerObject->getFileContent($filePath))) {
                                $this->storeFileContentToIndex(
                                    $fileObject,
                                    $content,
                                    $fileIndexerObject,
                                    $feGroups,
                                    $tags,
                                    $ttContentRow
                                );
                                $this->fileCounter++;
                            } else {
                                $this->addError($fileIndexerObject->getErrors());
                                $this->addError('Could not index file ' . $filePath . '.');
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Finds files attached to "uploads" content elements
     * returns them as file reference objects array
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 24.09.13
     * @param array $ttContentRow content element
     * @return array
     */
    public function findAttachedFiles($ttContentRow)
    {
        // Set current data
        $this->cObj->data = $ttContentRow;

        // Get files by filesProcessor
        $processedData = [];
        $processedData = $this->filesProcessor->process($this->cObj, [], $this->filesProcessorConfiguration, $processedData);
        $fileReferenceObjects = $processedData['files'];

        return $fileReferenceObjects;
    }


    /**
     * Finds files linked in rte text
     * returns them as array of file objects
     * @param array $ttContentRow content element
     * @return array
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 24.09.13
     */
    public function findLinkedFilesInRte($ttContentRow)
    {
        $fileObjects = array();
        // check if there are links to files in the rte text
        /* @var $rteHtmlParser \TYPO3\CMS\Core\Html\RteHtmlParser */
        $rteHtmlParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\RteHtmlParser');

        if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) <
            \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('8.0')
        ) {
            // TYPO3 7.6
            $blockSplit = $rteHtmlParser->splitIntoBlock('link', $ttContentRow['bodytext'], 1);
            foreach ($blockSplit as $k => $v) {
                if ($k % 2) {
                    $tagCode = GeneralUtility::unQuoteFilenames(
                        trim(substr($rteHtmlParser->getFirstTag($v), 0, -1)),
                        true
                    );
                    $link_param = $tagCode[1];

                    // Check for FAL link-handler keyword
                    list($linkHandlerKeyword, $linkHandlerValue) = explode(':', trim($link_param), 2);
                    if ($linkHandlerKeyword === 'file') {
                        try {
                            $fileOrFolderObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject(
                                rawurldecode($linkHandlerValue)
                            );
                            if ($fileOrFolderObject instanceof FileInterface) {
                                $fileObjects[] = $fileOrFolderObject;
                            }
                        } catch (ResourceDoesNotExistException $resourceDoesNotExistException) {
                            $this->addError(
                                'Could not index file with FAL uid #'
                                . $linkHandlerValue
                                . ' (the indentifier inserted in the RTE is already gone).'
                            );
                        }
                    }
                }
            }
        } else {
            // TYPO3 8.7
            /** @var \TYPO3\CMS\Core\LinkHandling\LinkService $linkService */
            $linkService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\LinkHandling\LinkService::class);
            $blockSplit = $rteHtmlParser->splitIntoBlock('A', $ttContentRow['bodytext'], 1);
            foreach ($blockSplit as $k => $v) {
                list($attributes) = $rteHtmlParser->get_tag_attributes($rteHtmlParser->getFirstTag($v), true);
                if (!empty($attributes['href'])) {
                    $hrefInformation = $linkService->resolve($attributes['href']);
                    if ($hrefInformation['type'] === \TYPO3\CMS\Core\LinkHandling\LinkService::TYPE_FILE) {
                        $fileObjects[] = $hrefInformation['file'];
                    }
                }
            }
        }
        return $fileObjects;
    }


    /**
     * Store the file content and additional information to the index
     * @param $fileObject file reference object or file object
     * @param string $content file text content
     * @param tx_kesearch_indexer_types_file $fileIndexerObject
     * @param string $feGroups comma list of groups to assign
     * @param array $ttContentRow tt_content element the file was assigned to
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 25.09.13
     */
    public function storeFileContentToIndex($fileObject, $content, $fileIndexerObject, $feGroups, $tags, $ttContentRow)
    {
        // get metadata
        if ($fileObject instanceof TYPO3\CMS\Core\Resource\FileReference) {
            $orig_uid = $fileObject->getOriginalFile()->getUid();
            $metadata = $fileObject->getOriginalFile()->_getMetaData();
        } else {
            $orig_uid = $fileObject->getUid();
            $metadata = $fileObject->_getMetaData();
        }

        if ($metadata['fe_groups']) {
            if ($feGroups) {
                $feGroupsContentArray = GeneralUtility::intExplode(',', $feGroups);
                $feGroupsFileArray = GeneralUtility::intExplode(',', $metadata['fe_groups']);
                $feGroups = implode(',', array_intersect($feGroupsContentArray, $feGroupsFileArray));
            } else {
                $feGroups = $metadata['fe_groups'];
            }
        }

        // assign categories as tags (as cleartext, eg. "colorblue")
        $categories = tx_kesearch_helper::getCategories($metadata['uid'], 'sys_file_metadata');
        tx_kesearch_helper::makeTags($tags, $categories['title_list']);

        // assign categories as generic tags (eg. "syscat123")
        tx_kesearch_helper::makeSystemCategoryTags($tags, $metadata['uid'], 'sys_file_metadata');

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

        $title = $fileIndexerObject->fileInfo->getName();
        $storagePid = $this->indexerConfig['storagepid'];
        $type = 'file:' . $fileObject->getExtension();

        $additionalFields = array(
            'sortdate' => $fileIndexerObject->fileInfo->getModificationTime(),
            'orig_uid' => $orig_uid,
            'orig_pid' => 0,
            'directory' => $fileIndexerObject->fileInfo->getRelativePath(),
            'hash' => $fileIndexerObject->getUniqueHashForFile()
        );

        //hook for custom modifications of the indexed data, e. g. the tags
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFileIndexEntryFromContentIndexer'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFileIndexEntryFromContentIndexer'] as
                     $_classRef) {
                $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                $_procObj->modifyFileIndexEntryFromContentIndexer(
                    $fileObject,
                    $content,
                    $fileIndexerObject,
                    $feGroups,
                    $ttContentRow,
                    $storagePid,
                    $title,
                    $tags,
                    $abstract,
                    $additionalFields
                );
            }
        }

        // Store record in index table:
        // Add usergroup restrictions of the page and the
        // content element to the index data.
        // Add time restrictions to the index data.
        $this->pObj->storeInIndex(
            $storagePid,                             // storage PID
            $title,                                  // file name
            $type,                                   // content type
            $ttContentRow['pid'],                    // target PID: where is the single view?
            $content,                                // indexed content
            $tags,                                   // tags
            '',                                      // typolink params for singleview
            $abstract,                               // abstract
            $ttContentRow['sys_language_uid'],       // language uid
            $ttContentRow['starttime'],              // starttime
            $ttContentRow['endtime'],                // endtime
            $feGroups,                               // fe_group
            false,                                   // debug only?
            $additionalFields                        // additional fields added by hooks
        );
    }

    /**
     * Extracts content from content element and returns it as plain text
     * for writing it directly to the index
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 24.09.13
     * @param array $ttContentRow content element
     * @return string
     */
    public function getContentFromContentElement($ttContentRow)
    {
        // bodytext
        $bodytext = $ttContentRow['bodytext'];

        // following lines prevents having words one after the other like: HelloAllTogether
        $bodytext = str_replace('<td', ' <td', $bodytext);
        $bodytext = str_replace('<br', ' <br', $bodytext);
        $bodytext = str_replace('<p', ' <p', $bodytext);
        $bodytext = str_replace('<li', ' <li', $bodytext);

        if ($ttContentRow['CType'] == 'table') {
            // replace table dividers with whitespace
            $bodytext = str_replace('|', ' ', $bodytext);
        }
        $bodytext = strip_tags($bodytext);

        // hook for modifiying a content elements content
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentFromContentElement'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentFromContentElement'] as
                     $_classRef) {
                $_procObj = &\TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                $_procObj->modifyContentFromContentElement(
                    $bodytext,
                    $ttContentRow,
                    $this
                );
            }
        }

        return $bodytext;
    }
}
