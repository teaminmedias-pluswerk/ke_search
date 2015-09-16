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
class tx_kesearch_indexer_types {
	var $startMicrotime = 0;
	var $indexerConfig = array(); // current indexer configuration

	/**
	 * @var tx_kesearch_indexer
	 */
	var $pObj;

	/**
	 * needed to get all recursive pids
	 *
	 * @var t3lib_queryGenerator
	 */
	var $queryGen;

	/**
	 *
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Constructor of this object
	 *
	 * @param $pObj
	 */
	public function __construct($pObj) {
		$this->startMicrotime = microtime(true);
		$this->pObj = $pObj;
		$this->indexerConfig = $this->pObj->indexerConfig;
		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$this->queryGen = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\QueryGenerator');
		} else if (TYPO3_VERSION_INTEGER >= 6002000) {
			$this->queryGen = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_queryGenerator');
		} else {
			$this->queryGen = t3lib_div::makeInstance('t3lib_queryGenerator');
		}
	}


	/**
	 * get all recursive contained pids of given Page-UID
	 * regardless if we need them or if they are sysfolders, links or what ever
	 *
	 * @param string $startingPointsRecursive comma-separated list of pids of recursive start-points
	 * @param string $singlePages comma-separated list of pids of single pages
	 * @return array List of page UIDs
	 */
	public function getPagelist($startingPointsRecursive = '', $singlePages = '') {
		// make array from list

		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$pidsRecursive = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $startingPointsRecursive, true);
			$pidsNonRecursive = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $singlePages, true);
		} else {
			$pidsRecursive = t3lib_div::trimExplode(',', $startingPointsRecursive, true);
			$pidsNonRecursive = t3lib_div::trimExplode(',', $singlePages, true);
		}

		// add recursive pids
		foreach($pidsRecursive as $pid) {
			$pageList .= $this->queryGen->getTreeList($pid, 99, 0, '1=1') . ',';
		}

		// add non-recursive pids
		foreach($pidsNonRecursive as $pid) {
			$pageList .= $pid . ',';
		}

		// convert to array
		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$pageUidArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $pageList, true);
		} else {
			$pageUidArray = t3lib_div::trimExplode(',', $pageList, true);
		}

		return $pageUidArray;
	}


	/**
	 * get array with all pages
	 * but remove all pages we don't want to have
	 *
	 * @param array $uids Array with all page uids
	 * @param string $whereClause Additional where clause for the query
	 * @param string $table The table to select the fields from
	 * @param fields $fields The requested fields
	 * @return array Array containing page records with all available fields
	 */
	public function getPageRecords(array $uids, $whereClause = '', $table = 'pages', $fields = 'pages.*' ) {
		$where = 'pages.uid IN (' . implode(',', $uids) . ') ';
		// index only pages which are searchable
		// index only page which are not hidden
		$where .= ' AND pages.no_search <> 1 AND pages.hidden=0 AND pages.deleted=0';

		// additional where clause
		$where .= $whereClause;

		$pages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			$fields,
			$table,
			$where,
			'', '', '', 'uid'
		);

		return $pages;
	}


	/**
	 * get a list of pids
	 *
	 * @param string $startingPointsRecursive
	 * @param string $singlePages
	 * @param string $table
	 * @return array Array containing uids of pageRecords
	 */
	public function getPidList($startingPointsRecursive = '', $singlePages = '', $table = 'pages') {
		// get all pages. Regardless if they are shortcut, sysfolder or external link
		$indexPids = $this->getPagelist($startingPointsRecursive, $singlePages);

		// add complete page record to list of pids in $indexPids
		$where = ' AND ' . $table . '.pid = pages.uid ';
		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table);
			$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);
		} else {
			$where .= t3lib_befunc::BEenableFields($table);
			$where .= t3lib_befunc::deleteClause($table);
		}
		$this->pageRecords = $this->getPageRecords($indexPids, $where, 'pages,' . $table, 'pages.*' );
		if(count($this->pageRecords)) {
			// create a new list of allowed pids
			return array_keys($this->pageRecords);
		} else return array('0' => 0);
	}


	/**
	 * Add Tags to records array
	 *
	 * @param array Simple array with uids of pages
	 * @param string additional where-clause
	 * @return array extended array with uids and tags for records
	 */
	public function addTagsToRecords($uids, $pageWhere = '1=1') {
		$tagChar = $this->pObj->extConf['prePostTagChar'];

		// add tags which are defined by page properties
		$fields = 'pages.*, GROUP_CONCAT(CONCAT("' . $tagChar . '", tx_kesearch_filteroptions.tag, "' . $tagChar . '")) as tags';
		$table = 'pages, tx_kesearch_filteroptions';
		$where = 'pages.uid IN (' . implode(',', $uids) . ')';
		$where .= ' AND pages.tx_kesearch_tags <> "" ';
		$where .= ' AND FIND_IN_SET(tx_kesearch_filteroptions.uid, pages.tx_kesearch_tags)';
		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_kesearch_filteroptions');
			$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_kesearch_filteroptions');
		} else {
			$where .= t3lib_befunc::BEenableFields('tx_kesearch_filteroptions');
			$where .= t3lib_befunc::deleteClause('tx_kesearch_filteroptions');
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where, 'pages.uid', '', '');
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$this->pageRecords[$row['uid']]['tags'] = $row['tags'];
		}

		// add tags which are defined by filteroption records
		$fields = 'automated_tagging, automated_tagging_exclude, tag';
		$table = 'tx_kesearch_filteroptions';
		$where = 'automated_tagging <> "" ';
		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_kesearch_filteroptions');
			$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_kesearch_filteroptions');
		} else {
			$where .= t3lib_befunc::BEenableFields('tx_kesearch_filteroptions');
			$where .= t3lib_befunc::deleteClause('tx_kesearch_filteroptions');
		}

		$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, $where);

		$where = $pageWhere . ' AND no_search <> 1 ';

		foreach($rows as $row) {
			$tempTags = array();

			if ( $row['automated_tagging_exclude'] > '' ) {
				$whereRow = $where . 'AND FIND_IN_SET(pages.pid, "' . $row['automated_tagging_exclude'] .'") = 0';
			} else {
				$whereRow = $where;
			}

			if (TYPO3_VERSION_INTEGER >= 7000000) {
				$pageList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->queryGen->getTreeList($row['automated_tagging'], 99, 0, $whereRow));
			} else {
				$pageList = t3lib_div::trimExplode(',', $this->queryGen->getTreeList($row['automated_tagging'], 99, 0, $whereRow));
			}

			foreach($pageList as $uid) {
				if($this->pageRecords[$uid]['tags']) {
					$this->pageRecords[$uid]['tags'] .= ',' . $tagChar . $row['tag'] . $tagChar;
				} else $this->pageRecords[$uid]['tags'] = $tagChar . $row['tag'] . $tagChar;
			}
		}
	}


	/**
	 * shows time used
	 *
	 * @author  Christian Buelter <buelter@kennziffer.com>
	 * @return  string
 	*/
	public function showTime() {
		// calculate duration of indexing process
		$endMicrotime = microtime(true);
		$duration = ceil(($endMicrotime - $this->startMicrotime) * 1000);

		// show sec or ms?
		if ($duration > 1000) {
			$duration /= 1000;
			$duration = intval($duration);
			return '<p><i>Indexing process for "' . $this->indexerConfig['title'] . '" took '.$duration.' s.</i> </p>'."\n\n";
		} else {
			return '<p><i>Indexing process for "' . $this->indexerConfig['title'] . '" took '.$duration.' ms.</i> </p>'."\n\n";
		}
	}

	/**
	 * Prints errors which occured while indexing.
	 *
	 * @return string
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 26.11.13
	 */
	public function showErrors() {
		if (count($this->errors)) {
			return '<div class="error">' . implode('<br />' . "\n", $this->errors) . '</div>';
		} else {
			return '';
		}
	}

	/**
	 * adds an error to the error array
	 *
	 * @param string or array of strings $errorMessage
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 26.11.13
	 */
	public function addError($errorMessage) {
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
	 *
	 * @return array
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 26.11.13
	 */
	public function getErrors() {
		return $this->errors;
	}


	/*
	 * function getTag
	 * @param int $tagUid
	 * @param bool $clearText
	 */
	public function getTag($tagUid, $clearText=false) {
		$fields = 'title,tag';
		$table = 'tx_kesearch_filteroptions';
		$where = 'uid="'.intval($tagUid).'" ';
		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table);
			$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);
		} else {
			$where .= t3lib_befunc::BEenableFields($table);
			$where .= t3lib_befunc::deleteClause($table);
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='1');
		$anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

		if ($clearText) {
			return $row['title'];
		} else {
			return $row['tag'];
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/indexer/class.tx_kesearch_indexer_types.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/indexer/class.tx_kesearch_indexer_types.php']);
}
?>