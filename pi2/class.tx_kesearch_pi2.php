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
class tx_kesearch_pi2 extends tx_kesearch_lib {
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

		if($this->conf['resultPage'] != $GLOBALS['TSFE']->id) {
			$content = '<div id="textmessage">' . $this->pi_getLL('error_resultPage') . '</div>';
			return $this->pi_wrapInBaseClass($content);
		}

		// init template
		if ($this->conf['renderMethod'] == 'fluidtemplate') {
			$this->initFluidTemplate();
		} else {
			if (!$this->initMarkerTemplate()) {
				return ;
			}
		}

		// hook for initials
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['initials'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['initials'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$_procObj->addInitials($this);
			}
		}

		// fetch template code for marker based templating
		if ($this->conf['renderMethod'] != 'fluidtemplate') {
			$content = $this->cObj->getSubpart($this->templateCode, '###RESULT_LIST###');
		}

		// hook: modifyResultList (only valid for marker based templating, not for fluid based templating)
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyResultList'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyResultList'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$_procObj->modifyResultList($content, $this);
			}
		}

		// assign isEmptySearch to fluid templates
		$this->fluidTemplateVariables['isEmptySearch'] = $this->isEmptySearch;

		// if there's exclusive content do the rendering and stop here
		if ($this->conf['renderMethod'] != 'fluidtemplate') {
			$exclusiveContent = $this->renderExclusiveMarkerBasedContent();
			if ($exclusiveContent) {
				return $exclusiveContent;
			}
		}

		// render "no results"-message, "too short words"-message and finally the result list
		$resultList = $this->getSearchResults();
		$content = $this->cObj->substituteMarker($content, '###MESSAGE###', $resultList);

		// number of results
		$content = $this->cObj->substituteMarker($content, '###NUMBER_OF_RESULTS###', sprintf($this->pi_getLL('num_results'), $this->numberOfResults));
		$this->fluidTemplateVariables['numberofresults'] = $this->numberOfResults;

		// sorting, fluid template variables are filled in class tx_kesearch_lib_sorting
		$content = $this->cObj->substituteMarker($content, '###ORDERING###', $this->renderOrdering());

		// spinner and loading icon (does not apply to fluid template)
		if ($this->conf['renderMethod'] != 'fluidtemplate') {
			$subpart = $this->cObj->getSubpart($content, '###SHOW_SPINNER###');
			if($this->conf['renderMethod'] == 'static') {
				$content = $this->cObj->substituteSubpart($content, '###SHOW_SPINNER###', '');
			} else {
				$subpart = $this->cObj->substituteMarker($subpart, '###SPINNER###', $this->spinnerImageResults);
				$content = $this->cObj->substituteSubpart($content, '###SHOW_SPINNER###', $subpart);
			}
			$content = $this->cObj->substituteMarker($content, '###LOADING###', $this->pi_getLL('loading'));
		}

		// process query time
		$queryTime = (TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds() - $GLOBALS['TSFE']->register['ke_search_queryStartTime']);
		$this->fluidTemplateVariables['queryTime'] = $queryTime;
		$this->fluidTemplateVariables['queryTimeText'] = sprintf($this->pi_getLL('query_time'), $queryTime);
		if($this->conf['showQueryTime']) {
			$content = $this->cObj->substituteMarker($content, '###QUERY_TIME###', sprintf($this->pi_getLL('query_time'), $queryTime));
		} else {
			$content = $this->cObj->substituteMarker($content, '###QUERY_TIME###', '');
		}

		// render pagebrowser
		if ($GLOBALS['TSFE']->id == $this->conf['resultPage']) {
			if ($this->conf['pagebrowserOnTop'] || $this->conf['pagebrowserAtBottom']) {
				$pagebrowserContent = $this->renderPagebrowser();
			}
			if ($this->conf['pagebrowserOnTop']) {
				$content = $this->cObj->substituteMarker($content,'###PAGEBROWSER_TOP###', $pagebrowserContent);
			} else {
				$content = $this->cObj->substituteMarker($content,'###PAGEBROWSER_TOP###', '');
			}
			if ($this->conf['pagebrowserAtBottom']) {
				$content = $this->cObj->substituteMarker($content,'###PAGEBROWSER_BOTTOM###', $pagebrowserContent);
			} else {
				$content = $this->cObj->substituteMarker($content,'###PAGEBROWSER_BOTTOM###','');
			}
		}

		// generate HTML output (fluid or marker based templating)
		if ($this->conf['renderMethod'] == 'fluidtemplate') {
			$this->resultListView->assignMultiple($this->fluidTemplateVariables);
			$htmlOutput = $this->resultListView->render();
		} else {
			$htmlOutput = $this->pi_wrapInBaseClass($content);
		}

		return $htmlOutput;
	}

	/**
	 * inits the marker based template for pi2
	 *
	 */
	public function initMarkerTemplate() {
		// init XAJAX?
		if ($this->conf['renderMethod'] != 'static') {
			$xajaxIsLoaded = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('xajax');
			if (!$xajaxIsLoaded) return false;
			else $this->initXajax();
		}

		// Spinner Image
		if ($this->conf['spinnerImageFile']) {
			$spinnerSrc = $this->conf['spinnerImageFile'];
		} else {
			$spinnerSrc = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'res/img/spinner.gif';
		}
		$this->spinnerImageFilters = '<img id="kesearch_spinner_filters" src="'.$spinnerSrc.'" alt="'.$this->pi_getLL('loading').'" />';
		$this->spinnerImageResults = '<img id="kesearch_spinner_results" src="'.$spinnerSrc.'" alt="'.$this->pi_getLL('loading').'" />';

		// get javascript onclick actions
		$this->initOnclickActions();
	}

	/**
	 * inits the standalone fluid template
	 */
	public function initFluidTemplate() {
		/** @var \TYPO3\CMS\Fluid\View\StandaloneView $this->resultListView */
		$this->resultListView = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$this->resultListView->setPartialRootPath($this->conf['partialRootPath']);
		$this->resultListView->setLayoutRootPath($this->conf['layoutRootPath']);
		$this->resultListView->setTemplatePathAndFilename($this->conf['templateRootPath'] . 'ResultList.html');

		// make settings available in fluid template
		$this->resultListView->assign('conf', $this->conf);
		$this->resultListView->assign('extConf', $this->extConf);
		$this->resultListView->assign('extConfPremium', $this->extConfPremium);
	}

	/**
	 * renders marker based content which is exclusive, that means no other content
	 * will be created for the result list.
	 * Only valid for marker based (statc, ajax) template, not for fluid
	 *
	 * @return string
	 */
	public function renderExclusiveMarkerBasedContent() {

		// show text instead of results if no searchparams set and activated in ff
		if ($this->isEmptySearch && $this->conf['showTextInsteadOfResults']) {
			// Don't replace the following with substituteMarker
			// this is used to be valid against JavaScript calls
			$content = '<div id="textmessage">'.$this->pi_RTEcssText($this->conf['textForResults']).'</div>';
			$content .= '<div id="kesearch_results"></div>';
			$content .= '<div id="kesearch_updating_results"></div>';
			$content .= '<div id="kesearch_pagebrowser_top"></div>';
			$content .= '<div id="kesearch_pagebrowser_bottom"></div>';
			$content .= '<div id="kesearch_query_time"></div>';
			return $content;
		}

		if ($this->conf['renderMethod'] == 'ajax_after_reload') {
			$content = $this->cObj->substituteMarker($content,'###MESSAGE###', '');
			$content = $this->cObj->substituteMarker($content,'###QUERY_TIME###', '');
			$content = $this->cObj->substituteMarker($content,'###PAGEBROWSER_TOP###', '');
			$content = $this->cObj->substituteMarker($content,'###PAGEBROWSER_BOTTOM###', '');
			$content = $this->cObj->substituteMarker($content,'###NUMBER_OF_RESULTS###', '');
			$content = $this->cObj->substituteMarker($content,'###ORDERING###', '');
			$content = $this->cObj->substituteMarker($content,'###SPINNER###', '');
			$content = $this->cObj->substituteMarker($content,'###LOADING###', '');
			return $this->pi_wrapInBaseClass($content);
		}

		return $content;
	}

}
