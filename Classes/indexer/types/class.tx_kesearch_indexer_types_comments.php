<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Christian Bülter (kennziffer.com) <buelter@kennziffer.com>
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
 * @author	Christian Bülter (kennziffer.com) <buelter@kennziffer.com>
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_indexer_types_comments extends tx_kesearch_indexer_types {

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

			// get all the records to index
			// don't index hidden, deleted or not approved comments
		$fields = '*';
		$table = 'tx_comments_comments';
		$indexPids = $this->getPidList($this->indexerConfig['startingpoints_recursive'], $this->indexerConfig['sysfolder'], $table);
		if($this->indexerConfig['index_use_page_tags']) {
			// add the tags of each page to the global page array
			$this->pageRecords = $this->getPageRecords($indexPids);
			$this->addTagsToRecords($indexPids);
		}
		$where = 'pid IN (' . implode(',', $indexPids) . ') ';
		$where .= ' AND approved=1';

		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$where .= ' AND external_prefix IN ("' . implode('","', TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->indexerConfig['commenttypes'])) . '")';
			$where .= TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table);
			$where .= TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);
		} else {
			$where .= ' AND external_prefix IN ("' . implode('","',t3lib_div::trimExplode(',', $this->indexerConfig['commenttypes'])) . '")';
			$where .= t3lib_befunc::BEenableFields($table);
			$where .= t3lib_befunc::deleteClause($table);
		}

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where);
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
			$count = 0;
			while ( ($comment = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) ) {

				// compile the information which should go into the index
				$title = $this->compileCommentTitle($comment);
				$abstract = '';
				$content = trim(strip_tags($comment['content']));

				// use page ID stored in field "external_ref" as target page
				// makes sense for comments to page
				// Should be adjusted for other comments?
				if ($comment['external_prefix'] == 'pages') {
					$external_ref_exploded = explode('_', $comment['external_ref']);
					$targetPage = $external_ref_exploded[1];
				} else {
					// TODO: Make the target page configurable, eg. for tt_news comments
					//$targetPage = $indexerConfig['targetpid'];
					$targetPage = 0;
				}

				// create tags
				if($this->indexerConfig['index_use_page_tags']) {
					$tags = $this->pageRecords[intval($comment['pid'])]['tags'];
				} else $tags = '';

				// fill additional fields
				$additionalFields = array(
					'orig_uid' => $comment['uid'],
					'orig_pid' => $comment['pid'],
					'sortdate' => $comment['crdate']
				);

				// make it possible to modify the indexerConfig via hook
				$indexerConfig = $this->indexerConfig;

				// hook for custom modifications of the indexed data, e. g. the tags
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyCommentsIndexEntry'])) {
					foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyCommentsIndexEntry'] as $_classRef) {
						if (TYPO3_VERSION_INTEGER >= 7000000) {
							$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
						} else {
							$_procObj = & t3lib_div::getUserObj($_classRef);
						}
						$_procObj->modifyCommentsIndexEntry(
							$title,
							$abstract,
							$content,
							$params,
							$tags,
							$comment,
							$additionalFields,
							$indexerConfig
						);
					}
				}

				// ... and store them
				$this->pObj->storeInIndex(
					$indexerConfig['storagepid'],   // storage PID
					$title,                         // page title
					'comments',                     // content type
					$targetPage,  				    // target PID: where is the single view?
					$content,	                    // indexed content, includes the title (linebreak after title)
					$tags,                          // tags
					$params,                        // typolink params for singleview
					$abstract,                      // abstract
					-1,								// language uid is -1 because comments is not able to use multiple languages
					0,								// starttime
					0,								// endtime
					'',								// fe_group
					false,                          // debug only?
					$additionalFields               // additional fields added by hooks
				);
				$count++;
			}

			$content = '<p><b>Indexer "' . $this->indexerConfig['title'] . '":</b><br />' . "\n"
					. $count . ' Comments have been indexed.</p>' . "\n";

			$content .= $this->showErrors();
			$content .= $this->showTime();
		}
		return $content;
	}

	/**
	 * compiles the title from different fields of the comment
	 *
	 * @param type $comment
	 */
	public function compileCommentTitle($comment=array()) {
		$title = '';

		if ($comment['firstname']) {
			$title = $comment['firstname'];
		}

		if ($comment['lastname']) {
			if (!empty($title)) {
				$title .= ' ';
			}
			$title .= $comment['lastname'];
		}

		if ($comment['location']) {
			if (!empty($title)) {
				$title .= ', ';
			}
			$title .= $comment['location'];
		}
		return $title;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/indexer/types/class.tx_kesearch_indexer_types_ttnews.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/indexer/types/class.tx_kesearch_indexer_types_ttnews.php']);
}
?>