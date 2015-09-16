<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
*  (c) 2012 Jan Bartels, ADFC NRW <j.bartels@adfc-nrw.de>
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
 * @author	Jan Bartels (ADFC NRW) <j.bartels@adfc-nrw.de>
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_indexer_types_mmforum extends tx_kesearch_indexer_types {

	/**
	 * Initializes indexer for mm_forum
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

		$table = 'tx_mmforum_forums';
		$indexPids = $this->getPidList($this->indexerConfig['startingpoints_recursive'], $this->indexerConfig['sysfolder'], $table);
		if ($this->indexerConfig['index_use_page_tags']) {
			// add the tags of each page to the global page array
			$this->pageRecords = $this->getPageRecords($indexPids);
			$this->addTagsToRecords($indexPids);
		}
			// get all the mm_forum forums to index
			// don't index hidden or deleted entries, BUT
			// get the entries with frontend user group access restrictions
			// or time (start / stop) restrictions.
			// Copy those restrictions to the index.

		$table = 'tx_mmforum_forums';
		$where = 'tx_mmforum_forums.pid IN (' . implode(',', $indexPids) . ') ';

		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$where .= TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_mmforum_forums');
			$where .= TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_mmforum_forums');
		} else {
			$where .= t3lib_BEfunc::BEenableFields('tx_mmforum_forums');
			$where .= t3lib_BEfunc::deleteClause('tx_mmforum_forums');
		}

		// Select forums
		$forumRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_mmforum_forums',
			$where,
			'', '', '', 'uid'
		);

		$topicCount = 0;
		foreach ($forumRecords as $forumRecord) {
			// calculate effective group-rights recursively
			if (!empty($forumRecord['grouprights_read'])) {
				$groups = explode(',', $forumRecord['grouprights_read'] );
			} else $groups = array();

			$parentID = $forumRecord['parentID'];
			if ($parentID) {
				$parentForum = $forumRecords[$parentID];

				if (!empty($parentForum['grouprights_read'])) {
					$groups = array_merge($groups, explode(',', $parentForum['grouprights_read']));
				}
				$parentID = $parentForum['parentID'];
			}

			$uniqueGroups = array();
			foreach ($groups as $group) {
				$uniqueGroups[intval($group)] = intval($group);
			}
			$fegroups = implode(',', $uniqueGroups);

			// get all the mm_forum topics to index
			// don't index hidden or deleted entries, BUT
			// get the entries with frontend user group access restrictions
			// or time (start / stop) restrictions.
			// Copy those restrictions to the index.

			$table  = 'tx_mmforum_topics';
			$where  = 'tx_mmforum_topics.forum_id = ' . $forumRecord['uid'] . ' ';
			if (TYPO3_VERSION_INTEGER >= 7000000) {
				$where .= TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('tx_mmforum_topics');
				$where .= TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_mmforum_topics');
			} else {
				$where .= t3lib_BEfunc::BEenableFields('tx_mmforum_topics');
				$where .= t3lib_BEfunc::deleteClause('tx_mmforum_topics');
			}

			// Select topics
			$resTopic = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$table,
				$where,
				'', '', ''
			);

			if ($resTopic) {
				while (($topicRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resTopic))) {
					++$topicCount;

					// get all the mm_forum text entries to index
					// don't index hidden or deleted entries, BUT
					// get the entries with frontend user group access restrictions
					// or time (start / stop) restrictions.
					// Copy those restrictions to the index.
					$table  = 'tx_mmforum_posts_text, tx_mmforum_posts';
					$where  = 'tx_mmforum_posts_text.post_id = tx_mmforum_posts.uid ';
					$where .= 'AND tx_mmforum_posts.topic_id = ' . $topicRecord[ 'uid' ] . ' ';
					$where .= 'AND tx_mmforum_posts.forum_id = ' . $forumRecord[ 'uid' ] . ' ';

					if (TYPO3_VERSION_INTEGER >= 7000000) {
						$where .= TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_mmforum_posts_text');
						$where .= TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('tx_mmforum_posts');
					} else {
						$where .= t3lib_BEfunc::deleteClause('tx_mmforum_posts_text');
						$where .= t3lib_BEfunc::deleteClause('tx_mmforum_posts');
					}

					$groupBy = '';
					$orderBy = '';
					$limit = '';

					// Select post-texts
					$resTexts = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'tx_mmforum_posts_text.post_text',
						$table,
						$where,
						$groupBy,
						$orderBy,
						$limit
					);

					$content = '';
					if ($resTexts) {
						while (($textRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resTexts))) {
							$content .= strip_tags($textRecord['post_text']) . "\n";
						}
					}
					// compile the information which should go into the index
					$title = strip_tags($topicRecord['topic_title']);
					$abstract = '';
					$fullContent = $abstract . "\n" . $content ."\n";

					// create params
					$paramsSingleView = array();
					$paramsSingleView['tx_mmforum_pi1']['action'] = 'list_post';
					$paramsSingleView['tx_mmforum_pi1']['fid'] = $forumRecord[ 'uid' ];  // ###FORUM_ID###
					$paramsSingleView['tx_mmforum_pi1']['tid'] = $topicRecord[ 'uid' ];  // ###TOPIC_ID###
					$params = '&' . http_build_query($paramsSingleView, NULL, '&');
					$params = rawurldecode($params);

					// create tags
					if($this->indexerConfig['index_use_page_tags']) {
						$tags = $this->pageRecords[intval($topicRecord['pid'])]['tags'];
					} else $tags = '';

					$additionalFields = array();
					// crdate is always given, but can be overwritten
					if (isset($topicRecord['topic_time']) && $topicRecord['topic_time'] > 0) {
						$additionalFields['sortdate'] = $topicRecord['topic_time'];
					}

					// fill orig_uid
					if (isset($topicRecord[ 'uid' ]) && $topicRecord[ 'uid' ] > 0) {
						$additionalFields['orig_uid'] = $topicRecord[ 'uid' ];
					}
					// fill orig_pid
					if (isset($topicRecord[ 'pid' ]) && $topicRecord[ 'pid' ] > 0) {
						$additionalFields['orig_pid'] = $topicRecord[ 'pid' ];
					}

					// make it possible to modify the indexerConfig via hook
					$indexerConfig = $this->indexerConfig;

						// hook for custom modifications of the indexed data, e. g. the tags
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyMMForumIndexEntry'])) {
						foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyMMForumIndexEntry'] as $_classRef) {
							if (TYPO3_VERSION_INTEGER >= 7000000) {
								$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
							} else {
								$_procObj = & t3lib_div::getUserObj($_classRef);
							}
							$_procObj->modifyMMForumIndexEntry(
								$title,
								$abstract,
								$fullContent,
								$params,
								$tags,
								$postRecord,
								$additionalFields,
								$indexerConfig
							);
						}
					}

					// ... and store them
					$this->pObj->storeInIndex(
						$indexerConfig['storagepid'],    // storage PID
						$title,                          // page title
						'mm_forum',                   // content type
						$indexerConfig['targetpid'],     // target PID: where is the single view?
						$fullContent,                    // indexed content, includes the title (linebreak after title)
						$tags,                           // tags
						$params,                         // typolink params for singleview
						$abstract,                       // abstract
						0,                               // language uid ####
						0,                               // starttime ####
						0,                               // endtime ###
						$fegroups,                       // fe_group
						false,                           // debug only?
						$additionalFields                // additional fields added by hooks
					);
				}
			}
		}

		if ($topicCount) {
			$content = '<p><b>Indexer "' . $this->indexerConfig['title'] . '":</b><br />' . "\n"
				. $topicCount . ' mm_forum topics have been indexed.</p>' . "\n";
			$content .= $this->showTime();
		}

		$content .= $this->showErrors();

		return $content;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/indexer/types/class.tx_kesearch_indexer_types_mmforum.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/indexer/types/class.tx_kesearch_indexer_types_mmforum.php']);
}
?>