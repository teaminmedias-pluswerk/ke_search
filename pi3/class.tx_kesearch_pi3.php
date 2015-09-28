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
 * Plugin 'Faceted search - searchbox and filters' for the 'ke_search' extension.
 *
 * @author	Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_pi3 extends tx_kesearch_lib {
	var $scriptRelPath      = 'pi1/class.tx_kesearch_pi1.php';	// Path to this script relative to the extension dir.

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content, $conf) {

		$this->ms = TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds();
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		$this->pi_USER_INT_obj = 1;	// Configuring so caching is not expected. This value means that no cHash params are ever set. We do this, because it's a USER_INT object!

		// initializes plugin configuration
		$this->init();

		// init XAJAX?
		if ($this->conf['renderMethod'] != 'static') {
			$xajaxIsLoaded = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax');
			if (!$xajaxIsLoaded) {
				return ('<span style="color: red;"><b>ke_search error:</b>"XAJAX" must be installed for this mode.</span>');
			}
			else $this->initXajax();
		}

		// hook for initials
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['initials'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['initials'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$_procObj->addInitials($this);
			}
		}

		// get templates
		$template['multiselect'] = $this->cObj->getSubpart($this->templateCode, '###SUB_FILTER_MULTISELECT###');
		$template['multihidden'] = $this->cObj->getSubpart($template['multiselect'], '###SUB_FILTER_MULTISELECT_HIDDEN###');
		$template['multifilter'] = $this->cObj->getSubpart($template['multiselect'], '###SUB_FILTER_MULTISELECT_FILTER###');
		$template['multioption'] = $this->cObj->getSubpart($template['multifilter'], '###SUB_FILTER_MULTISELECT_OPTION###');

		// get current filter
		$filters = $this->filters->getFilters();
		foreach($filters as $filter) {
			if($filter['target_pid'] == intval($GLOBALS['TSFE']->id)) {
				break;
			}
		}

		// hook for modifying content
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyMultiselectContent'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyMultiselectContent'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$content = $_procObj->modifyMultiselectContent($template['multiselect'], $filter, $this);
			}
		}
		if(is_array($filter) && count($filter)) {
			$contentOptions = '';
			$optionsAmountArray = $GLOBALS['TSFE']->fe_user->getKey('ses', 'ke_search.tagsInSearchResults');
			$countLoops = 1;
			if(is_array($this->piVars['filter'][$filter['uid']]) && count($this->piVars['filter'][$filter['uid']])) {
				$this->piVars['filter'][$filter['uid']] = array_unique($this->piVars['filter'][$filter['uid']]);
			}
			foreach($filter['options'] as $optionKey => $option) {
				$option['title'] = htmlspecialchars($option['title']);
				$option['tag'] = htmlspecialchars($option['tag']);
				if($optionsAmountArray[$option['tag']]) {
					$optionCounter = $optionsAmountArray[$option['tag']];
				} else $optionCounter = 0;
				$selected = ($this->piVars['filter'][$filter['uid']][$optionKey]) ? 'checked="checked"' : '';
				$markerArray['###ADDCLASS###'] = ($countLoops%3) ? '' : ' last';
				$markerArray['###FILTERNAME###'] = 'tx_kesearch_pi1[filter][' . $filter['uid'] . ']';
				$markerArray['###OPTIONID###'] = $option['uid'];
				$markerArray['###OPTIONKEY###'] = $optionKey;
				$markerArray['###OPTIONTITLE###'] = $option['title'] . ' (' . $optionCounter . ')';
				$markerArray['###OPTIONTAG###'] = $option['tag'];
				$markerArray['###SELECTED###'] = $selected;
				$countLoops++;
				$contentOptions .= $this->cObj->substituteMarkerArray($template['multioption'], $markerArray);
			}
			$content .= $this->cObj->substituteSubpart(
				$template['multifilter'],
				'###SUB_FILTER_MULTISELECT_OPTION###',
				$contentOptions
			);
			$content = $this->cObj->substituteMarker(
				$content,
				'###TITLE###',
				$filter['title']
			);
		}
		$content = $this->cObj->substituteSubpart(
			$template['multiselect'],
			'###SUB_FILTER_MULTISELECT_FILTER###',
			$content
		);
		$content = $this->cObj->substituteMarker(
			$content,
			'###FORM_ACTION###',
			$this->pi_getPageLink($this->conf['resultPage'])
		);
		$content = $this->cObj->substituteMarker(
			$content,
			'###SHOW_RESULTS###',
			$this->pi_getLL('show_results')
		);
		$content = $this->cObj->substituteMarker(
			$content,
			'###LINK_BACK###',
			$this->cObj->typoLink(
				$this->pi_getLL('back'),
				array(
					'parameter' => $this->conf['resultPage'],
					'addQueryString' => 1,
					'addQueryString.' => array(
						'exclude' => 'id'
					)
				)
			)
		);
		if(is_array($this->piVars['filter']) && count($this->piVars['filter'])) {
			foreach($this->piVars['filter'] as $filterKey => $filterValue) {
				if($filterKey == $filter['uid']) continue;
				foreach($this->piVars['filter'][$filterKey] as $optionKey => $option) {
					$hidden .= $this->cObj->substituteMarker($template['multihidden'], '###NAME###', 'tx_kesearch_pi1[filter][' . $filterKey . '][' . $optionKey . ']');
					$hidden = $this->cObj->substituteMarker($hidden, '###VALUE###', $option);
				}
			}
		}
		$content = $this->cObj->substituteSubpart($content, '###SUB_FILTER_MULTISELECT_HIDDEN###', $hidden);
		$content = $this->cObj->substituteMarker($content, '###PAGEID###', $this->conf['resultPage']);
		$content = $this->cObj->substituteMarker($content, '###SWORD###', htmlspecialchars($this->piVars['sword']));

		return $this->pi_wrapInBaseClass($content);
	}
}