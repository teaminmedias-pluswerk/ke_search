<?php
/***************************************************************
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
***************************************************************/

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 *
 * @author	Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 * @author	Stefan Froemken 
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_indexer_types_tt_content extends tx_kesearch_indexer_types_page {
	var $indexedElementsName = 'content elements';

	/**
	 * get content of current page and save data to db
	 * @param $uid page-UID that has to be indexed
	 */
	function getPageContent($uid) {
		// get content elements for this page
		$fields = '*';
		$table = 'tt_content';
		$where = 'pid = ' . intval($uid);
		$where .= ' AND (' . $this->whereClauseForCType. ')';

		// don't index elements which are hidden or deleted, but do index
		// those with time restrictons, the time restrictens will be 
		// copied to the index
		//$where .= t3lib_BEfunc::BEenableFields($table);
		$where .= ' AND hidden=0';

		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$where .= TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);
		} else {
			$where .= t3lib_BEfunc::deleteClause($table);
		}

		// get tags from page
		$tags = $this->pageRecords[$uid]['tags'];

		// Get access restrictions for this page
		$pageAccessRestrictions = $this->getInheritedAccessRestrictions($uid);

		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, $where);
		if(count($rows)) {
			foreach($rows as $row) {

				// skip this content element if the page itself is hidden or a
				// parent page with "extendToSubpages" set is hidden
				if ($pageAccessRestrictions['hidden']) continue;
				if ($row['sys_language_uid'] > 0 && $this->cachedPageRecords[$row['sys_language_uid']][$row['pid']]['hidden']) continue;

				// combine group access restrictons from page(s) and content element
				$feGroups = $this->getCombinedFeGroupsForContentElement($pageAccessRestrictions['fe_group'], $row['fe_group']);

				// skip this content element if either the page or the content
				// element is set to "hide at login"
				// and the other one has a frontend group attached to it
				if ($feGroups == DONOTINDEX) continue;

				// get content for this content element
				$content = '';

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
				$this->indexFiles($fileObjects, $row, $pageAccessRestrictions['fe_group]'], $tags) . "\n";

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

				if ($endtime == 0 || ($this->cachedPageRecords[$row['sys_language_uid']][$row['pid']]['endtime'] && $this->cachedPageRecords[$row['sys_language_uid']][$row['pid']]['endtime'] < $endtime)) {
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
				if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentIndexEntry'])) {
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentIndexEntry'] as $_classRef) {
						if (TYPO3_VERSION_INTEGER >= 7000000) {
							$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
						} else {
							$_procObj = & t3lib_div::getUserObj($_classRef);
						}
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
					$indexerConfig['storagepid'],    	    // storage PID
					$title,                             	// page title inkl. tt_content-title
					'content',                        	    // content type
					$row['pid'] . '#c' . $row['uid'],      	// target PID: where is the single view?
					$content,                             	// indexed content, includes the title (linebreak after title)
					$tags,                                	// tags
					'',                                    	// typolink params for singleview
					'',                                   	// abstract
					$row['sys_language_uid'],              	// language uid
					$starttime,                     	    // starttime
					$endtime,                       	    // endtime
					$feGroups,                             	// fe_group
					false,                                 	// debug only?
					$additionalFields                      	// additional fields added by hooks
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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/indexer/types/class.tx_kesearch_indexer_types_tt_content.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/indexer/types/class.tx_kesearch_indexer_types_tt_content.php']);
}
?>