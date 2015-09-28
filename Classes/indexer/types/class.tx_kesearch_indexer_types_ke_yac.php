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
class tx_kesearch_indexer_types_ke_yac extends tx_kesearch_indexer_types {

	/**
	 * Initializes indexer for ke_yac
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
		$tagChar = $this->pObj->extConf['prePostTagChar'];
		$now = strtotime('today');

		// get YAC records from specified pid
		$fields = '*';
		$table = 'tx_keyac_dates';
		$where = 'pid IN ('.$this->indexerConfig['sysfolder'].') ';
		$where .= ' AND hidden=0 AND deleted=0 ';
		// do not index passed events?
		if ($this->indexerConfig['index_passed_events'] == 'no') {
			$keYacProductsIsLoaded = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('ke_yac_products');
			if ($keYacProductsIsLoaded) {
				// special query if ke_yac_products loaded (VNR)
				$where .= '
					AND ((
						tx_keyacproducts_type<>"product"
						AND (startdat >= "'.time().'" OR enddat >= "'.time().'")
					) OR (tx_keyacproducts_type="product" AND tx_keyacproducts_product<>""))';
			} else {
				// "normal" YAC events
				$where .= ' AND (startdat >= "'.time().'" OR enddat >= "'.time().'")';
			}
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
		$resCount = $GLOBALS['TYPO3_DB']->sql_num_rows($res);

		if ($resCount) {
			while ($yacRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// prepare content for storing in index table
				$title = strip_tags($yacRecord['title']);
				$tags = '';
				$params = '&tx_keyac_pi1[showUid]='.intval($yacRecord['uid']);
				$abstract = str_replace('<br />', chr(13), $yacRecord['teaser']);
				$abstract = str_replace('<br>', chr(13), $abstract);
				$abstract = str_replace('</p>', chr(13), $abstract);
				$abstract = strip_tags($abstract);
				$content = strip_tags($yacRecord['bodytext']);
				$fullContent = $abstract . "\n" . $content;
				$targetPID = $this->indexerConfig['targetpid'];

				// get tags
				$yacRecordTags = TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$yacRecord['tx_keyacsearchtags_tags'], true);

				$tags = '';
				$clearTextTags = '';
				if (count($yacRecordTags)) {
					foreach ($yacRecordTags as $key => $tagUid)  {
						if($tags) {
							$tags .= ',' . $tagChar . $this->getTag($tagUid) . $tagChar;
						} else $tags = $tagChar . $this->getTag($tagUid) . $tagChar;
						$clearTextTags .= chr(13).$this->getTag($tagUid, true);
					}
				}

					// add clearText Tags to content
				if (!empty($clearTextTags)) $fullContent .= chr(13).$clearTextTags;

					// make it possible to modify the indexerConfig via hook
				$indexerConfig = $this->indexerConfig;

					// hook for custom modifications of the indexed data, e. g. the tags
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyYACIndexEntry'])) {
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyYACIndexEntry'] as $_classRef) {
						$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
						$_procObj->modifyYACIndexEntry(
							$title,
							$abstract,
							$fullContent,
							$params,
							$tags,
							$yacRecord,
							$targetPID,
							$additionalFields,
							$indexerConfig
						);
					}
				}

				// store data in index table
				$this->pObj->storeInIndex(
					$indexerConfig['storagepid'],				// storage PID
					$title,										// page/record title
					'ke_yac', 									// content type
					$targetPID,									// target PID: where is the single view?
					$fullContent, 								// indexed content, includes the title (linebreak after title)
					$tags,				 						// tags
					$params, 									// typolink params for singleview
					$abstract,									// abstract
					$yacRecord['sys_language_uid'],				// language uid
					$yacRecord['starttime'], 					// starttime
					$yacRecord['endtime'], 						// endtime
					$yacRecord['fe_group'], 					// fe_group
					false, 										// debug only?
					$additionalFields							// additional fields added by hooks
				);
			}
		}

		$content = '<p><b>Indexer "' . $this->indexerConfig['title'] . '": ' . $resCount . ' YAC records have been indexed.</b></p>'."\n";

		$content .= $this->showErrors();
		$content .= $this->showTime();

		return $content;
	}
}