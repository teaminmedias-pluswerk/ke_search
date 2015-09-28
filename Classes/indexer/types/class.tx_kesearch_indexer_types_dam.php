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
class tx_kesearch_indexer_types_dam extends tx_kesearch_indexer_types {

	var $catList = ''; // holder for recursive/non-recursive dam categories


	/**
	 * Initializes indexer for dam
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

		// get categories
		$categories = $this->getCategories();

		// get dam records from categories
		$fields = 'DISTINCT tx_dam.*';
		$table = 'tx_dam_mm_cat, tx_dam';
		$where = '1=1';
		if(is_array($categories) && count($categories)) {
			if($this->indexerConfig['index_dam_without_categories']) {
				$table = 'tx_dam_mm_cat RIGHT JOIN tx_dam ON (tx_dam_mm_cat.uid_local = tx_dam.uid)';
				$where .= ' AND uid_foreign IN (' . implode(',', $categories) . ')';
				$where .= ' OR tx_dam.category = 0';
			} else {
				$where .= ' AND tx_dam_mm_cat.uid_local = tx_dam.uid';
				$where .= ' AND uid_foreign IN (' . implode(',', $categories) . ')';
			}
		}

		$where .= TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_dam');
		$where .= TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_dam');

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where);
		$resCount = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		if ($resCount) {
			while ($damRecord=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

				$additionalFields = array();

				// prepare content for storing in index table
				$title = strip_tags($damRecord['title']);
				$params = '&tx_dam[uid]='.intval($damRecord['uid']);
				$abstract = '';
				$content = strip_tags($damRecord['description']);
				$title = strip_tags($damRecord['title']);
				$keywords = strip_tags($damRecord['keywords']);
				$filename = strip_tags($damRecord['file_name']);
				$fullContent = $content . "\n" . $keywords . "\n" . $filename;
				$targetPID = $this->indexerConfig['targetpid'];

				// get tags for this record
				// needs extension ke_search_dam_tags
				$keSearchDamTagsIsLoaded = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('ke_search_dam_tags');
				if ($keSearchDamTagsIsLoaded) {
					$damRecordTags = TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',',$damRecord['tx_kesearchdamtags_tags'], true);
					$tags = '';
					$clearTextTags = '';
					if (count($damRecordTags)) {
						foreach ($damRecordTags as $key => $tagUid)  {
							if($tags) {
								$tags .= ',' . $tagChar . $this->getTag($tagUid) . $tagChar;
							} else $tags = $tagChar . $this->getTag($tagUid) . $tagChar;
							$clearTextTags .= chr(13).$this->getTag($tagUid, true);
						}
					}
				} else {
					$tags = '';
				}


					// make it possible to modify the indexerConfig via hook
				$indexerConfig = $this->indexerConfig;

					// hook for custom modifications of the indexed data, e. g. the tags
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyDAMIndexEntry'])) {
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyDAMIndexEntry'] as $_classRef) {
						$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
						$_procObj->modifyDAMIndexEntry(
							$title,
							$abstract,
							$fullContent,
							$params,
							$tags,
							$damRecord,
							$targetPID,
							$clearTextTags,
							$additionalFields,
							$indexerConfig
						);
					}
				}

					// add clearText Tags to content, make them searchable
					// by fulltext search
				if (!empty($clearTextTags)) $fullContent .= $clearTextTags;

				// store data in index table
				$this->pObj->storeInIndex(
					$indexerConfig['storagepid'],   // storage PID
					$title,                         // page/record title
					'dam',                          // content type
					$indexerConfig['targetpid'],    // target PID: where is the single view?
					$fullContent,                   // indexed content, includes the title (linebreak after title)
					$tags,                          // tags
					$params,                        // typolink params for singleview
					$abstract,                      // abstract
					$damRecord['sys_language_uid'], // language uid
					$damRecord['starttime'],        // starttime
					$damRecord['endtime'],          // endtime
					$damRecord['fe_group'],         // fe_group
					false,                          // debug only?
					$additionalFields               // additional fields added by hooks
				);

			}
		}

		$content = '<p><b>Indexer "' . $this->indexerConfig['title'] . '": ' . $resCount . ' DAM records have been indexed.</b></p>'."\n";

		$content .= $this->showErrors();
		$content .= $this->showTime();

		return $content;
	}


	/**
	 * get categories
	 *
	 * @return array A simple array containing the category uids
	 */
	public function getCategories() {
		// remove empty values from category list
		$indexerCategories = TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->indexerConfig['index_dam_categories'], true);

		// if valid array then make array unique
		if(is_array($indexerCategories) && count($indexerCategories)) {
			$indexerCategories = array_unique($indexerCategories);
		}

		// add recursive categories if set in indexer config
		if($this->indexerConfig['index_dam_categories_recursive'] && is_array($indexerCategories) && count($indexerCategories)) {
			foreach($indexerCategories as $value) {
				$categories[] = $this->getRecursiveDAMCategories($value);
			}
			// remove empty values from list and make array unique
			$categories = TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', implode(',', $categories), true);
			$indexerCategories = array_unique($categories);
		}

		if(is_array($indexerCategories) && count($indexerCategories)) {
			return $indexerCategories;
		} else { // if array is empty
			return array();
		}
	}


	/**
	 * get recursive DAM categories
	 *
	 * @param integer $catUid The category uid to search in recursive records
	 * @param integer $depth Recursive depth. Normally you don't have to set it.
	 * @return string A commaseperated list of category uids
	 */
	public function getRecursiveDAMCategories($catUid, $depth = 0) {
		if($catUid) {
			$enableFields = TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_dam_cat') . TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_dam_cat');

			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'GROUP_CONCAT(uid) AS categoryUids',
				'tx_dam_cat',
				'parent_id = ' . intval($catUid) . $enableFields,
				'', '', ''
			);

			// add categories to list
			$listOfCategories = $row['categoryUids'];
			$categories = TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $row['categoryUids']);

			if(is_array($categories) && count($categories)) {
				foreach($categories as $category) {
					// only if further categories are found, add them to list
					$tempCatList = $this->getRecursiveDAMCategories($category, $depth + 1);

					$addCategory = count(TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $tempCatList, true));
					if ($addCategory) {
						$listOfCategories .= ',' . $tempCatList;
					}
				}
			}
			return ($depth===0 ? $catUid . ',' : '') . $listOfCategories;
		} else {
			return '';
		}
	}
}