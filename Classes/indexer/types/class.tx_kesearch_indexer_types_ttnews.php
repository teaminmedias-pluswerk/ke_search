<?php

/* * *************************************************************
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
 * ************************************************************* */

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 *
 * @author	Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 * @author	Stefan Froemken
 * @author	Christian Bülter (kennziffer.com) <buelter@kennziffer.com>
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_indexer_types_ttnews extends tx_kesearch_indexer_types {

	/**
	 * Initializes indexer for tt_news
	 */
	public function __construct($pObj) {
		parent::__construct($pObj);
	}

	/**
	 * converts the datetime of a record into variables you can use in realurl
	 *
	 * @param	integer the timestamp to convert into a HR date
	 * @return array
	 */
	function getParamsForHrDateSingleView($tstamp) {

		if ($this->conf['useHRDatesSingle']) {
			$params = array('tx_ttnews' => array(
				'year' => date('Y', $tstamp),
				'month' => date('m', $tstamp),
			));
			if (!$this->conf['useHRDatesSingleWithoutDay']) {
				$params['tx_ttnews']['day'] = date('d', $tstamp);
			}
		} else {
			return array();
		}
		return $params;
	}

	/**
	 * This function was called from indexer object and saves content to index table
	 *
	 * @return string content which will be displayed in backend
	 */
	public function startIndexing() {
		$content = '';

		$this->conf['useHRDatesSingle'] = $this->indexerConfig['index_news_useHRDatesSingle'];
		$this->conf['useHRDatesSingleWithoutDay'] = $this->indexerConfig['index_news_useHRDatesSingleWithoutDay'];

		// get all the tt_news entries to index
		// don't index hidden or deleted news, BUT
		// get the news with frontend user group access restrictions
		// or time (start / stop) restrictions.
		// Copy those restrictions to the index.
		$fields = '*';
		$table = 'tt_news';
		$indexPids = $this->getPidList($this->indexerConfig['startingpoints_recursive'], $this->indexerConfig['sysfolder'], $table);
		if ($this->indexerConfig['index_use_page_tags']) {
			// add the tags of each page to the global page array
			$this->pageRecords = $this->getPageRecords($indexPids);
			$this->addTagsToRecords($indexPids);
		}

		$where = 'pid IN (' . implode(',', $indexPids) . ') ';
		$where .= TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table);
		$where .= TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		$counter = 0;

		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
			while (($newsRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {

				// if mode equals 'choose categories for indexing' (2). 1 = All
				if ($this->indexerConfig['index_news_category_mode'] == '2') {

					$enableFields = TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tt_news_cat') . TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tt_news_cat');
					$resCat = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
						'tt_news_cat.uid', 'tt_news', 'tt_news_cat_mm', 'tt_news_cat', ' AND tt_news.uid = ' . $newsRecord['uid'] .
						$enableFields,
						'', '', ''
					);

					if ($GLOBALS['TYPO3_DB']->sql_num_rows($resCat)) {
						$isInList = false;
						while ($newsCat = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resCat)) {

							// if category was found in list, set isInList to true and break further processing.
							if (TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->indexerConfig['index_news_category_selection'], $newsCat['uid'])) {
								$isInList = true;
								break;
							}
						}

						// if category was not found stop further processing and loop with next news record
						if (!$isInList) {
							continue;
						}
					}
				}

				// compile the information which should go into the index
				$type     = 'tt_news';
				$title    = strip_tags($newsRecord['title']);
				$abstract = strip_tags($newsRecord['short']);
				$content  = strip_tags($newsRecord['bodytext']);

				// add keywords to content if not empty
				if (!empty($newsRecord['keywords'])) {
					$content .= "\n" . $newsRecord['keywords'];
				}

				// create content
				$fullContent = $abstract . "\n" . $content;

				// create params and custom single view page:
				// if it is a default news (type = 0), add params
				// if it is an internal page (type = 1), put that into the "targetpid" field
				// if it is an external url (type = 2), put that into the "params" field
				if ($newsRecord['type'] == 1) {
					$singleViewPage = $newsRecord['page'];
					$params = '';
				} else if ($newsRecord['type'] == 2) {
					$type = 'external:tt_news';
					$singleViewPage = '';
					$params = $newsRecord['ext_url'];;
				} else {
					// get target page from category if set (first assigned category)
					$ttnewsIsLoaded = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tt_news');
					if ($ttnewsIsLoaded) {
						$singleViewPage = $this->getSingleViewPageFromCategories($newsRecord['uid']);
					}
					$paramsSingleView = $this->getParamsForHrDateSingleView($newsRecord['datetime']);
					$paramsSingleView['tx_ttnews']['tt_news'] = $newsRecord['uid'];
					$params = '&' . http_build_query($paramsSingleView, NULL, '&');
					$params = rawurldecode($params);
				}

				// create tags
				if ($this->indexerConfig['index_use_page_tags']) {
					$tags = $this->pageRecords[intval($newsRecord['pid'])]['tags'];
				} else {
					$tags = '';
				}

				// add additional fields
				$additionalFields = array();

				// crdate is always given, but can be overwritten
				$additionalFields['sortdate'] = $newsRecord['crdate'];

				// last changed date
				if (isset($newsRecord['datetime']) && $newsRecord['datetime'] > 0) {
					$additionalFields['sortdate'] = $newsRecord['datetime'];
				}

				// fill orig_uid and orig_pid
				$additionalFields['orig_uid'] = $newsRecord['uid'];
				$additionalFields['orig_pid'] = $newsRecord['pid'];

				// make it possible to modify the indexerConfig via hook
				$indexerConfig = $this->indexerConfig;

				// overwrite default targetpid value from indexerconfig
				// only if $singleViewPage is set
				if ($singleViewPage) {
					$indexerConfig['targetpid'] = $singleViewPage;
				}

				// hook for custom modifications of the indexed data, e. g. the tags
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyNewsIndexEntry'])) {
					foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyNewsIndexEntry'] as $_classRef) {
						$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
						$_procObj->modifyNewsIndexEntry(
							$title, $abstract, $fullContent, $params, $tags, $newsRecord, $additionalFields, $indexerConfig
						);
					}
				}

				// ... and store them
				$this->pObj->storeInIndex(
					$indexerConfig['storagepid'],    // storage PID
					$title,                          // news title
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
				$counter++;
			}

			$content = '<p><b>Indexer "' . $this->indexerConfig['title'] . '":</b><br />' . "\n"
				. $counter . ' news have been indexed.</p>' . "\n";

			$content .= $this->showErrors();
			$content .= $this->showTime();
		}

		return $content;
	}

	/**
	 * gets categories and subcategories for a news record
	 * code based on function in tx_ttnews
	 *
	 * @param	integer		$uid : uid of the current news record
	 * @param	bool		$getAll: ...
	 * @return	array		$categories: array of found categories
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 04.12.13
	 */
	function getSingleViewPageFromCategories($uid, $getAll = false) {

		// TODO: make sorting via typoscript available (but that would need a
		// fully instanciated frontend)
		$mmCatOrderBy = 'mmsorting';

		// TODO: take Storage PID into account (in tx_ttnws class the
		// where clause is stored in $this->SPaddWhere)
		$addWhere = ' AND tt_news_cat.deleted=0';
		$addWhere .= $getAll ? '' : TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tt_news_cat');

		// TODO: take tt_news configuration options useSPidFromCategory and
		// and useSPidFromCategoryRecusive into account (would need an instance
		// of the frontend). For now, we assume them set to TRUE
		$conf['useSPidFromCategory'] = TRUE;
		$conf['useSPidFromCategoryRecusive'] = TRUE;

		$select_fields = 'tt_news_cat.*,tt_news_cat_mm.sorting AS mmsorting';
		$from_table = 'tt_news_cat_mm, tt_news_cat ';
		$where_clause = 'tt_news_cat_mm.uid_local=' . intval($uid) . ' AND tt_news_cat_mm.uid_foreign=tt_news_cat.uid';
		$where_clause .= $addWhere;

		$groupBy = '';
		$orderBy = $mmCatOrderBy;
		$limit = '';

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($select_fields, $from_table, $where_clause, $groupBy, $orderBy, $limit);

		$singlePid = 0;
		while (($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {
			if (!$singlePid) {
				$singlePid = $this->getRecursiveCategorySinglePid($row['uid']);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $singlePid;
	}

	/**
	 * Searches the category rootline (up) for a single view pid. If nothing is found in the current
	 * category, the single view pid of the parent categories is taken (recusivly).
	 * taken from tx_ttnews
	 *
	 * @param int $currentCategory: Uid of the current category
	 * @return int first found single view pid
	 */
	function getRecursiveCategorySinglePid($currentCategory) {
		$addWhere = ' AND tt_news_cat.deleted=0' . TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tt_news_cat');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,parent_category,single_pid', 'tt_news_cat', 'tt_news_cat.uid=' . $currentCategory . $addWhere);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		if ($row['single_pid'] > 0) {
			return $row['single_pid'];
		} elseif ($row['parent_category'] > 0) {
			return $this->getRecursiveCategorySinglePid($row['parent_category']);
		}
	}

}