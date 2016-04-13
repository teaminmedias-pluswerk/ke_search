<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 Christian Bülter (kennziffer.com) <buelter@kennziffer.com>
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

use TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * helper functions
 * must be used used statically!
 * Example:
 * $this->extConf = tx_kesearch_helper::getExtConf();
 */
class tx_kesearch_helper {

	/**
	 * get extension manager configuration for ke_search
	 * and make it possible to override it with page ts setup
	 *
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 14.10.14
	 * @return array
	 */
	public static function getExtConf() {
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ke_search']);

		// Set the "tagChar"
		// sphinx has problems with # in query string.
		// so you we need to change the default char # against something else.
		// MySQL has problems also with #
		// but we wrap # with " and it works.
		$keSearchPremiumIsLoaded = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('ke_search_premium');
		if ($keSearchPremiumIsLoaded) {
			$extConfPremium = tx_kesearch_helper::getExtConfPremium();
			$extConf['prePostTagChar'] = $extConfPremium['prePostTagChar'];
		} else {
			$extConf['prePostTagChar'] = '#';
		}
		$extConf['multiplyValueToTitle'] = ($extConf['multiplyValueToTitle']) ? $extConf['multiplyValueToTitle'] : 1;
		$extConf['searchWordLength'] = ($extConf['searchWordLength']) ? $extConf['searchWordLength'] : 4;

		// override extConf with TS Setup
		if (is_array($GLOBALS['TSFE']->tmpl->setup['ke_search.']['extconf.']['override.']) && count($GLOBALS['TSFE']->tmpl->setup['ke_search.']['extconf.']['override.'])) {
			foreach ($GLOBALS['TSFE']->tmpl->setup['ke_search.']['extconf.']['override.'] as $key => $value) {
				$extConf[$key] = $value;
			}
		}

		return $extConf;
	}

	/**
	 * get extension manager configuration for ke_search_premium
	 * and make it possible to override it with page ts setup
	 *
	 * @return array
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 14.10.14
	 */
	public static function getExtConfPremium() {
		$keSearchPremiumIsLoaded = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('ke_search_premium');
		if ($keSearchPremiumIsLoaded) {
			$extConfPremium = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ke_search_premium']);
			if (!$extConfPremium['prePostTagChar']) $extConfPremium['prePostTagChar'] = '_';
		} else {
			$extConfPremium = array();
		}

		// override extConfPremium with TS Setup
		if (is_array($GLOBALS['TSFE']->tmpl->setup['ke_search_premium.']['extconf.']['override.']) && count($GLOBALS['TSFE']->tmpl->setup['ke_search_premium.']['extconf.']['override.'])) {
			foreach ($GLOBALS['TSFE']->tmpl->setup['ke_search_premium.']['extconf.']['override.'] as $key => $value) {
				$extConfPremium[$key] = $value;
			}
		}

		return $extConfPremium;
	}

	/**
	 * returns the list of assigned categories to a certain record in a certain table
	 *
	 * @param integer $uid
	 * @param string $table
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 17.10.14
	 * @return array
	 */
	public static function getCategories($uid, $table) {
		$categoryData = array(
			'uid_list' => array(),
			'title_list' => array()
		);

		if ($uid && $table) {
			$enableFields = \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('sys_category') . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_category');
			$resCat = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				'sys_category.uid, sys_category.title',
				'sys_category',
				'sys_category_record_mm',
				$table,
				' AND ' . $table . '.uid = ' . $uid .
				' AND sys_category_record_mm.tablenames = "' . $table . '"' .
				$enableFields,
				'',
				'sys_category_record_mm.sorting'
			);

			if ($GLOBALS['TYPO3_DB']->sql_num_rows($resCat)) {
				while (($cat = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resCat))) {
					$categoryData['uid_list'][] = $cat['uid'];
					$categoryData['title_list'][] = $cat['title'];
				}
			}
		}

		return $categoryData;
	}

	/**
	 * creates tags from category titles
	 * removes characters: # , space ( ) _
	 *
	 * @param string $tags comma-list of tags, new tags will be added to this
	 * @param array $categoryArray Array of Titles (eg. categories)
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 17.10.14
	 */
	public static function makeTags(&$tags, $categoryArray) {
		if (is_array($categoryArray) && count($categoryArray)) {
			$extConf = tx_kesearch_helper::getExtConf();

			foreach ($categoryArray as $catTitle) {
				$tag = $catTitle;
				$tag = str_replace('#', '', $tag);
				$tag = str_replace(',', '', $tag);
				$tag = str_replace(' ', '', $tag);
				$tag = str_replace('(', '', $tag);
				$tag = str_replace(')', '', $tag);
				$tag = str_replace('_', '', $tag);
				$tag = str_replace('&', '', $tag);

				if (!empty($tags)) {
					$tags .= ',';
				}
				$tags .= $extConf['prePostTagChar'] . $tag . $extConf['prePostTagChar'];
			}
		}
	}

	/**
	 * finds the system categories for $uid in $tablename, creates
	 * tags like "syscat123" ("syscat" + category uid).
	 *
	 * @param string $tags
	 * @param integer $uid
	 * @param string $tablename
	 * @author Christian Bülter <christian.buelter@inmedias.de>
	 * @since 24.09.15
	 */
	public static function makeSystemCategoryTags(&$tags, $uid, $tablename) {
			$categories = tx_kesearch_helper::getCategories($uid, $tablename);
			if (count($categories['uid_list'])) {
				foreach ($categories['uid_list'] as $category_uid) {
					tx_kesearch_helper::makeTags($tags, array('syscat' . $category_uid));
				}
			}
	}

	/**
	 * renders a link to a search result
	 *
	 * @param array $resultRow
	 * @param string $targetDefault
	 * @param string $targetFiles
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 31.10.14
	 * @return array
	 */
	public static function getResultLinkConfiguration($resultRow, $targetDefault='', $targetFiles='') {
		$linkconf = array();

		list($type) = explode(':', $resultRow['type']);

		switch($type) {

			case 'file':
				// render a link for files
				// if we use FAL, we can use the API
				if ($resultRow['orig_uid'] && ($fileObject = tx_kesearch_helper::getFile($resultRow['orig_uid']))) {
					$linkconf['parameter'] = $fileObject->getPublicUrl();
				} else {
					$linkconf['parameter'] = $resultRow['directory'] . rawurlencode($resultRow['title']);
				}
				$linkconf['fileTarget'] = $targetFiles;
				break;

			case 'external':
				// render a link for external results (provided by eg. ke_search_premium or tt_news)
				$linkconf['parameter'] = $resultRow['params'];
				$linkconf['useCacheHash'] = false;
				$linkconf['additionalParams'] = '';
				$extConfPremium = tx_kesearch_helper::getExtConfPremium();
				$linkconf['extTarget'] = $extConfPremium['apiExternalResultTarget'] ? $extConfPremium['apiExternalResultTarget'] : '_blank';
				break;

			default:
				// render a link for page targets
				// if params are filled, add them to the link generation process
				if (!empty($resultRow['params'])) {
					$linkconf['additionalParams'] = $resultRow['params'];
				}
				$linkconf['parameter'] = $resultRow['targetpid'];
				$linkconf['useCacheHash'] = true;
				$linkconf['target'] = $targetDefault;
				break;
		}

		return $linkconf;
	}

	/**
	 *
	 * @param  integer $uid
	 * @return File|NULL
	 */
	public static function getFile($uid) {

		try {
			$fileObject = ResourceFactory::getInstance()->getFileObject($uid);
		} catch (\TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException $e) {
			$fileObject = NULL;
		}

		return $fileObject;

	}
}
