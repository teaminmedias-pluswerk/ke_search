<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Andreas Kiefer (inmedias.de) <andreas.kiefer@inmedias.de>
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
 * @author	Christian Bülter (kennziffer.com) <buelter@kennziffer.com>
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_indexer_types_cal extends tx_kesearch_indexer_types {

	/**
	 * Initializes indexer for tt_news
	 */
	public function __construct($pObj) {
		parent::__construct($pObj);
	}

	/**
	 * This function was called from indexer object and saves content to index table
	 *
	 * @return string content which will be displayed in backend
	 */
	public function startIndexing() {
		$content = '';
		$table = 'tx_cal_event';

		// get the pages from where to index the news
		$indexPids = $this->getPidList($this->indexerConfig['startingpoints_recursive'], $this->indexerConfig['sysfolder'], $table);

		// add the tags of the parent page
		if($this->indexerConfig['index_use_page_tags']) {
			$this->pageRecords = $this->getPageRecords($indexPids);
			$this->addTagsToRecords($indexPids);
		}

		// get all the glossary records to index, don't index hidden or
		// deleted glossary records, BUT  get the records with frontend user group
		// access restrictions or time (start / stop) restrictions.
		// Copy those restrictions to the index.
		$fields = '*';
		$where = 'pid IN (' . implode(',', $indexPids) . ') ';

		// index expired events?
		if (!$this->indexerConfig['cal_expired_events']) {
			$where .= ' AND ((UNIX_TIMESTAMP(start_date) > UNIX_TIMESTAMP()) OR (UNIX_TIMESTAMP(end_date) > UNIX_TIMESTAMP())) ';
		}

		// add enablefields
		$where .= TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table);
		$where .= TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);

		$indexedRecordsCounter = 0;
		$resCount = $GLOBALS['TYPO3_DB']->sql_num_rows($res, 'res count: ');

		if ($resCount) {
			while (($record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {

				// compile the information which should go into the index:
				// title, description

				$title = strip_tags($record['title']);
				$abstract = '';
				$fullContent = strip_tags($record['description']);

				// compile params for single view, example:
				// index.php?id=4749&tx_cal_controller[view]=event&tx_cal_controller[type]=tx_cal_phpicalendar&tx_cal_controller[uid]=3&tx_cal_controller[year]=2016&tx_cal_controller[month]=3&tx_cal_controller%5Bday%5D=13&cHash=02c5c65558b8f44e16bee0c6703132bf
				$paramsSingleView = array();
				$paramsSingleView['tx_cal_controller']['uid'] = $record['uid'];
				$params = rawurldecode('&' . http_build_query($paramsSingleView, NULL, '&'));

				// add tags from pages
				if ($this->indexerConfig['index_use_page_tags']) {
					$tags = $this->pageRecords[intval($record['pid'])]['tags'];
				} else {
					$tags = '';
				}

				// get category tags
				if ($record['category_id']) {
					$this->buildCategoryTags($record['uid'], $tags);
				}

				// make it possible to modify the indexerConfig via hook
				$indexerConfig = $this->indexerConfig;

				// set additional fields
				$additionalFields = array();
				$additionalFields['orig_uid'] = $record['uid'];
				$additionalFields['orig_pid'] = $record['pid'];
				// set event start date as sortdate
				$additionalFields['sortdate'] = strtotime($record['start_date']) + $record['start_time'];

				// hook for custom modifications of the indexed data, e.g. the tags
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyCalIndexEntry'])) {
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyCalIndexEntry'] as $_classRef) {
						$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
						$_procObj->modifyCalIndexEntry(
							$title,
							$abstract,
							$fullContent,
							$params,
							$tags,
							$record,
							$additionalFields,
							$indexerConfig,
							$this
						);
					}
				}

				// store this record to the index
				$this->pObj->storeInIndex(
					$indexerConfig['storagepid'],	// storage PID
					$title,                         // page title
					'cal',                 			// content type
					$indexerConfig['targetpid'],    // target PID: where is the single view?
					$fullContent,                   // indexed content, includes the title (linebreak after title)
					$tags,                          // tags
					$params,                        // typolink params for singleview
					$abstract,                      // abstract
					$record['sys_language_uid'],	// language uid
					$record['starttime'],       	// starttime
					$record['endtime'],         	// endtime
					$record['fe_group'],        	// fe_group
					false,                          // debug only?
					$additionalFields               // additional fields added by hooks
				);
				$indexedRecordsCounter++;
			}

			$content = '<p><b>Indexer "' . $this->indexerConfig['title'] . '":</b><br />' . "\n"
					. $indexedRecordsCounter . ' "Calendar Base" records have been indexed.</p>' . "\n";

			$content .= $this->showErrors();
			$content .= $this->showTime();
		}

		return $content;
	}

	/**
	 * @param $eventUid
	 * @param $tags
	 */
	private function buildCategoryTags($eventUid, &$tags) {

		$table = 'tx_cal_event_category_mm, tx_cal_category';
		$fields = 'title';
		$where = 'tx_cal_category.uid = tx_cal_event_category_mm.uid_foreign';
		$where .= ' AND tx_cal_event_category_mm.uid_local = '. intval($eventUid);

		// add enablefields
		$where .= TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_cal_category');
		$where .= TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_cal_category');

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		$resCount = $GLOBALS['TYPO3_DB']->sql_num_rows($res, 'res count: ');

		if ($resCount) {
			while (($catRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
				// build tags for connected categories
				tx_kesearch_helper::makeTags($tags, array($catRecord['title']));
			}
		}

	}

}