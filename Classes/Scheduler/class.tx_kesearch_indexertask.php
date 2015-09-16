<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2011 Andreas Kiefer <kiefer@kennziffer.com>
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



// include indexer class and create a "baseclass" (only for backwards compatibility)
if (TYPO3_VERSION_INTEGER >= 6002000) {
	require_once(TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ke_search') . 'Classes/indexer/class.tx_kesearch_indexer.php');
	abstract class tx_kesearch_indexertask_baseclass extends \TYPO3\CMS\Scheduler\Task\AbstractTask { }
} else {
	require_once(t3lib_extMgm::extPath('ke_search') . 'Classes/indexer/class.tx_kesearch_indexer.php');
	abstract class tx_kesearch_indexertask_baseclass extends tx_scheduler_Task { }
}

class tx_kesearch_indexertask extends tx_kesearch_indexertask_baseclass {

	public function execute() {

		// get extension configuration
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ke_search']);

		// make indexer instance
		if (TYPO3_VERSION_INTEGER >= 6002000) {
			$indexer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_indexer');
		} else {
			$indexer = t3lib_div::makeInstance('tx_kesearch_indexer');
		}

		// process
		$response = $indexer->startIndexing(true, $this->extConf, 'CLI');

		return true;

	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/Scheduler/class.tx_kesearch_indexertask.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/Scheduler/class.tx_kesearch_indexertask.php']);
}
?>