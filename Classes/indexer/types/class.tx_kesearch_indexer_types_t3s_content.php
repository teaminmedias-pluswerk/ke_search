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
class tx_kesearch_indexer_types_t3s_content extends tx_kesearch_indexer_types {

	/**
	 * Initializes indexer for pages
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
		// get all content elements containing an t3s_content-Plugin
		$ttContent = $this->getTtContentRecordsWithT3sPlugin();
		$this->saveT3sRecordsToDb($ttContent);

		// show indexer content?
		$content .= '<p><b>Indexer "' . $this->indexerConfig['title'] . '": ' . count($ttContent) . ' t3s_content entries have been found for indexing.</b></p>' . "\n";

		$content .= $this->showErrors();
		$content .= $this->showTime();

		return $content;
	}


	/**
	 * get all content elements containing a t3s_content-Plugin
	 *
	 * @return array Array containing tt_content records
	 */
	public function getTtContentRecordsWithT3sPlugin() {
		$targetPid = intval($this->indexerConfig['targetpid']);

		if($targetPid) {
			$rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'tt_content',
				'1=1' .
				' AND pid=' . $targetPid .
				' AND CType="list"' .
				' AND list_type="t3s_content_pi1"' .
				' AND hidden=0 AND deleted=0',
				'', '', '', 'uid'
			);
			if($rows) {
				foreach($rows as $key => $row) {
					if (TYPO3_VERSION_INTEGER >= 7000000) {
						$xml = TYPO3\CMS\Core\Utility\GeneralUtility::xml2array($row['pi_flexform']);
					} else {
						$xml = t3lib_div::xml2array($row['pi_flexform']);
					}
					$config = $xml['data']['general']['lDEF'];
					$ttContentUids[] = $config['contentElements']['vDEF'];
				}
				$ttContentUids = implode(',', $ttContentUids);
				if (TYPO3_VERSION_INTEGER >= 7000000) {
					$ttContentUids = TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList($ttContentUids);
				} else {
					$ttContentUids = t3lib_div::uniqueList($ttContentUids);
				}
				$ttContentRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'*',
					'tt_content',
					' uid IN (' . $ttContentUids . ')',
					'', '', '', 'uid'
				);
				if($ttContentRecords) return $ttContentRecords;
			}
		}
		return array();
	}


	/**
	 * save t3s_content records to DB
	 * @param array $rows tt_content records
	 */
	public function saveT3sRecordsToDb($rows) {
		if(count($rows)) {
			foreach($rows as $row) {
				// header
				$title = strip_tags($row['header']);

				// following lines prevents having words one after the other like: HelloAllTogether
				$bodytext = $row['bodytext'];
				$bodytext = str_replace('<td', ' <td', $bodytext);
				$bodytext = str_replace('<br', ' <br', $bodytext);
				$bodytext = str_replace('<p', ' <p', $bodytext);
				$bodytext = str_replace('<li', ' <li', $bodytext);

				// crdate is always given, but can be overwritten
				if(isset($row['crdate']) && $row['crdate'] > 0) {
					$additionalFields['sortdate'] = $row['crdate'];
				}
				// if TYPO3 sets last changed
				if(isset($row['tstamp']) && $row['tstamp'] > 0) {
					$additionalFields['sortdate'] = $row['tstamp'];
				}

				// fill orig_uid
				if(isset($row['uid']) && $row['uid'] > 0) {
					$additionalFields['orig_uid'] = $row['uid'];
				}
				// fill orig_pid
				if(isset($row['pid']) && $row['pid'] > 0) {
					$additionalFields['orig_pid'] = $row['pid'];
				}

				// save record to index
				$this->pObj->storeInIndex(
					$this->indexerConfig['storagepid'], // storage PID
					$title,                             // content title
					't3s_content',                      // content type
					$this->indexerConfig['targetpid'],  // target PID: where is the single view?
					$row['bodytext'],                   // indexed content, includes the title (linebreak after title)
					'',                                 // tags
					'',                                 // typolink params for singleview
					'',                                 // abstract
					$row['sys_language_uid'],           // language uid
					$row['starttime'],                  // starttime
					$row['endtime'],                    // endtime
					0,                                  // fe_group
					false,                              // debug only?
					$additionalFields                   // additional fields added by hooks
				);
			}
		} else {
			return;
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/indexer/types/class.tx_kesearch_indexer_types_t3s_content.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/indexer/types/class.tx_kesearch_indexer_types_t3s_content.php']);
}
?>