<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Andreas Kiefer 
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
 * ************************************************************* */

/**
 * Class that adds the wizard icon.
 *
 * @author	Andreas Kiefer
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_pi1_wizicon {

	/**
	 * Processing the wizard items array
	 *
	 * @param	array		$wizardItems: The wizard items
	 * @return	Modified array with wizard items
	 */
	function proc($wizardItems) {
		global $LANG;

		$LL = $this->includeLocalLang();

		if (TYPO3_VERSION_INTEGER < 6002000) {
			$extRelPath = t3lib_extMgm::extRelPath('ke_search');
		} else {
			$extRelPath = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('ke_search');
		}

		$wizardItems['plugins_tx_kesearch_pi1'] = array(
		    'icon' =>  $extRelPath . 'pi1/ce_wiz.gif',
		    'title' => $LANG->getLLL('pi_title', $LL),
		    'description' => $LANG->getLLL('pi_plus_wiz_description', $LL),
		    'params' => '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=ke_search_pi1'
		);

		return $wizardItems;
	}

	/**
	 * Reads the [extDir]/locallang.xml and returns the \$LOCAL_LANG array found in that file.
	 *
	 * @return	The array with language labels
	 */
	function includeLocalLang() {
		if (TYPO3_VERSION_INTEGER < 6002000) {
			$llFile = t3lib_extMgm::extPath('ke_search') . 'pi1/locallang.xml';
		} else {
			$llFile = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ke_search') . 'pi1/locallang.xml';
		}

		if (TYPO3_VERSION_INTEGER >= 4006000) {
			if (TYPO3_VERSION_INTEGER >= 7000000) {
				$xmlParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Localization\\Parser\\LocallangXmlParser');
			} else if (TYPO3_VERSION_INTEGER >= 6002000) {
				$xmlParser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_l10n_parser_Llxml');
			} else {
				$xmlParser = t3lib_div::makeInstance('t3lib_l10n_parser_Llxml');
			}
			$LOCAL_LANG = $xmlParser->getParsedData($llFile, $GLOBALS['LANG']->lang);
		} else {
			$LOCAL_LANG = t3lib_div::readLLXMLfile($llFile, $GLOBALS['LANG']->lang);
		}
		return $LOCAL_LANG;
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/pi1/class.tx_kesearch_pi1_wizicon.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/pi1/class.tx_kesearch_pi1_wizicon.php']);
}
?>