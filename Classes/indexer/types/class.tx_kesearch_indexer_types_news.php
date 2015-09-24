<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Christian Bülter (kennziffer.com) <buelter@kennziffer.com>
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
 * @author	Stefan Frömken
 * @author	Christian Bülter (kennziffer.com) <buelter@kennziffer.com>
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_indexer_types_news extends tx_kesearch_indexer_types {

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
		$table = 'tx_news_domain_model_news';

		// get the pages from where to index the news
		$indexPids = $this->getPidList($this->indexerConfig['startingpoints_recursive'], $this->indexerConfig['sysfolder'], $table);

		// add the tags of each page to the global page array
		if($this->indexerConfig['index_use_page_tags']) {
			$this->pageRecords = $this->getPageRecords($indexPids);
			$this->addTagsToRecords($indexPids);
		}

		// get all the news entries to index, don't index hidden or
		// deleted news, BUT  get the news with frontend user group
		// access restrictions or time (start / stop) restrictions.
		// Copy those restrictions to the index.
		$fields = '*';
		$where = 'pid IN (' . implode(',', $indexPids) . ') ';
		$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table);
		$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
		$indexedNewsCounter = 0;
		$resCount = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		if ($resCount) {
			while (($newsRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))) {

				// get category data for this news record (list of
				// assigned categories and single view from category, if it exists)
				$categoryData = $this->getCategoryData($newsRecord);

				// If mode equals 2 ('choose categories for indexing')
				// check if the current news record has one of the categories
				// assigned that should be indexed.
				// mode 1 means 'index all news no matter what category
				// they have'
				if ($this->indexerConfig['index_news_category_mode'] == '2') {

					$isInList = false;
					foreach ($categoryData['uid_list'] as $catUid) {
						// if category was found in list, set isInList
						// to true and break further processing.
						if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->indexerConfig['index_extnews_category_selection'], $catUid)) { $isInList = true; break; }
					}

					// if category was not fount stop further processing
					// and continue with next news record
					if(!$isInList) {
						continue ;
					}
				}

				// compile the information which should go into the index:
				// title, teaser, bodytext
				$type     = 'news';
				$title    = strip_tags($newsRecord['title']);
				$abstract = strip_tags($newsRecord['teaser']);
				$content  = strip_tags($newsRecord['bodytext']);

				// add additional fields to the content:
				// alternative_title, author, author_email, keywords
				if (isset($newsRecord['author'])) {
					$content .= "\n" . strip_tags($newsRecord['author']);
				}
				if (isset($newsRecord['author_email'])) {
					$content .= "\n" . strip_tags($newsRecord['author_email']);
				}
				if (!empty($newsRecord['keywords'])) {
					$content .= "\n" . $newsRecord['keywords'];
				}

				// TODO:
				// in ext:news it is possible to assign content elements
				// to news elements. This content elements should be indexed.

				// create content
				$fullContent = '';
				if (isset($abstract)) {
					$fullContent .= $abstract . "\n";
				}
				$fullContent .= $content;

				// make it possible to modify the indexerConfig via hook
				$indexerConfig = $this->indexerConfig;

				// create params and custom single view page:
				// if it is a default news (type = 0), add params
				// if it is an internal page (type = 1), put that into the "targetpid" field
				// if it is an external url (type = 2), put that into the "params" field
				if ($newsRecord['type'] == 1) {
					$indexerConfig['targetpid'] = $newsRecord['internalurl'];
					$params = '';
				} else if ($newsRecord['type'] == 2) {
					$type = 'external:news';
					$params = $newsRecord['externalurl'];
				} else {
					// overwrite the targetpid if there is a category assigned
					// which has its own single view page
					if ($categoryData['single_pid']) {
						$indexerConfig['targetpid'] = $categoryData['single_pid'];
					}

					// create params for news single view, example:
					// index.php?id=123&tx_news_pi1[news]=9&tx_news_pi1[controller]=News&tx_news_pi1[action]=detail
					$paramsSingleView['tx_news_pi1']['news'] = $newsRecord['uid'];
					$paramsSingleView['tx_news_pi1']['controller'] = 'News';
					$paramsSingleView['tx_news_pi1']['action'] = 'detail';
					$params = '&' . http_build_query($paramsSingleView, NULL, '&');
					$params = rawurldecode($params);
				}

				// add tags from pages
				if ($indexerConfig['index_use_page_tags']) {
					$tags = $this->pageRecords[intval($newsRecord['pid'])]['tags'];
				} else {
					$tags = '';
				}

				// add keywords from ext:news as tags
				$tags = $this->addTagsFromNewsKeywords($tags, $newsRecord);

				// add tags from ext:news as tags
				$tags = $this->addTagsFromNewsTags($tags, $newsRecord);

				// add categories from from ext:news as tags
				$tags = $this->addTagsFromNewsCategories($tags, $categoryData);

				// add system categories as tags
				tx_kesearch_helper::makeSystemCategoryTags($tags, $newsRecord['uid'], $table);

				// set additional fields
				$additionalFields = array();
				$additionalFields['orig_uid'] = $newsRecord['uid'];
				$additionalFields['orig_pid'] = $newsRecord['pid'];
				$additionalFields['sortdate'] = $newsRecord['crdate'];
				if(isset($newsRecord['datetime']) && $newsRecord['datetime'] > 0) {
					$additionalFields['sortdate'] = $newsRecord['datetime'];
				}

				// hook for custom modifications of the indexed data, e.g. the tags
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyExtNewsIndexEntry'])) {
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyExtNewsIndexEntry'] as $_classRef) {
						$_procObj = & \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
						$_procObj->modifyExtNewsIndexEntry(
							$title,
							$abstract,
							$fullContent,
							$params,
							$tags,
							$newsRecord,
							$additionalFields,
							$indexerConfig,
							$categoryData,
							$this
						);
					}
				}

				// store this record to the index
				$this->pObj->storeInIndex(
					$indexerConfig['storagepid'],	// storage PID
					$title,                         // page title
					$type,                       	// content type
					$indexerConfig['targetpid'],    // target PID: where is the single view?
					$fullContent,                   // indexed content, includes the title (linebreak after title)
					$tags,                          // tags
					$params,                        // typolink params for singleview
					$abstract,                      // abstract
					$newsRecord['sys_language_uid'],// language uid
					$newsRecord['starttime'],       // starttime
					$newsRecord['endtime'],         // endtime
					$newsRecord['fe_group'],        // fe_group
					false,                          // debug only?
					$additionalFields               // additional fields added by hooks
				);
				$indexedNewsCounter++;
			}

			$content = '<p><b>Indexer "' . $this->indexerConfig['title'] . '":</b><br />' . "\n"
					. $indexedNewsCounter . ' News have been indexed.</p>' . "\n";

			$content .= $this->showErrors();
			$content .= $this->showTime();
		}
		return $content;
	}


	/**
	 * checks if there is a category assigned to the $newsRecord which has
	 * its own single view page and if yes, returns the uid of the page
	 * in $catagoryData['single_pid'].
	 * It also compiles a list of all assigned categories and returns
	 * it as an array in $categoryData['uid_list']. The titles of the
	 * categories are returned in $categoryData['title_list'] (array)
	 *
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 26.06.13 14:34
	 * @param type $newsRecord
	 * @return int
	 */
	private function getCategoryData($newsRecord) {
		$categoryData = array(
		    'single_pid' => 0,
		    'uid_list' => array(),
		    'title_list' => array()
		);

		// news version 3 features system categories instead of it's own
		// category system used in previous versions
		$ttnewsVersion = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('news');
		if (version_compare($ttnewsVersion, '3.0.0') >= 0) {

			$where = ' AND tx_news_domain_model_news.uid = ' . $newsRecord['uid'] .
				' AND sys_category_record_mm.tablenames = "tx_news_domain_model_news"';
			$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('sys_category') .  \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_category');

			$resCat = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'sys_category.uid, sys_category.single_pid, sys_category.title',
				'sys_category',
				'sys_category_record_mm',
				'tx_news_domain_model_news',
				$where,
				'', // groupBy
				'sys_category_record_mm.sorting' // orderBy
			);
		} else {

			$where = ' AND tx_news_domain_model_news.uid = ' . $newsRecord['uid'];
			$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_news_domain_model_category') .  \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_news_domain_model_category');

			$resCat = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'tx_news_domain_model_category.uid, tx_news_domain_model_category.single_pid, tx_news_domain_model_category.title',
				'tx_news_domain_model_news',
				'tx_news_domain_model_news_category_mm',
				'tx_news_domain_model_category',
				$where,
				'', // groupBy
				'tx_news_domain_model_news_category_mm.sorting' // orderBy
			);
		}

		while (($newsCat = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resCat))) {
			$categoryData['uid_list'][] = $newsCat['uid'];
			$categoryData['title_list'][] = $newsCat['title'];
			if ($newsCat['single_pid'] && !$categoryData['single_pid']) {
				$categoryData['single_pid'] = $newsCat['single_pid'];
			}
		}

		return $categoryData;
	}

	/**
	 * adds tags from the ext:news "keywords" field to the index entry
	 *
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 26.06.13 14:27
	 * @param string $tags
	 * @param array $newsRecord
	 * @return string
	 */
	private function addTagsFromNewsKeywords($tags, $newsRecord) {
		if (!empty($newsRecord['keywords'])) {
			$keywordsList = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $newsRecord['keywords']);
			foreach ($keywordsList as $keyword) {
				tx_kesearch_helper::makeTags($tags, array($keyword));
			}
		}

		return $tags;
	}

	/**
	 * Adds tags from the ext:news table "tags" as ke_search tags to the index entry
	 *
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 26.06.13 14:25
	 * @param string $tags
	 * @param array $newsRecord
	 * @return string comma-separated list of tags
	 */
	private function addTagsFromNewsTags($tags, $newsRecord) {
		$addWhere = \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_news_domain_model_tag') . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_news_domain_model_tag');
		$resTag = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_news_domain_model_tag.title',
			'tx_news_domain_model_news',
			'tx_news_domain_model_news_tag_mm',
			'tx_news_domain_model_tag',
			' AND tx_news_domain_model_news.uid = ' . $newsRecord['uid'] .
			$addWhere
		);

		while (($newsTag = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resTag))) {
			tx_kesearch_helper::makeTags($tags, array($newsTag['title']));
		}

		return $tags;
	}

	/**
	 * creates tags from category titles
	 *
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 26.06.13 15:49
	 * @param string $tags
	 * @param array $categoryData
	 * @return string
	 */
	private function addTagsFromNewsCategories($tags, $categoryData) {
		tx_kesearch_helper::makeTags($tags, $categoryData['title_list']);
		return $tags;
	}
}
