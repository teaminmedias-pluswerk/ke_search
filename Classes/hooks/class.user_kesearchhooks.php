<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Stefan Froemken 
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
 * Hooks for ke_search
 *
 * @author Stefan Froemken 
 * @package	TYPO3
 * @subpackage	ke_search
 */

class user_kesearch_sortdate {

	/**
	 * @var tx_ttnews
	 */
	protected $ttNews;

	public function registerAdditionalFields(&$additionalFields) {
		$additionalFields[] = 'sortdate';
		$additionalFields[] = 'orig_uid';
		$additionalFields[] = 'orig_pid';
		$additionalFields[] = 'directory';
		$additionalFields[] = 'hash';
	}

	public function modifyPagesIndexEntry($uid, &$pageContent, &$tags, $cachedPageRecords, &$additionalFields) {
		// crdate is always given, but can be overwritten
		if(isset($cachedPageRecords[0][$uid]['crdate']) && $cachedPageRecords[0][$uid]['crdate'] > 0) {
			$additionalFields['sortdate'] = $cachedPageRecords[0][$uid]['crdate'];
		}
		// if TYPO3 sets last changed
		if(isset($cachedPageRecords[0][$uid]['SYS_LASTCHANGED']) && $cachedPageRecords[0][$uid]['SYS_LASTCHANGED'] > 0) {
			$additionalFields['sortdate'] = $cachedPageRecords[0][$uid]['SYS_LASTCHANGED'];
		}
		// if the user has manually set a date
		if(isset($cachedPageRecords[0][$uid]['lastUpdated']) && $cachedPageRecords[0][$uid]['lastUpdated'] > 0) {
			$additionalFields['sortdate'] = $cachedPageRecords[0][$uid]['lastUpdated'];
		}

		// fill orig_uid
		if(isset($cachedPageRecords[0][$uid]['uid']) && $cachedPageRecords[0][$uid]['uid'] > 0) {
			$additionalFields['orig_uid'] = $cachedPageRecords[0][$uid]['uid'];
		}
		// fill orig_pid
		if(isset($cachedPageRecords[0][$uid]['pid']) && $cachedPageRecords[0][$uid]['pid'] > 0) {
			$additionalFields['orig_pid'] = $cachedPageRecords[0][$uid]['pid'];
		}
	}

	public function modifyYACIndexEntry(&$title, &$abstract, &$fullContent, &$params, &$tags, $yacRecord, $targetPID, &$additionalFields) {
		// crdate is always given, but can be overwritten
		if(isset($yacRecord['crdate']) && $yacRecord['crdate'] > 0) {
			$additionalFields['sortdate'] = $yacRecord['crdate'];
		}
		// if TYPO3 sets last changed
		if(isset($yacRecord['starttime']) && $yacRecord['starttime'] > 0) {
			$additionalFields['sortdate'] = $yacRecord['starttime'];
		}

		// fill orig_uid
		if(isset($yacRecord['uid']) && $yacRecord['uid'] > 0) {
			$additionalFields['orig_uid'] = $yacRecord['uid'];
		}
		// fill orig_pid
		if(isset($yacRecord['pid']) && $yacRecord['pid'] > 0) {
			$additionalFields['orig_pid'] = $yacRecord['pid'];
		}
	}

	public function modifyDAMIndexEntry(&$title, &$abstract, &$fullContent, &$params, &$tags, $damRecord, $targetPID, &$clearTextTags, &$additionalFields) {
		// crdate is always given, but can be overwritten
		if(isset($damRecord['crdate']) && $damRecord['crdate'] > 0) {
			$additionalFields['sortdate'] = $damRecord['crdate'];
		}
		// if TYPO3 sets last changed
		if(isset($damRecord['file_ctime']) && $damRecord['file_ctime'] > 0) {
			$additionalFields['sortdate'] = $damRecord['file_ctime'];
		}
		// if TYPO3 sets last changed
		if(isset($damRecord['file_mtime']) && $damRecord['file_mtime'] > 0) {
			$additionalFields['sortdate'] = $damRecord['file_mtime'];
		}

		// fill orig_uid
		if(isset($damRecord['uid']) && $damRecord['uid'] > 0) {
			$additionalFields['orig_uid'] = $damRecord['uid'];
		}
		// fill orig_pid
		if(isset($damRecord['pid']) && $damRecord['pid'] > 0) {
			$additionalFields['orig_pid'] = $damRecord['pid'];
		}
	}

	public function modifyContentIndexEntry(&$title, &$contentRecord, &$tags, $contentUid, &$additionalFields) {
		// crdate is always given, but can be overwritten
		if(isset($contentRecord['crdate']) && $contentRecord['crdate'] > 0) {
			$additionalFields['sortdate'] = $contentRecord['crdate'];
		}
		// if TYPO3 sets last changed
		if(isset($contentRecord['tstamp']) && $contentRecord['tstamp'] > 0) {
			$additionalFields['sortdate'] = $contentRecord['tstamp'];
		}

		// fill orig_uid
		if(isset($contentRecord['uid']) && $contentRecord['uid'] > 0) {
			$additionalFields['orig_uid'] = $contentRecord['uid'];
		}
		// fill orig_pid
		if(isset($contentRecord['pid']) && $contentRecord['pid'] > 0) {
			$additionalFields['orig_pid'] = $contentRecord['pid'];
		}
	}

	public function modifyTemplaVoilaIndexEntry($uid, &$pageContent, &$tags, $cachedPageRecords, &$additionalFields) {
		// crdate is always given, but can be overwritten
		if(isset($cachedPageRecords[0][$uid]['crdate']) && $cachedPageRecords[0][$uid]['crdate'] > 0) {
			$additionalFields['sortdate'] = $cachedPageRecords[0][$uid]['crdate'];
		}
		// if TYPO3 sets last changed
		if(isset($cachedPageRecords[0][$uid]['SYS_LASTCHANGED']) && $cachedPageRecords[0][$uid]['SYS_LASTCHANGED'] > 0) {
			$additionalFields['sortdate'] = $cachedPageRecords[0][$uid]['SYS_LASTCHANGED'];
		}
		// if the user has manually set a date
		if(isset($cachedPageRecords[0][$uid]['lastUpdated']) && $cachedPageRecords[0][$uid]['lastUpdated'] > 0) {
			$additionalFields['sortdate'] = $cachedPageRecords[0][$uid]['lastUpdated'];
		}

		// fill orig_uid
		if(isset($cachedPageRecords[0][$uid]['uid']) && $cachedPageRecords[0][$uid]['uid'] > 0) {
			$additionalFields['orig_uid'] = $cachedPageRecords[0][$uid]['uid'];
		}
		// fill orig_pid
		if(isset($cachedPageRecords[0][$uid]['pid']) && $cachedPageRecords[0][$uid]['pid'] > 0) {
			$additionalFields['orig_pid'] = $cachedPageRecords[0][$uid]['pid'];
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/hooks/class.user_kesearchhooks.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/hooks/class.user_kesearchhooks.php']);
}
?>