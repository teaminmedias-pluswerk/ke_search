<?php

namespace TeaminmediasPluswerk\KeSearch\Indexer\Types;

use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TeaminmediasPluswerk\KeSearch\Lib\Db;

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

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 * @author    Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 * @author    Stefan Froemken
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class TtContent extends Page
{
    public $indexedElementsName = 'content elements';

    /**
     * get content of current page and save data to db
     * @param $uid page-UID that has to be indexed
     */
    public function getPageContent($uid)
    {
        // get content elements for this page
        $fields = '*';
        $table = 'tt_content';
        $where = 'pid = ' . intval($uid);
        $where .= ' AND (' . $this->whereClauseForCType . ')';

        $table = 'tt_content';
        $queryBuilder = Db::getQueryBuilder($table);

        // don't index elements which are hidden or deleted, but do index
        // those with time restrictions, the time restrictions will be
        // copied to the index
        $queryBuilder->getRestrictions()
            ->removeByType(StartTimeRestriction::class)
            ->removeByType(EndTimeRestriction::class);

        // build array with where clauses
        $where = [];
        $where[] = $queryBuilder->expr()->eq(
            'pid',
            $queryBuilder->createNamedParameter(
                $uid,
                \PDO::PARAM_INT
            )
        );
        $where[] = $this->whereClauseForCType;

        // add condition for not indexing gridelement columns with colPos = -2 (= invalid)
        if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('gridelements')) {
            $where[] = $queryBuilder->expr()->neq(
                'colPos',
                $queryBuilder->createNamedParameter(
                    -2,
                    \PDO::PARAM_INT
                )
            );
        }

        // Get access restrictions for this page
        $pageAccessRestrictions = $this->getInheritedAccessRestrictions($uid);

        $rows = $queryBuilder
            ->select($fields)
            ->from($table)
            ->where(...$where)
            ->execute()
            ->fetchAll();


        if (count($rows)) {
            foreach ($rows as $row) {

                // skip this content element if the page itself is hidden or a
                // parent page with "extendToSubpages" set is hidden
                if ($pageAccessRestrictions['hidden']) {
                    continue;
                }

                // skip this content element if the page is hidden or set to "no_search"
                if (!$this->checkIfpageShouldBeIndexed($uid, $row['sys_language_uid'])) {
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

                $logMessage = 'Indexing tt_content record';
                $logMessage .= $row['header'] ? ' "' . $row['header'] .'"' : '';
                $this->pObj->logger->debug( $logMessage, [
                    'uid' => $row['uid'],
                    'pid' => $row['pid'],
                    'CType' => $row['CType']
                ]);

                // get content for this content element
                $content = '';

                // get tags from page
                $tags = $this->pageRecords[$uid]['tags'];

                // assign categories as tags (as cleartext, eg. "colorblue")
                $categories = SearchHelper::getCategories($row['uid'], $table);
                SearchHelper::makeTags($tags, $categories['title_list']);

                // assign categories as generic tags (eg. "syscat123")
                SearchHelper::makeSystemCategoryTags($tags, $row['uid'], $table);

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

                // index the files found
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
                        $_procObj = GeneralUtility::makeInstance($_classRef);
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
                    $row['pid'] . '#c' . $row['uid'],    // target PID: where is the single view?
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
}
