<?php
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 * @author    Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 * @author    Stefan Froemken
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class tx_kesearch_indexer_types_tt_content extends tx_kesearch_indexer_types_page
{
    public $indexedElementsName = 'content elements';

    private $table = 'tt_content';

    /**
     * get content of current page and save data to db
     * @param $uid page-UID that has to be indexed
     */
    public function getPageContent($uid)
    {
        $uid = (int)$uid;

        if (strpos($this->whereClauseForCType, '"shortcut"')) {
            // get shortcut elements and normal elements on this page
            $shortCutIds = $this->loadShortcutContent($uid);
            $rows = $this->fetchContentFromPage($uid, $this->whereClauseForCType, $shortCutIds);
        }
        else {
            // get content elements for this page
            $rows = $this->fetchContentFromPage($uid, $this->whereClauseForCType);
        }

        // Get access restrictions for this page
        $pageAccessRestrictions = $this->getInheritedAccessRestrictions($uid);

        if ($rows) {
            foreach ($rows as $row) {
                // skip this content element if the page itself is hidden or a
                // parent page with "extendToSubpages" set is hidden
                if ($pageAccessRestrictions['hidden']) {
                    continue;
                }
                if ($row['sys_language_uid'] > 0
                    && $this->cachedPageRecords[$row['sys_language_uid']][$row['pid']]['hidden']) {
                    continue;
                }

                // shortcut items are included already, the shortcut itself must not be indexed
                if ($row['CType'] === 'shortcut') {
                    continue;
                }

                // combine group access restrictons from page(s) and content element
                $feGroups = $this->getCombinedFeGroupsForContentElement(
                    $pageAccessRestrictions['fe_group'],
                    $row['fe_group']
                );

                // skip this content element if either the page or the content
                // element is set to "hide at login"
                // and the other one has a frontend group attached to it
                if ($feGroups == DONOTINDEX) {
                    continue;
                }

                // get content for this content element
                $content = '';

                // get tags from page
                $tags = $this->pageRecords[$uid]['tags'];

                // assign categories as tags (as cleartext, eg. "colorblue")
                $categories = tx_kesearch_helper::getCategories($row['uid'], $this->table);
                tx_kesearch_helper::makeTags($tags, $categories['title_list']);

                // assign categories as generic tags (eg. "syscat123")
                tx_kesearch_helper::makeSystemCategoryTags($tags, $row['uid'], $this->table);

                // index header
                // add header only if not set to "hidden"
                if ($row['header_layout'] != 100) {
                    $content .= strip_tags($row['header']) . "\n";
                }

                // index content of this content element and find attached or linked files.
                // Attached files are saved as file references, the RTE links directly to
                // a file, thus we get file objects.
                if (in_array($row['CType'], $this->fileCTypes)) {
                    $fileObjects = $this->findAttachedFiles($row);
                } else {
                    $fileObjects = $this->findLinkedFilesInRte($row);
                    $content .= $this->getContentFromContentElement($row) . "\n";
                }

                // index the files fond
                $this->indexFiles($fileObjects, $row, $pageAccessRestrictions['fe_group]'], $tags);

                // Combine starttime and endtime from page, page language overlay
                // and content element.
                // TODO:
                // If current content element is a localized content
                // element, fetch startdate and enddate from original conent
                // element as the localized content element cannot have it's
                // own start- end enddate
                $starttime = $pageAccessRestrictions['starttime'];

                if ($this->cachedPageRecords[$row['sys_language_uid']][$row['pid']]['starttime'] > $starttime) {
                    $starttime = $this->cachedPageRecords[$row['sys_language_uid']][$row['pid']]['starttime'];
                }

                if ($row['starttime'] > $starttime) {
                    $starttime = $row['starttime'];
                }

                $endtime = $pageAccessRestrictions['endtime'];

                if ($endtime == 0 || ($this->cachedPageRecords[$row['sys_language_uid']][$row['pid']]['endtime']
                        && $this->cachedPageRecords[$row['sys_language_uid']][$row['pid']]['endtime'] < $endtime)) {
                    $endtime = $this->cachedPageRecords[$row['sys_language_uid']][$row['pid']]['endtime'];
                }

                if ($endtime == 0 || ($row['endtime'] && $row['endtime'] < $endtime)) {
                    $endtime = $row['endtime'];
                }

                // prepare additionalFields (to be added via hook)
                $additionalFields = array();

                // make it possible to modify the indexerConfig via hook
                $indexerConfig = $this->indexerConfig;

                // hook for custom modifications of the indexed data, e. g. the tags
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentIndexEntry'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentIndexEntry'] as
                             $_classRef) {
                        $_procObj = &TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                        $_procObj->modifyContentIndexEntry(
                            $row['header'],
                            $row,
                            $tags,
                            $row['uid'],
                            $additionalFields,
                            $indexerConfig
                        );
                    }
                }

                // compile title from page title and content element title
                // TODO: make changeable via hook
                $title = $this->cachedPageRecords[$row['sys_language_uid']][$row['pid']]['title'];
                if ($row['header'] && $row['header_layout'] != 100) {
                    $title = $title . ' - ' . $row['header'];
                }

                // save record to index
                $this->pObj->storeInIndex(
                    $indexerConfig['storagepid'],        // storage PID
                    $title,                              // page title inkl. tt_content-title
                    'content',                           // content type
                    $uid . '#c' . $row['uid'],    // target PID: where is the single view?
                    $content,                            // indexed content, includes the title (linebreak after title)
                    $tags,                               // tags
                    '',                                  // typolink params for singleview
                    '',                                  // abstract
                    $row['sys_language_uid'],            // language uid
                    $starttime,                          // starttime
                    $endtime,                            // endtime
                    $feGroups,                           // fe_group
                    false,                               // debug only?
                    $additionalFields                    // additional fields added by hooks
                );

                // count elements written to the index
                $this->counter++;
            }
        } else {
            return;
        }

        return;
    }

    private function addContentWhereConditions()
    {
        $where = '';
        // add condition for not indexing gridelement columns with colPos = -2 (= invalid)
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('gridelements')) {
            $where .= ' AND colPos <> -2 ';
        }

        // don't index elements which are hidden or deleted, but do index
        // those with time restrictions, the time restrictions will be
        // copied to the index
        $where .= ' AND hidden=0';
        $where .= TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($this->table);

        return $where;
    }

    private function fetchContentFromPage(int $uid, string $whereClauseForCType, string $uidList = '')
    {
        $fields = '*';
        if ($uidList === '') {
            $where = 'pid = ' . $uid;
        }
        else {
            $where = '(pid = ' . $uid . ' or uid in (' . $uidList . '))';
        }

        $where .= ' AND (' . $whereClauseForCType . ')';
        $where .= $this->addContentWhereConditions();
        $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $this->table, $where);
        return $rows;
    }

    private function fetchContent(int $uid)
    {
        $fields = '*';
        $where = 'uid = ' . $uid;
        $where .= ' AND (' . $this->whereClauseForCType . ')';
        $where .= $this->addContentWhereConditions();
        return $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow($fields, $this->table, $where);
    }

    private function fetchContentTranslations(int $uid)
    {
        $fields = 'uid, CType';
        $where = 'l18n_parent = ' . $uid;
        $where .= ' AND (' . $this->whereClauseForCType . ')';
        $where .= $this->addContentWhereConditions();
        return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $this->table, $where);
    }

    private function recursiveShortcut(array $row)
    {
        $records = explode(',', $row['records']);
        $idList = [];
        foreach ($records as $record) {
            list($table, $uid) = GeneralUtility::revExplode('_', $record, 2);

            if ($table !== 'tt_content' || !$uid) {
                continue;
            }

            $content = $this->fetchContent($uid);
            if (!$content) {
                continue;
            }

            $translations = $this->fetchContentTranslations($uid);
            foreach ($translations as $translation) {
                if ($translation['CType'] === 'shortcut') {
                    $idList = array_merge($idList, $this->recursiveShortcut($translation));
                }
                else {
                    $idList[] = (int)$translation['uid'];
                }
            }

            if ($content['CType'] === 'shortcut') {
                $idList = array_merge($idList, $this->recursiveShortcut($content));
            }
            else {
                $idList[] = (int)$uid;
            }
        }
        return $idList;
    }

    private function loadShortcutContent(int $uid)
    {
        $rows = $this->fetchContentFromPage($uid, 'CType="shortcut"');
        $idList = [];
        if ($rows) {
            foreach ($rows as $row) {
                $idList = array_merge($idList,$this->recursiveShortcut($row));
            }
        }

        return implode(',', array_unique($idList));
    }
}
