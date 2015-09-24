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
 * Parent class for plugins pi1 and pi2
 *
 * @author	Andreas Kiefer
 * @author	Stefan Froemken
 * @author	Christian Bülter
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_lib extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin {
	var $prefixId            = 'tx_kesearch_pi1';		// Same as class name
	var $extKey              = 'ke_search';	// The extension key.

	var $sword               = ''; // cleaned searchword (karl-heinz => karl heinz)
	var $swords              = ''; // searchwords as array
	var $wordsAgainst        = ''; // searchphrase for boolean mode (+karl* +heinz*)
	var $tagsAgainst         = ''; // tagsphrase for boolean mode (+#category_213# +#city_42#)
	var $scoreAgainst        = ''; // searchphrase for score/non boolean mode (karl heinz)
	var $isEmptySearch       = true; // true if no searchparams given; otherwise false

	var $templateFile        = ''; // Template file
	var $templateCode        = ''; // content of template file

	var $startingPoints      = 0; // comma seperated list of startingPoints
	var $firstStartingPoint  = 0; // first entry in list of startingpoints
	var $conf                = array(); // FlexForm-Configuration
	var $extConf             = array(); // Extension-Configuration
	var $extConfPremium      = array(); // Extension-Configuration of ke_search_premium if installed
	var $numberOfResults     = 0; // count search results
	var $indexToUse          = ''; // it's for 'USE INDEX ($indexToUse)' to speed up queries
	var $tagsInSearchResult  = false; // contains all tags of current search result
	var $preselectedFilter   = array(); // preselected filters by flexform
	var $filtersFromFlexform = array(); // array with filter-uids as key and whole data as value
	var $hasTooShortWords    = false; // contains a boolean value which represents if there are too short words in the searchstring
	var $fileTypesWithPreviewPossible = array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff');
	var $fluidTemplateVariables = array();

 	/**
	 * @var tx_xajax
	 */
	var $xajax;

	/**
	 * @var tx_kesearch_db
	 */
	var $db;

	/**
	 * @var tx_kesearch_lib_div
	 */
	var $div;

	/**
	 * @var user_kesearchpremium
	 */
	var $user_kesearchpremium;

	/**
	 * @var tx_kesearch_lib_searchresult
	 */
	var $searchResult;

	/**
	 * @var tx_kesearch_filters
	 */
	var $filters;

	/**
	 * Initializes flexform, conf vars and some more
	 *
	 * @return nothing
	 */
	public function init() {
		// get some helper functions
		$this->div = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_lib_div', $this);

		// set start of query timer
		if(!$GLOBALS['TSFE']->register['ke_search_queryStartTime']) $GLOBALS['TSFE']->register['ke_search_queryStartTime'] = TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds();

		// make settings from flexform available in general configuration ($this->conf)
		$this->moveFlexFormDataToConf();

		// in pi2 (the list plugin) fetch the configuration from pi1 (the search
		// box plugin) since all the configuration is done there
		if(!empty($this->conf['loadFlexformsFromOtherCE'])) {
			$data = $this->pi_getRecord('tt_content', intval($this->conf['loadFlexformsFromOtherCE']));
			$this->cObj->data = $data;
			$this->moveFlexFormDataToConf();
		}

		// clean piVars
		$this->piVars = $this->div->cleanPiVars($this->piVars);

		// get preselected filter from rootline
		$this->getFilterPreselect();

		// add stdWrap properties to each config value
		foreach($this->conf as $key => $value) {
			$this->conf[$key] = $this->cObj->stdWrap($value, $this->conf[$key . '.']);
		}

		// set some default values (this part have to be after stdWrap!!!)
		if(!$this->conf['resultPage']) $this->conf['resultPage'] = $GLOBALS['TSFE']->id;
		if(!isset($this->piVars['page'])) $this->piVars['page'] = 1;
		if(!empty($this->conf['additionalPathForTypeIcons'])) {
			$this->conf['additionalPathForTypeIcons'] = rtrim($this->conf['additionalPathForTypeIcons'], '/') . '/';
		}

		// hook: modifyFlexFormData
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFlexFormData'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFlexFormData'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$_procObj->modifyFlexFormData($this->conf, $this->cObj, $this->piVars);
			}
		}

		// prepare database object
		$this->db = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_db', $this);

		// set startingPoints
		$this->startingPoints = $this->div->getStartingPoint();

		// get filter class
		$this->filters = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_filters');

		// get extension configuration array
		$this->extConf = tx_kesearch_helper::getExtConf();
		$this->extConfPremium = tx_kesearch_helper::getExtConfPremium();

		// initialize filters
		$this->filters->initialize($this);

		// init templating (marker based or fluid)
		$this->initTemplate();

		// get first startingpoint
		$this->firstStartingPoint = $this->div->getFirstStartingPoint($this->startingPoints);

		// build words searchphrase
		$searchPhrase = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_lib_searchphrase');
		$searchPhrase->initialize($this);
		$searchWordInformation = $searchPhrase->buildSearchPhrase();

		// Hook: modifySearchWords
		if(isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifySearchWords'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifySearchWords'] as $classRef) {
				$hookObj = TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classRef);
				if(method_exists($hookObj, 'modifySearchWords')) {
					$hookObj->modifySearchWords($searchWordInformation, $this);
				}
			}
		}

		// set searchword and tag information
		$this->sword = $searchWordInformation['sword'];
		$this->swords = $searchWordInformation['swords'];
		$this->wordsAgainst = $searchWordInformation['wordsAgainst'];
		$this->tagsAgainst = $searchWordInformation['tagsAgainst'];
		$this->scoreAgainst = $searchWordInformation['scoreAgainst'];

		$this->isEmptySearch = $this->isEmptySearch();

		// Since sorting for "relevance" in most cases ist the most useful option and
		// this sorting option is not available until a searchword is given, make it
		// the default sorting after a searchword has been given.
		// Set default sorting to "relevance" if the following conditions are true:
		// * sorting by user is allowed
		// * sorting for "relevance" is allowed (internal: "score")
		// * user did not select his own sorting yet
		// * a searchword is given
		$isInList = TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->conf['sortByVisitor'], 'score');
		if ($this->conf['showSortInFrontend'] && $isInList && !$this->piVars['sortByField'] && $this->sword) {
			$this->piVars['sortByField'] = 'score';
			$this->piVars['sortByDir'] = 'desc';
		}

		// after the searchword is removed, sorting for "score" is not possible
		// anymore. So remove this sorting here and put it back to default.
		if (!$this->sword && $this->piVars['sortByField'] == 'score') {
			unset($this->piVars['sortByField']);
			unset($this->piVars['sortByDir']);
		}

		// chooseBestIndex is only needed for MySQL-Search. Not for Sphinx
		if (!$this->extConfPremium['enableSphinxSearch']) {
			// precount results to find the best index
			$this->db->chooseBestIndex($this->wordsAgainst, $this->tagsAgainst);
		}

		// perform search at this point already if we need to calculate what
		// filters to display.
		if ($this->conf['checkFilterCondition'] != 'none') {
			$this->db->getSearchResults();
		}

		// add cssTag to header if set
		$cssFile = $GLOBALS['TSFE']->tmpl->getFileName($this->conf['cssFile']);
		if(!empty($cssFile)) {
			$cssTag = $this->cObj->wrap($cssFile, '<link rel="stylesheet" type="text/css" href="|" />');
			$GLOBALS['TSFE']->getPageRenderer()->addCssFile($cssFile);
		}
	}

	/**
	 *
	 * initializes the marker based or fluid based template
	 */
	public function initTemplate() {
		// check for rendering method
		if ($this->conf['renderMethod'] == 'fluidtemplate') {
			// set default template paths
			$this->conf['templateRootPath'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->conf['templateRootPath'] ? $this->conf['templateRootPath'] : 'EXT:ke_search/Resources/Private/Templates/');
			$this->conf['partialRootPath'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->conf['partialRootPath'] ? $this->conf['partialRootPath'] : 'EXT:ke_search/Resources/Private/Partials/');
			$this->conf['layoutRootPath'] = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->conf['layoutRootPath'] ? $this->conf['layoutRootPath'] : 'EXT:ke_search/Resources/Private/Layouts/');
		} else {
			// get html template
			$this->templateFile = $this->conf['templateFile'] ? $this->conf['templateFile'] : TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'res/template_pi1.tpl';
			$this->templateCode = $this->cObj->fileResource($this->templateFile);
		}
	}

	/**
	 * Move all FlexForm data of current record to conf array
	 */
	public function moveFlexFormDataToConf() {
		// don't move this to init
		$this->pi_initPIflexForm();

		$piFlexForm = $this->cObj->data['pi_flexform'];
		if(is_array($piFlexForm['data'])) {
			foreach($piFlexForm['data'] as $sheetKey => $sheet) {
				foreach($sheet as $lang) {
					foreach($lang as $key => $value) {
						// delete current conf value from conf-array when FF-Value differs from TS-Conf and FF-Value is not empty
						$value = $this->fetchConfigurationValue($key, $sheetKey);
						if($this->conf[$key] != $value && !empty($value)) {
							unset($this->conf[$key]);
							$this->conf[$key] = $this->fetchConfigurationValue($key, $sheetKey);
						}
					}
				}
			}
		}
	}

	/*
	 * function initOnclickActions
	 */
	public function initOnclickActions() {

		switch ($this->conf['renderMethod']) {

			// AJAX after reload version
			case 'ajax_after_reload':

				// set pagebrowser onclick
				$this->onclickPagebrowser = 'pagebrowserAction(); ';

				// $this->onclickFilter = 'this.form.submit();';
				$this->onclickFilter = 'document.getElementById(\'pagenumber\').value=1; document.getElementById(\'xajax_form_kesearch_pi1\').submit();';

				break;

			// STATIC version
			case 'static':
				return;
				break;
		}
	}

	/**
	 * creates the searchbox
	 * 1. fills the marker for marker based templating and renders the searchbox
	 * 2. fills fluid variables for fluid based templating to $this->fluidTemplateVariables
	 *
	 * @return string rendered searchbox (for static or ajax templating, not for fluid templating)
	 */
	public function getSearchboxContent() {

		// get main template code
		$content = $this->cObj->getSubpart($this->templateCode,'###SEARCHBOX_STATIC###');

		// set page = 1 if not set yet or if we are in static mode
		if (!$this->piVars['page'] || $this->conf['renderMethod'] == 'static' || $this->conf['renderMethod'] == "fluidtemplate") {
			$pageValue = 1;
		} else {
			$pageValue = $this->piVars['page'];
		}
		$content = $this->cObj->substituteMarker($content,'###HIDDEN_PAGE_VALUE###', $pageValue);
		$this->fluidTemplateVariables['page'] = $pageValue;

		// submit
		$content = $this->cObj->substituteMarker($content,'###SUBMIT_VALUE###', $this->pi_getLL('submit'));
		$this->fluidTemplateVariables['submitAltText'] = $this->pi_getLL('submit');

		// searchword input value
		$searchString = $this->piVars['sword'];

		if(!empty($searchString) && $searchString != $this->pi_getLL('searchbox_default_value')) {
			$this->swordValue = $searchString;
			$searchboxFocusJS = '';
			$searchboxBlurJS = '';
		} else {
			$this->swordValue = $this->pi_getLL('searchbox_default_value');

			// set javascript for resetting searchbox value
			$searchboxFocusJS = 'searchboxFocus(this);';
			$searchboxBlurJS = 'searchboxBlur(this);';
		}

		$content = $this->cObj->substituteMarker($content,'###SWORD_VALUE###', htmlspecialchars($this->swordValue));
		$this->fluidTemplateVariables['searchword'] = htmlspecialchars($this->swordValue);

		$content = $this->cObj->substituteMarker($content,'###SEARCHBOX_DEFAULT_VALUE###', htmlspecialchars($this->pi_getLL('searchbox_default_value')));
		$this->fluidTemplateVariables['searchwordDefault'] = htmlspecialchars($this->pi_getLL('searchbox_default_value'));

		$content = $this->cObj->substituteMarker($content,'###SWORD_ONFOCUS###', $searchboxFocusJS);

		$content = $this->cObj->substituteMarker($content,'###SWORD_ONBLUR###', $searchboxBlurJS);

		$content = $this->cObj->substituteMarker($content,'###SORTBYFIELD###', $this->piVars['sortByField']);
		$this->fluidTemplateVariables['sortByField'] = $this->piVars['sortByField'];

		$content = $this->cObj->substituteMarker($content,'###SORTBYDIR###', $this->piVars['sortByDir']);
		$this->fluidTemplateVariables['sortByDir'] = $this->piVars['sortByDir'];

		// set onsubmit action
		if ($this->conf['renderMethod'] != 'static') {
			$onSubmitMarker = 'onsubmit="document.getElementById(\'pagenumber\').value=1;"';
		} else {
			$onSubmitMarker = '';
		}
		$content = $this->cObj->substituteMarker($content,'###ONSUBMIT###', $onSubmitMarker);

		// get filters
		$renderedFilters = $this->renderFilters();
		$content = $this->cObj->substituteMarker($content, '###FILTER###', $renderedFilters);
		$this->fluidTemplateVariables['filter'] = $renderedFilters;

		// set form action pid
		$content = $this->cObj->substituteMarker($content,'###FORM_TARGET_PID###', $this->conf['resultPage']);
		$this->fluidTemplateVariables['targetpage'] = $this->conf['resultPage'];

		// set form action
		$siteUrl = TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
		$lParam = TYPO3\CMS\Core\Utility\GeneralUtility::_GET('L');
		$mpParam = TYPO3\CMS\Core\Utility\GeneralUtility::_GET('MP');
		$typeParam = TYPO3\CMS\Core\Utility\GeneralUtility::_GP('type');
		$actionUrl =  $siteUrl . 'index.php';
		$content = $this->cObj->substituteMarker($content,'###FORM_ACTION###', $actionUrl);
		$this->fluidTemplateVariables['actionUrl'] = $actionUrl;

		// set other hidden fields
		$hiddenFieldsContent = '';

		// language parameter
		if (isset($lParam)) {
			$hiddenFieldValue = intval($lParam);
			$hiddenFieldsContent .= '<input type="hidden" name="L" value="'.$hiddenFieldValue.'" />';
			$this->fluidTemplateVariables['lparam'] = $hiddenFieldValue;
		}

		// mountpoint parameter
		if (isset($mpParam)) {
			// the only allowed characters in the MP parameter are digits and , and -
			$hiddenFieldValue = preg_replace('/[^0-9,-]/', '', $mpParam);
			$hiddenFieldsContent .= '<input type="hidden" name="MP" value="'.$hiddenFieldValue.'" />';
			$this->fluidTemplateVariables['mpparam'] = $hiddenFieldValue;
		}
		$content = $this->cObj->substituteMarker($content,'###HIDDENFIELDS###', $hiddenFieldsContent);

		// type param
		if ($typeParam) {
			$hiddenFieldValue = intval($typeParam);
			$typeContent = $this->cObj->getSubpart($this->templateCode,'###SUB_PAGETYPE###');
			$typeContent = $this->cObj->substituteMarker($typeContent,'###PAGETYPE###',$typeParam);
			$this->fluidTemplateVariables['typeparam'] = $hiddenFieldValue;
		} else $typeContent = '';
		$content = $this->cObj->substituteSubpart ($content, '###SUB_PAGETYPE###', $typeContent, $recursive=1);

		// add submit button in static mode
		if ($this->conf['renderMethod'] == 'static') {
			$submitButton = '<input type="submit" value="' . $this->pi_getLL('submit') . '" />';
		} else {
			$submitButton = '';
		}
		$content = $this->cObj->substituteMarker($content,'###SUBMIT###',$submitButton);

		// set reset link
		unset($linkconf);
		$linkconf['parameter'] = $this->conf['resultPage'];
		$resetUrl = $this->cObj->typoLink_URL($linkconf);
		$this->fluidTemplateVariables['resetUrl'] = $resetUrl;
		$resetLink = '<a href="' . $resetUrl . '" class="resetButton"><span>' . $this->pi_getLL('reset_button') . '</span></a>';
		$content = $this->cObj->substituteMarker($content,'###RESET###',$resetLink);

		// init onDomReadyAction
		$this->initDomReadyAction();

		return $content;
	}

	/**
	 * loop through all available filters and compile the values for the fluid template rendering
	 *
	 */
	public function renderFilters() {
		foreach ($this->filters->getFilters() as $filter) {

			// if the current filter is a "hidden filter", skip
			// rendering of this filter. The filter is only used
			// to add preselected filter options to the query and
			// must not be rendered.
			$isInList = TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->conf['hiddenfilters'], $filter['uid']);

			if ($isInList) {
				continue;
			}

			// get filter options which should be displayed
			$options = $this->findFilterOptionsToDisplay($filter);

			// alphabetical sorting of filter options
			if ($filter['alphabeticalsorting'] == 1) {
				$this->sortArrayByColumn($options, 'title');
			}

			// hook for modifying filter options
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilterOptionsArray'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilterOptionsArray'] as $_classRef) {
					$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
					$options = $_procObj->modifyFilterOptionsArray($filter['uid'], $options, $this);
				}
			}

			// build link to reset this filter while keeping the others
			unset($linkconf);
			$linkconf['parameter'] = $GLOBALS['TSFE']->id;
			$linkconf['additionalParams'] = '&tx_kesearch_pi1[sword]='.$this->piVars['sword'];
			$linkconf['additionalParams'] .= '&tx_kesearch_pi1[filter][' . $filter['uid'] . ']=';
			if (is_array($this->piVars['filter']) && count($this->piVars['filter'])) {
				foreach ($this->piVars['filter'] as $key => $value) {
					if ($key != $filter['uid']) {
						$linkconf['additionalParams'] .= '&tx_kesearch_pi1[filter][' . $key . ']=' . $value;
					}
				}
			}
			$resetLink = $this->cObj->typoLink_URL($linkconf);

			// set values for fluid template
			$filterData = $filter;
			$filterData['name'] = 'tx_kesearch_pi1[filter][' . $filter['uid'] . ']';
			$filterData['id'] = 'filter_' . $filter['uid'];
			$filterData['options'] = $options;
			$filterData['checkboxOptions'] = $this->compileCheckboxOptions($filter, $options);
			$filterData['optionCount'] = count($options);
			$filterData['resetLink'] = $resetLink;

			// special classes / custom code
			switch($filter['rendertype']) {
				case 'textlinks':
					$textLinkObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_lib_filters_textlinks', $this);
					$textLinkObj->renderTextlinks($filter['uid'], $options, $this);
					break;

				// use custom code for filter rendering
				default:
					// hook for custom filter renderer
					if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['customFilterRenderer'])) {
						foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['customFilterRenderer'] as $_classRef) {
							$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
							$_procObj->customFilterRenderer($filter['uid'], $options, $this);
						}
					}
					break;
			}


			// add values to fluid template
			$this->fluidTemplateVariables['filters'][] = $filterData;
		}
	}

	/**
	 * compiles a list of checkbox records
	 *
	 * @param $filterUid UID of the filter for which we need the checkboxes
	 * @param $options contains all options which are found in the search result
	 * @return $array list of checkboxes records
	 */
	public function compileCheckboxOptions($filter, $options) {
		$allOptionsOfCurrentFilter = $filter['options'];

		// alphabetical sorting of filter options
		if ($filter['alphabeticalsorting'] == 1) {
			$this->sortArrayByColumn($allOptionsOfCurrentFilter, 'title');
		}

		// loop through options
		$checkboxOptions = array();
		if(is_array($allOptionsOfCurrentFilter)) {
			foreach($allOptionsOfCurrentFilter as $key => $data) {
				$data['key'] = $key;
				$isOptionInOptionArray = FALSE;

				// check if current option (of searchresults) is in array of all possible options
				$isOptionInOptionArray = 0;
				if (is_array($options)) {
					foreach($options as $optionInResultList) {
						if ($optionInResultList['value'] == $data['tag']) {
							$isOptionInOptionArray = TRUE;
							$data['results'] = $optionInResultList['results'];
							break;
						}
					}
				}

				// if option is in optionArray, we have to mark the checkboxes
				if ($isOptionInOptionArray) {
					// if user has selected a checkbox it must be selected on the resultpage, too.
					// options which have been preselected in the backend are already in $this->piVars['filter'][$filter['uid]]
					if($this->piVars['filter'][$filter['uid']][$key]) {
						$data['selected'] = 1;
					}

					// mark all checkboxes if that config options is set and no search string was given and there
					// are no preselected filters given for that filter
					if($this->isEmptySearch && $filter['markAllCheckboxes'] && empty($this->preselectedFilter[$filter['uid']])) {
						$data['selected'] = 1;
					}

				} else { // if an option was not found in the search results
					$data['disabled'] = 1;
				}

				$data['id'] = 'filter_' . $filter['uid'] . '_' . $key;
				$checkboxOptions[] = $data;
			}
		}

		// modify filter options by hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilterOptions'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilterOptions'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$contentOptions .= $_procObj->modifyFilterOptions(
					$filter,
					$checkboxOptions,
					$this
				);
			}
		}

		return $checkboxOptions;
	}

	/**
	 * find out which filter options should be displayed for the given filter
	 * check filter options availability and preselection status
	 *
	 * @param array $filter
	 * @return array
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 09.09.14
	 */
	public function findFilterOptionsToDisplay($filter) {
		$options = array();

		foreach ($filter['options'] as $option) {

			// should we check if the filter option is available in
			// the current search result?
			if ($this->conf['checkFilterCondition'] != 'none') {

				// Once one filter option has been selected, don't display the
				// others anymore since this leads to a strange behaviour (options are
				// only displayed if they have BOTH tags: the selected and the other filter option.
				if ((!count($filter['selectedOptions']) || in_array($option['uid'], $filter['selectedOptions'])) && $this->filters->checkIfTagMatchesRecords($option['tag'])) {

					// build link which selects this option and keeps all the other selected filters
					unset($linkconf);
					$linkconf['parameter'] = $GLOBALS['TSFE']->id;
					$linkconf['additionalParams'] = '&tx_kesearch_pi1[sword]=' . $this->piVars['sword'] . '&tx_kesearch_pi1[filter][' . $filter['uid'] . ']=' . $option['tag'];
					$linkconf['useCacheHash'] = false;
					if (is_array($this->piVars['filter']) && count($this->piVars['filter'])) {
						foreach ($this->piVars['filter'] as $key => $value) {
							if ($key != $filter['uid']) {
								$linkconf['additionalParams'] .= '&tx_kesearch_pi1[filter][' . $key . ']=' . $value;
							}
						}
					}
					$optionLink = $this->cObj->typoLink_URL($linkconf);

					$options[$option['uid']] = array(
						'title' => $option['title'],
						'value' => $option['tag'],
						'results' => $this->tagsInSearchResult[$option['tag']],
						'selected' => is_array($filter['selectedOptions']) && in_array($option['uid'], $filter['selectedOptions']),
						'link' => $optionLink
					);
				}
			} else {
				// do not process any checks; show all filter options
				$options[$option['uid']] = array(
					'title' => $option['title'],
					'value' => $option['tag'],
					'selected' => is_array($filter['selectedOptions']) && !empty($filter['selectedOptions']) && in_array($option['uid'], $filter['selectedOptions']),
				);
			}
		}

		return $options;
	}

	/**
	 * renders brackets around the number of results, returns an empty
	 * string if there are no results or if an option for this filter already
	 * has been selected.
	 *
	 * @param integer $numberOfResults
	 * @param array $filter
	 * @return string
	 */
	public function renderNumberOfResultsString($numberOfResults, $filter) {
		if ($filter['shownumberofresults'] && !count($filter['selectedOptions']) && $numberOfResults) {
			$returnValue = ' (' . $numberOfResults . ')';
		} else {
			$returnValue = '';
		}
		return $returnValue;
	}

	/**
	 * get all filters configured in FlexForm
	 *
	 * @return array Array with filter UIDs
	 */
	public function getFiltersFromFlexform() {
		if(!empty($this->conf['filters']) && count($this->filtersFromFlexform) == 0) {
			$fields = '*';
			$table = 'tx_kesearch_filters';
			$where = 'pid in ('.$GLOBALS['TYPO3_DB']->quoteStr($this->startingPoints, $table).')';
			$where .= ' AND uid in ('.$GLOBALS['TYPO3_DB']->quoteStr($this->conf['filters'], 'tx_kesearch_filters').')';
			$where .= $this->cObj->enableFields($table);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where);
			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// Perform overlay on each record
				if(is_array($row) && $GLOBALS['TSFE']->sys_language_contentOL) {
					$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay(
						'tx_kesearch_filters',
						$row,
						$GLOBALS['TSFE']->sys_language_content,
						$GLOBALS['TSFE']->sys_language_contentOL
					);
				}
				$this->filtersFromFlexform[$row['uid']] = $row;
			}
		}
		return $this->filtersFromFlexform;
	}

	/**
	 * get optionrecords of given list of option-IDs
	 *
	 * @param string $optionList
	 * @param boolean $returnSortedByTitle Default: Sort by the exact order as they appear in optionlist. This is usefull if the customer want's the same ordering as in the filterRecord (inline)
	 * @return array Filteroptions
	 */
	public function getFilterOptions($optionList, $returnSortedByTitle = false) {
		// check/convert if list contains only integers
		$optionIdArray = TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $optionList, true);
		$optionList = implode(',', $optionIdArray);
		if($returnSortedByTitle) {
			$sortBy = 'title';
		} else $sortBy = 'FIND_IN_SET(uid, "' . $GLOBALS['TYPO3_DB']->quoteStr($optionList, 'tx_kesearch_filteroptions') . '")';

		// search for filteroptions
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'tx_kesearch_filteroptions',
			'pid in ('.$this->startingPoints.') ' .
			'AND FIND_IN_SET(uid, "' . $GLOBALS['TYPO3_DB']->quoteStr($optionList, 'tx_kesearch_filteroptions') . '") ' .
			$this->cObj->enableFields('tx_kesearch_filteroptions'),
			'', $sortBy, ''
		);
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// Perform overlay on each record
			if(is_array($row) && $GLOBALS['TSFE']->sys_language_contentOL) {
				$row = $GLOBALS['TSFE']->sys_page->getRecordOverlay(
					'tx_kesearch_filteroptions',
					$row,
					$GLOBALS['TSFE']->sys_language_content,
					$GLOBALS['TSFE']->sys_language_contentOL
				);
			}
			$optionArray[$row['uid']] = $row;
		}

		return $optionArray;
	}


	/**
	 * Init XAJAX
	 */
	public function initXajax()	{
		// Include xaJax
		if(!class_exists('xajax')) {
			$path_to_xajax = t3lib_extMgm::extPath('xajax') . 'class.tx_xajax.php';
			require_once($path_to_xajax);
		}
		// Make the instance
		$this->xajax = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_xajax');
		// Decode form vars from utf8
		$this->xajax->decodeUTF8InputOn();
		// Encoding of the response to utf-8.
		$this->xajax->setCharEncoding('utf-8');
		// $this->xajax->setCharEncoding('iso-8859-1');
		// To prevent conflicts, prepend the extension prefix.
		$this->xajax->setWrapperPrefix($this->prefixId);
		// Do you want messages in the status bar?
		$this->xajax->statusMessagesOn();
		// Turn only on during testing
		// $this->xajax->debugOn();

		// Register the names of the PHP functions you want to be able to call through xajax
		$this->xajax->registerFunction(array('refresh', &$this, 'refresh'));
		if ($this->conf['renderMethod'] != 'static') {
			$this->xajax->registerFunction(array('refreshFiltersOnLoad', &$this, 'refreshFiltersOnLoad'));
		}
		// $this->xajax->registerFunction(array('resetSearchbox', &$this, 'resetSearchbox'));

		// If this is an xajax request call our registered function, send output and exit
		$this->xajax->processRequests();

		// Create javacript and add it to the normal output
		$jsCode = $this->xajax->getJavascript(t3lib_extMgm::siteRelPath('xajax'));
		$GLOBALS['TSFE']->getPageRenderer()->addHeaderData($jsCode);
	}


	/**
	 * create hide spinner img-tag
	 * this is needed to make results and filters visible in AJAX-Mode
	 *
	 * @return string HTML IMG-Tag
	 */
	public function createHideSpinner() {
		// generate onload image
		$path = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'res/img/blank.gif';
		if ($GLOBALS['TSFE']->id != $this->conf['resultPage']) {
			$spinnerFunction = 'hideSpinnerFiltersOnly()';
		} else $spinnerFunction = 'hideSpinner()';
		return $this->cObj->fileResource($path, 'onload="' . $spinnerFunction . ';" alt="" title=""');
	}


	/**
	 * This function will be called from AJAX directly, so this must be public
	 *
	 * @param $data
	 */
	public function refresh($data) {
		// initializes plugin configuration
		$this->init();

			// set pivars
		foreach($data[$this->prefixId] as $key => $value) {
			if(is_array($data[$this->prefixId][$key])) {
				foreach($data[$this->prefixId][$key] as $subkey => $subtag)  {
					$this->piVars[$key][$subkey] = $subtag;
				}
			} else {
				$this->piVars[$key] = $value;
			}
		}

		// create a list of all filters in piVars
		if (is_array($this->piVars['filter'])) {
			foreach($this->piVars['filter'] as $key => $value) {
				if(is_array($this->piVars['filter'][$key])) {
					$filterString .= implode($this->piVars['filter'][$key]);
				} else {
					$filterString .= $this->piVars['filter'][$key];
				}
			}
		}

		// generate onload image
		$this->onloadImage = $this->createHideSpinner();

		// init javascript onclick actions
		$this->initOnclickActions();

		// reset filters?
		if ($this->piVars['resetFilters'] && is_array($this->piVars['filter'])) {
			foreach ($this->piVars['filter'] as $key => $value) {
				// do not reset the preselected filters
				if ($this->preselectedFilter[$key]) {
					$this->piVars['filter'][$key] = $this->preselectedFilter[$key];
				}
			}
		}

		// make xajax response object
		$objResponse = new tx_xajax_response();

		if(!$filterString && !$this->piVars['sword'] && $this->conf['showTextInsteadOfResults']) {
			$objResponse->addAssign('kesearch_results', 'innerHTML', $this->pi_RTEcssText($this->conf['textForResults']));
			$objResponse->addAssign('kesearch_query_time', 'innerHTML', '');
			$objResponse->addAssign('kesearch_ordering', 'innerHTML', '');
			$objResponse->addAssign('kesearch_pagebrowser_top', 'innerHTML', '');
			$objResponse->addAssign('kesearch_pagebrowser_bottom', 'innerHTML', '');
			$objResponse->addAssign('kesearch_updating_results', 'innerHTML', '');
			$objResponse->addAssign('kesearch_num_results', 'innerHTML', '');
			$objResponse->addAssign('kesearch_filters', 'innerHTML', $this->renderFilters() . $this->onloadImage);
		} else {
			// set search results
			// process if on result page
			if ($GLOBALS['TSFE']->id == $this->conf['resultPage']) {
				$objResponse->addAssign('kesearch_results', 'innerHTML', $this->getSearchResults() . $this->onloadImage);
				$objResponse->addAssign('kesearch_num_results', 'innerHTML', sprintf($this->pi_getLL('num_results'), $this->numberOfResults));
				$objResponse->addAssign('kesearch_ordering', 'innerHTML', $this->renderOrdering());
			}

			// set pagebrowser
			if ($GLOBALS['TSFE']->id == $this->conf['resultPage']) {
				if ($this->conf['pagebrowserOnTop'] || $this->conf['pagebrowserAtBottom']) {
					$pagebrowserContent = $this->renderPagebrowser();
				}
				if ($this->conf['pagebrowserOnTop']) {
					$objResponse->addAssign('kesearch_pagebrowser_top', 'innerHTML', $pagebrowserContent);
				} else {
					$objResponse->addAssign('kesearch_pagebrowser_top', 'innerHTML', '');
				}
				if ($this->conf['pagebrowserAtBottom']) {
					$objResponse->addAssign('kesearch_pagebrowser_bottom', 'innerHTML', $pagebrowserContent);
				} else {
					$objResponse->addAssign('kesearch_pagebrowser_bottom', 'innerHTML', '');
				}
			}

			// set filters
			$objResponse->addAssign('kesearch_filters', 'innerHTML', $this->renderFilters() . $this->onloadImage);

			// set end milliseconds for query time calculation
			if ($this->conf['showQueryTime']) {
				// Calculate Querytime
				// we have two plugin. That's why we work with register here.
				$GLOBALS['TSFE']->register['ke_search_queryTime'] = (TYPO3\CMS\Core\Utility\GeneralUtility::milliseconds() - $GLOBALS['TSFE']->register['ke_search_queryStartTime']);
				$objResponse->addAssign('kesearch_query_time', 'innerHTML', sprintf($this->pi_getLL('query_time'), $GLOBALS['TSFE']->register['ke_search_queryTime']));
			}
		}
		// return response xml
		return $objResponse->getXML();
	}

	/*
	 * function refresh
	 * @param $arg
	 */
	public function refreshFiltersOnload($data) {
		// initializes plugin configuration
		$this->init();

		// set pivars
		$this->piVars = $data[$this->prefixId];
		foreach ($this->piVars as $key => $value) {
			$this->piVars[$key] = $value;
		}

		// init javascript onclick actions
		$this->initOnclickActions();

		// reset filters?
		if ($this->piVars['resetFilters'] && is_array($this->piVars['filter'])) {
			foreach ($this->piVars['filter'] as $key => $value) {
				// do not reset the preselected filters
				if ($this->preselectedFilter[$key]) {
					$this->piVars['filter'][$key] = $this->preselectedFilter[$key];
				}
				else {
					$this->piVars['filter'][$key] = '';
				}
			}
		}

		// make xajax response object
		$objResponse = new tx_xajax_response();

		// generate onload image
		$this->onloadImage = $this->createHideSpinner();

		// set filters
		$objResponse->addAssign('kesearch_filters', 'innerHTML', $this->renderFilters().$this->onloadImage );

		// return response xml
		return $objResponse->getXML();
	}

	/**
	 * render the text for "no results"
	 *
	 * @return string
	 */
	public function renderNoResultsText() {
		// get subpart for general message
		$content = $this->cObj->getSubpart($this->templateCode, '###GENERAL_MESSAGE###');

		// no results found
		if($this->conf['showNoResultsText']) {
			// use individual text set in flexform
			$noResultsText = $this->pi_RTEcssText($this->conf['noResultsText']);
			$attentionImage = '';
		} else {
			// use general text
			$noResultsText = $this->pi_getLL('no_results_found');

			// attention icon (only in marker based template)
			unset($imageConf);
			$imageConf['file'] = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey).'res/img/attention.gif';
			$imageConf['altText'] = $this->pi_getLL('no_results_found');
			$attentionImage = $this->cObj->IMAGE($imageConf);
		}

		// hook to implement your own idea of a no result message
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['noResultsHandler'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['noResultsHandler'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$_procObj->noResultsHandler($noResultsText, $this);
			}
		}

		// set text for "no results found"
		$content = $this->cObj->substituteMarker($content,'###MESSAGE###', $noResultsText);
		$this->fluidTemplateVariables['noResultsText'] = $noResultsText;

		// set attention icon. Note: Not used for fluid template. Use the "NoResults"-Partial to set an image.
		$content = $this->cObj->substituteMarker($content,'###IMAGE###', $attentionImage);

		// add onload image if in AJAX mode
		if($this->conf['renderMethod'] == 'ajax_after_reload') {
			$content .= $this->onloadImage;
		}

		return $content;
	}

	/**
	 * render message if at least one word in the search phrase is too short
	 *
	 * @return string
	 */
	public function renderTooShortWordsText() {
		// get subpart for general message
		$content = $this->cObj->getSubpart($this->templateCode, '###GENERAL_MESSAGE###');
		$content = $this->cObj->substituteMarker($content, '###MESSAGE###', $this->pi_getLL('searchword_length_error'));

		// attention icon
		unset($imageConf);
		$imageConf['file'] = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'res/img/attention.gif';
		$imageConf['altText'] = $this->pi_getLL('no_results_found');
		$attentionImage=$this->cObj->IMAGE($imageConf);

		// set attention icon?
		$content = $this->cObj->substituteMarker($content, '###IMAGE###', $attentionImage);

		return $content;
	}

	/**
	 * creates the search result list
	 * 1. does the actual searching (fetches the results to $rows)
	 * 2. fills the marker for marker based templating and renders the resultlist
	 * 3. fills fluid variables for fluid based templating to $this->fluidTemplateVariables
	 *
	 * @return string rendered searchbox (for static or ajax templating, not for fluid templating)
	 */
	public function getSearchResults() {
		// generate and add onload image
		$this->onloadImage = $this->createHideSpinner();

		// fetch the search results
		$limit = $this->db->getLimit();
		$rows = $this->db->getSearchResults();

		// TODO: Check how Sphinx handles this, seems to return full result set
		if(count($rows) > $limit[1]) {
			$rows = array_slice($rows, $limit[0], $limit[1]);
		}

		// set number of results
		$this->numberOfResults = $this->db->getAmountOfSearchResults();

		// count searchword with ke_stats
		$this->countSearchWordWithKeStats($this->sword);

		// count search phrase in ke_search statistic tables
		if ($this->conf['countSearchPhrases']) {
			$this->countSearchPhrase($this->sword, $this->swords, $this->numberOfResults, $this->tagsAgainst);
		}

		// render "no results" text and stop here
		if ($this->numberOfResults == 0) {
			return $this->renderNoResultsText();
		}

		if($this->hasTooShortWords) {
			$content = $this->renderTooShortWordsText();
			$this->fluidTemplateVariables['wordsTooShort'] = 1;
		}

		// init counter and loop through the search results
		$resultCount = 1;
		$this->searchResult = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_lib_searchresult', $this);

		$this->fluidTemplateVariables['resultrows'] = array();

		foreach($rows as $row) {
			// generate row content
			$tempContent = $this->cObj->getSubpart($this->templateCode, '###RESULT_ROW###');
			$this->searchResult->setRow($row);

			$tempMarkerArray = array(
				'title' => $this->searchResult->getTitle(),
				'teaser' => $this->searchResult->getTeaser(),
			);

			// hook for additional markers in result (only valid for maker based templating)
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['additionalResultMarker'])) {
					// make curent row number available to hook
				$this->currentRowNumber = $resultCount;
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['additionalResultMarker'] as $_classRef) {
					$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
					$_procObj->additionalResultMarker(
						$tempMarkerArray,
						$row,
						$this
					);
				}
				unset($this->currentRowNumber);
			}

			// add type marker
			// for file results just use the "file" type, not the file extension (eg. "file:pdf")
			list($type) = explode(':', $row['type']);
			$tempMarkerArray['type'] = str_replace(' ', '_', $type);

			// replace markers
			$tempContent = $this->cObj->substituteMarkerArray($tempContent, $tempMarkerArray, $wrap='###|###', $uppercase=1);
			$resultrowTemplateValues = $tempMarkerArray;

			// show result url?
			$resultUrl = $this->searchResult->getResultUrl($this->conf['renderResultUrlAsLink']);
			if ($this->conf['showResultUrl']) {
				$subContent = $this->cObj->getSubpart($this->templateCode, '###SUB_RESULTURL###');
				$subContent = $this->cObj->substituteMarker($subContent, '###LABEL_RESULTURL###', $this->pi_getLL('label_resulturl'));
				$subContent = $this->cObj->substituteMarker($subContent, '###RESULTURL###', $resultUrl);
			} else {
				$subContent = '';
			}
			$tempContent = $this->cObj->substituteSubpart($tempContent, '###SUB_RESULTURL###', $subContent, $recursive=1);
			$resultrowTemplateValues['url'] = $resultUrl;

			// show result numeration?
			$resultNumber = $resultCount + ($this->piVars['page'] * $this->conf['resultsPerPage']) - $this->conf['resultsPerPage'];
			if ($this->conf['resultsNumeration']) {
				$subContent = $this->cObj->getSubpart($this->templateCode, '###SUB_NUMERATION###');
				$subContent = $this->cObj->substituteMarker($subContent, '###NUMBER###', $resultNumber);
			} else {
				$subContent = '';
			}
			$tempContent = $this->cObj->substituteSubpart($tempContent, '###SUB_NUMERATION###', $subContent, $recursive=1);
			$resultrowTemplateValues['number'] = $resultNumber;

			// show score?
			$resultScore = number_format($row['score'],2,',','');
			if ($this->conf['showScore'] && $row['score']) {
				$subContent = $this->cObj->getSubpart($this->templateCode, '###SUB_SCORE###');
				$subContent = $this->cObj->substituteMarker($subContent, '###LABEL_SCORE###', $this->pi_getLL('label_score'));
				$subContent = $this->cObj->substituteMarker($subContent, '###SCORE###', $resultScore);
			} else {
				$subContent = '';
			}
			$tempContent = $this->cObj->substituteSubpart($tempContent, '###SUB_SCORE###', $subContent, $recursive=1);
			$resultrowTemplateValues['score'] = $resultScore;

			// show date?
			$resultDate = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $row['sortdate']);
			if ($this->conf['showDate'] && $row['sortdate']) {
				$subContent = $this->cObj->getSubpart($this->templateCode, '###SUB_DATE###');
				$subContent = $this->cObj->substituteMarker($subContent, '###LABEL_DATE###', $this->pi_getLL('label_date'));
				$subContent = $this->cObj->substituteMarker($subContent, '###DATE###', $resultDate);
			} else {
				$subContent = '';
			}
			$tempContent = $this->cObj->substituteSubpart ($tempContent, '###SUB_DATE###', $subContent, $recursive=1);
			$resultrowTemplateValues['date'] = $resultDate;

			// show percental score?
			if ($this->conf['showPercentalScore'] && $row['percent']) {
				$subContent = $this->cObj->getSubpart($this->templateCode,'###SUB_SCORE_PERCENT###');
				$subContent = $this->cObj->substituteMarker($subContent,'###LABEL_SCORE_PERCENT###', $this->pi_getLL('label_score_percent'));
				$subContent = $this->cObj->substituteMarker($subContent,'###SCORE_PERCENT###', $row['percent']);
			} else {
				$subContent = '';
			}
			$tempContent = $this->cObj->substituteSubpart ($tempContent, '###SUB_SCORE_PERCENT###', $subContent, $recursive=1);
			$resultrowTemplateValues['percent'] = $row['percent'];

			// show score scale?
			if ($this->conf['showScoreScale'] && $row['percent']) {
				$subContent = $this->cObj->getSubpart($this->templateCode, '###SUB_SCORE_SCALE###');
				$subContent = $this->cObj->substituteMarker($subContent, '###SCORE###', $row['percent']);
			} else {
				$subContent = '';
			}
			$tempContent = $this->cObj->substituteSubpart ($tempContent, '###SUB_SCORE_SCALE###', $subContent, $recursive=1);

			// show tags?
			$tags = $row['tags'];
			$tags = str_replace('#', ' ', $tags);
			if ($this->conf['showTags']) {
				$subContent = $this->cObj->getSubpart($this->templateCode,'###SUB_TAGS###');
				$subContent = $this->cObj->substituteMarker($subContent,'###LABEL_TAGS###', $this->pi_getLL('label_tags'));
				$subContent = $this->cObj->substituteMarker($subContent,'###TAGS###', htmlspecialchars($tags));
			} else {
				$subContent = '';
			}
			$tempContent = $this->cObj->substituteSubpart ($tempContent, '###SUB_TAGS###', $subContent, $recursive=1);
			$resultrowTemplateValues['tags'] = $tags;

			// preview image
			$renderedImage = $this->renderPreviewImageOrTypeIcon($row);
			$resultrowTemplateValues['imageHtml'] = $renderedImage[0];
			$tempContent = $this->cObj->substituteSubpart ($tempContent, '###SUB_TYPE_ICON###', $renderedImage[1], $recursive=1);

			// fluid templating: add result row to the variables array
			$this->fluidTemplateVariables['resultrows'][] = $resultrowTemplateValues;

			// marker based templating: add temp content to result list
			$content .= $tempContent;

			// increase result counter
			$resultCount++;

		}

		// hook for additional content AFTER the result list
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['additionalContentAfterResultList'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['additionalContentAfterResultList'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$content .= $_procObj->additionalContentAfterResultList($this);
			}
		}

		// add onload image if in AJAX mode
		if($this->conf['renderMethod'] != 'static') {
			$content .= $this->onloadImage;
		}

		return $content;
	}


	/**
	 * renders a preview image in the result list or a icon which indicates
	 * the type of the result (page, news, ...)
	 *
	 * returns an array with
	 * index 0: pure HTML code for the image for the use in fluid templating
	 * index 1: fully rendered subpart for marker based templating
	 *
	 * TODO: Move the image rendering itself to fluid
	 *
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 19.03.15
	 * @return array
	 */
	function renderPreviewImageOrTypeIcon($row) {

			// preview image (instead of type icon)
			$subContent = $this->cObj->getSubpart($this->templateCode,'###SUB_TYPE_ICON###');
			list($type, $filetype) = explode(':', $row['type']);
			switch ($type) {

				case 'file':
					if ($this->conf['showFilePreview']) {
						$imageHtml = $this->renderFilePreview($row);
					}
					break;

				case 'page':
					if ($this->conf['showPageImages']) {
						$imageHtml = $this->renderFALPreviewImage($row['orig_uid'], 'pages', 'media');
					}
					break;

				case 'news':
					if ($this->conf['showNewsImages']) {
						$imageHtml = $this->renderFALPreviewImage($row['orig_uid'], 'tx_news_domain_model_news', 'fal_media');
					}
					break;

				default:
					$imageHtml = '';
					break;
			}
			$subContent = $this->cObj->substituteMarker($subContent,'###TYPE_ICON###', $imageHtml);

			// render type icon if no preview image is available (or preview is disabled)
			if ($this->conf['showTypeIcon'] && empty($imageHtml)) {
				$imageHtml = $this->renderTypeIcon($row['type']);
				$subContent = $this->cObj->getSubpart($this->templateCode,'###SUB_TYPE_ICON###');
				$subContent = $this->cObj->substituteMarker($subContent,'###TYPE_ICON###', $imageHtml);
			}

			return array($imageHtml, $subContent);
	}

	/**
 	* Counts searchword and -phrase if ke_stats is installed
 	*
 	* @param   string $searchphrase
 	* @return  void
 	* @author  Christian Buelter <buelter@kennziffer.com>
 	* @since   Tue Mar 01 2011 12:34:25 GMT+0100
 	*/
	public function countSearchWordWithKeStats($searchphrase='') {

		$searchphrase = trim($searchphrase);
		$keStatsIsLoaded = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('ke_stats');
		if ($keStatsIsLoaded && !empty($searchphrase)) {
			$keStatsObj = TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj('EXT:ke_stats/pi1/class.tx_kestats_pi1.php:tx_kestats_pi1');
			$keStatsObj->initApi();

				// count words
			$wordlist = TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $searchphrase, true);
			foreach ($wordlist as $singleword) {
				$keStatsObj->increaseCounter(
					'ke_search Words',
					'element_title,year,month',
					$singleword,
					0,
					$this->firstStartingPoint,
					$GLOBALS['TSFE']->sys_page->sys_language_uid,
					0,
					'extension'
				);
			}

				// count phrase
			$keStatsObj->increaseCounter(
				'ke_search Phrases',
				'element_title,year,month',
				$searchphrase,
				0,
				$this->firstStartingPoint,
				$GLOBALS['TSFE']->sys_page->sys_language_uid,
				0,
				'extension'
			);

			unset($wordlist);
			unset($singleword);
			unset($keStatsObj);
		}
	}


	/**
	 * Fetches configuration value given its name.
	 * Merges flexform and TS configuration values.
	 *
	 * @param	string	$param	Configuration value name
	 * @return	string	Parameter value
	 */
	public function fetchConfigurationValue($param, $sheet = 'sDEF') {
		$value = trim($this->pi_getFFvalue(
			$this->cObj->data['pi_flexform'], $param, $sheet)
		);
		return $value ? $value : $this->conf[$param];
	}


	/*
	 * function betterSubstr
	 *
	 * better substring function
	 *
	 * @param $str
	 * @param $length
	 * @param $minword
	 */
	public function betterSubstr($str, $length, $minword = 3) {
		$sub = '';
		$len = 0;
		foreach (explode(' ', $str) as $word) {
			$part = (($sub != '') ? ' ' : '') . $word;
			$sub .= $part;
			$len += strlen($part);
			if (strlen($word) > $minword && strlen($sub) >= $length) {
				break;
			}
		}
		return $sub . (($len < strlen($str)) ? '...' : '');
	}


	/*
	 * function renderPagebrowser
	 * @param $arg
	 */
	public function renderPagebrowser() {

		$this->initOnclickActions();

		// hook for third party pagebrowsers or for modification of build in browser
		// if the hook return content then return that content
		// use only if you use marker based templating, not for fluid based templating!
		$content = '';
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['renderPagebrowserInit'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['renderPagebrowserInit'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$content = $_procObj->renderPagebrowserInit($this);
			}
		}
		if($content) {
			return $content;
		}

		$numberOfResults = $this->numberOfResults;
		$resultsPerPage = $this->conf['resultsPerPage'];
		$maxPages = $this->conf['maxPagesInPagebrowser'];

		// get total number of items to show
		if ($numberOfResults > $resultsPerPage) {
			// show pagebrowser if there are more entries that are
			// shown on one page
			$this->limit = $resultsPerPage;
		} else {
			// do not show pagebrowser
			return '';
		}

		// set db limit
		$start = ($this->piVars['page'] * $resultsPerPage) - $resultsPerPage;
		$this->dbLimit = $start.','.$resultsPerPage;

		// number of pages
		$pagesTotal = ceil($numberOfResults/ $resultsPerPage);

		$interval = ceil($maxPages/2);

		$startPage = $this->piVars['page'] - ceil(($maxPages/2));
		$endPage = $startPage + $maxPages - 1;
		if ($startPage < 1) {
			$startPage = 1;
			$endPage = $startPage + $maxPages -1;
		}
		if ($startPage > $pagesTotal) {
			$startPage = $pagesTotal - $maxPages + 1;
			$endPage = $pagesTotal;
		}
		if ($endPage > $pagesTotal) {
			$startPage = $pagesTotal - $maxPages + 1;
			$endPage = $pagesTotal;
		}

		// render pages list
		for ($i=1; $i<=$pagesTotal; $i++) {
			if ($i >= $startPage && $i <= $endPage) {

				// render static version
				unset($linkconf);
				$linkconf['parameter'] = $GLOBALS['TSFE']->id;
				$linkconf['addQueryString'] = 1;
				$linkconf['addQueryString.']['exclude'] = 'id';
				$linkconf['additionalParams'] = '&tx_kesearch_pi1[page]=' . intval($i);
				$filterArray = $this->filters->getFilters();

				if (is_array($this->piVars['filter'])) {
					foreach($this->piVars['filter'] as $filterId => $data) {
						if(is_array($data)) {
							foreach($data as $tagKey => $tag) {
								$linkconf['additionalParams'] .= '&tx_kesearch_pi1[filter]['.$filterId.'][' . $tagKey . ']='.$tag;
							}
						} else $linkconf['additionalParams'] .= '&tx_kesearch_pi1[filter]['.$filterId.']='.$this->piVars['filter'][$filterId];
					}
				}

				if ($this->piVars['page'] == $i) $linkconf['ATagParams'] = 'class="current" ';
				$tempContent .= $this->cObj->typoLink($i, $linkconf) . ' ';
			}
		}

		// end
		$end = ($start+$resultsPerPage > $numberOfResults) ? $numberOfResults : ($start+$resultsPerPage);

		// previous image with link
		if ($this->piVars['page'] > 1) {

			$previousPage = $this->piVars['page']-1;

			// get static version
			unset($linkconf);
			$linkconf['parameter'] = $GLOBALS['TSFE']->id;
			$linkconf['addQueryString'] = 1;
			$linkconf['additionalParams'] = '&tx_kesearch_pi1[sword]='.$this->piVars['sword'];
			$linkconf['additionalParams'] .= '&tx_kesearch_pi1[page]='.intval($previousPage);
			$filterArray = $this->filters->getFilters();

			if (is_array($this->piVars['filter'])) {
				foreach($this->piVars['filter'] as $filterId => $data) {
					if(is_array($data)) {
						foreach($data as $tagKey => $tag) {
							$linkconf['additionalParams'] .= '&tx_kesearch_pi1[filter]['.$filterId.'][' . $tagKey . ']='.$tag;
						}
					} else $linkconf['additionalParams'] .= '&tx_kesearch_pi1[filter]['.$filterId.']='.$this->piVars['filter'][$filterId];
				}
			}

			$linkconf['ATagParams'] = 'class="prev" ';
			$previous = $this->cObj->typoLink($this->pi_getLL('pagebrowser_prev'), $linkconf);
		} else {
			$previous = '';
		}

		// next image with link
		if ($this->piVars['page'] < $pagesTotal) {
			$nextPage = $this->piVars['page']+1;

			// get static version
			unset($linkconf);
			$linkconf['parameter'] = $GLOBALS['TSFE']->id;
			$linkconf['addQueryString'] = 1;
			$linkconf['additionalParams'] = '&tx_kesearch_pi1[sword]='.$this->piVars['sword'];
			$linkconf['additionalParams'] .= '&tx_kesearch_pi1[page]='.intval($nextPage);
			$filterArray = $this->filters->getFilters();

			if (is_array($this->piVars['filter'])) {
				foreach($this->piVars['filter'] as $filterId => $data) {
					if(is_array($data)) {
						foreach($data as $tagKey => $tag) {
							$linkconf['additionalParams'] .= '&tx_kesearch_pi1[filter]['.$filterId.'][' . $tagKey . ']='.$tag;
						}
					} else $linkconf['additionalParams'] .= '&tx_kesearch_pi1[filter]['.$filterId.']='.$this->piVars['filter'][$filterId];
				}
			}

			$linkconf['ATagParams'] = 'class="next" ';
			$next = $this->cObj->typoLink($this->pi_getLL('pagebrowser_next'), $linkconf);
		} else {
			$next = '';
		}


		// render pagebrowser content
		$content = $this->cObj->getSubpart($this->templateCode, '###PAGEBROWSER###');
		$markerArray = array(
			'current' => $this->piVars['page'],
			'pages_total' => $pagesTotal,
			'pages_list' => $tempContent,
			'start' => $start+1,
			'end' => $end,
			'total' => $numberOfResults,
			'previous' => $previous,
			'next' => $next,
			'results' => $this->pi_getLL('results'),
			'until' => $this->pi_getLL('until'),
			'of' => $this->pi_getLL('of'),
		);
		$this->fluidTemplateVariables['pagebrowser'] = $markerArray;

		// hook for additional markers in pagebrowse
		// use only if you use marker based templating, not for fluid based templating!
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['pagebrowseAdditionalMarker'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['pagebrowseAdditionalMarker'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$_procObj->pagebrowseAdditionalMarker(
					$markerArray,
					$this
				);
			}
		}

		$content = $this->cObj->substituteMarkerArray($content,$markerArray,$wrap='###|###',$uppercase=1);

		return $content;
	}


	public function renderOrdering() {
		$sortObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_lib_sorting', $this);
		return $sortObj->renderSorting($this->fluidTemplateVariables);
	}

	/**
	 * renders the preview image of a file result
	 *
	 * @param array $row result row
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 17.10.14
	 * @return string
	 */
	public function renderFilePreview($row) {
		list($type, $filetype) = explode(':', $row['type']);
		if (in_array($filetype, $this->fileTypesWithPreviewPossible)) {
			$imageConf = $this->conf['previewImage.'];

			// if index record is of type "file" and contains an orig_uid, this is the reference
			// to a FAL record. Otherwise we use the path directly.
			if ($row['orig_uid']) {
				$fileRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
				$fileObject = $fileRepository->findByUid($row['orig_uid']);
				$metadata = $fileObject->_getMetaData();
				$imageConf['file'] = $fileObject->getPublicUrl();
				$imageConf['altText'] = $metadata['alternative'];
			} else {
				$imageConf['file'] = $row['directory'] . rawurlencode($row['title']);
			}
			return $this->renderPreviewImage($imageConf);
		}
	}

	/**
	 * renders the preview image of a result which has an attached image,
	 * needs FAL and is therefore only available for TYPO3 version 6 or higher.
	 * Returns an empty string if no image could be rendered.
	 *
	 * @param integer $uid uid of referencing record
	 * @param string $table table name of the original table
	 * @param string $fieldname field which holds the FAL reference
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 5.11.14
	 * @return string
	 */
	public function renderFALPreviewImage($uid, $table='pages', $fieldname='media') {
		$imageHtml = '';

		$imageConf = $this->conf['previewImage.'];
		$fileRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$fileObjects = $fileRepository->findByRelation($table, $fieldname, $uid);
		if (count($fileObjects)) {
			$fileObject = $fileObjects[0];
		}

		if ($fileObject) {
			$referenceProperties = $fileObject->getReferenceProperties();
			$originalFileProperties = $fileObject->getOriginalFile()->getProperties();
			$alternative = $referenceProperties['alternative'] ? $referenceProperties['alternative'] : $originalFileProperties['alternative'];

			$imageConf['file'] = $fileObject->getPublicUrl();
			$imageConf['altText'] = $alternative;
			$imageHtml = $this->renderPreviewImage($imageConf);
		}

		return $imageHtml;
	}

	/**
	 * renders a review image and sets the max. width and max. height if not
	 * defined yet.
	 *
	 * @param array $imageConf
	 * @return string
	 */
	public function renderPreviewImage($imageConf) {
			if (empty($imageConf['file.']['maxW'])) $imageConf['file.']['maxW'] = 150;
			if (empty($imageConf['file.']['maxH'])) $imageConf['file.']['maxH'] = 150;
			$rendered = $this->cObj->cObjGetSingle('IMAGE', $imageConf);
			return $rendered;
	}

	/**
	 * renders an image tag which will prepend the teaser if activated by user.
	 *
	 * @param $typeComplete string A value like page, dam, tt_address, for files eg. "file:pdf"
	 */
	public function renderTypeIcon($typeComplete) {
		list($type) = explode(':', $typeComplete);
		$name = str_replace(':', '_', $typeComplete);

		if ($this->conf['resultListTypeIcon.'][$name . '.']) {
			$imageConf = $this->conf['resultListTypeIcon.'][$name . '.'];
		} else {
			// custom image (old configuration option, only for gif images)
			if ($this->conf['additionalPathForTypeIcons']) {
				$imageConf['file'] = str_replace(PATH_site, '', TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->conf['additionalPathForTypeIcons'] . $name . '.gif'));
			}
		}

		// fallback: default image
		if(!is_file(PATH_site . $imageConf['file'])) {
			$imageConf['file'] = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'res/img/types/' . $name . '.gif';

			// fallback for file results: use default if no image for this file extension is available
			if($type == 'file' && !is_file(PATH_site . $imageConf['file'])) {
				$imageConf['file'] = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey) . 'res/img/types/file.gif';
			}
		}

		$rendered = $this->cObj->cObjGetSingle('IMAGE', $imageConf);

		return $rendered;
	}

	/*
	 * function initDomReadyAction
	 */
	public function initDomReadyAction() {

		// is current page the result page?
		$resultPage = ($GLOBALS['TSFE']->id == $this->conf['resultPage']) ? TRUE : FALSE;

		switch ($this->conf['renderMethod']) {
			case 'ajax_after_reload':
				// refresh results only if we are on the defined result page
				// do not refresh results if default text is shown (before filters and swords are sent)
				if ($resultPage) {
					if($this->isEmptySearch && $this->conf['showTextInsteadOfResults']) {
						$domReadyAction = 'onloadFilters();';
					} else {
						$domReadyAction = 'onloadFiltersAndResults();';
					}
				} else {
					$domReadyAction = 'onloadFilters();';
				}
				break;
			case 'static':
			default:
				$domReadyAction = '';
				break;
		}
		$this->onDomReady = empty($domReadyAction) ? '' : 'domReady(function() {'.$domReadyAction.'});';
	}


	/*
	 * count searchwords and phrases in statistic tables
	 * assumes that charset ist UTF-8 and uses mb_strtolower
	 *
	 * @param $searchPhrase string
	 * @param $searchWordsArray array
	 * @param $hits int
	 * @param $this->tagsAgainst string
	 * @return void
	 *
	 */
	public function countSearchPhrase($searchPhrase, $searchWordsArray, $hits, $tagsAgainst) {

		// prepare "tagsAgainst"
		$search = array('"', ' ', '+');
		$replace = array('', '', '');
		$tagsAgainst = str_replace($search, $replace, implode(' ', $tagsAgainst));

		if (extension_loaded('mbstring')) {
			$searchPhrase = mb_strtolower($searchPhrase, 'UTF-8');
		} else {
			$searchPhrase = strtolower($searchPhrase);
		}

		// count search phrase
		if (!empty($searchPhrase)) {
			$table = 'tx_kesearch_stat_search';
			$fields_values = array(
				'pid' => $this->firstStartingPoint,
				'searchphrase' => '\'' . $searchPhrase . '\'',
				'tstamp' => time(),
				'hits' => $hits,
				'tagsagainst' => $tagsAgainst,
				'language' => $GLOBALS['TSFE']->sys_language_uid,
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $fields_values, array('searchphrase'));
		}

		// count single words
		foreach ($searchWordsArray as $searchWord) {
			if (extension_loaded('mbstring')) {
				$searchWord = mb_strtolower($searchWord, 'UTF-8');
			} else {
				$searchWord = strtolower($searchWord);
			}
			$table = 'tx_kesearch_stat_word';
			$timestamp = time();
			if (!empty($searchWord)) {
				$fields_values = array(
					'pid' => $this->firstStartingPoint,
					'word' => '\'' . $searchWord . '\'',
					'tstamp' => $timestamp,
					'pageid' => $GLOBALS['TSFE']->id,
					'resultsfound' => $hits ? 1 : 0,
					'language' => $GLOBALS['TSFE']->sys_language_uid,
				);
				$GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $fields_values, array('word'));
			}
		}
	}


	/**
	 * gets all preselected filters from flexform
	 *
	 * @return none but fills global var with needed data
	 */
	public function getFilterPreselect() {
		// get definitions from plugin settings
		// and proceed only when preselectedFilter was not set
		// this reduces the amount of sql queries, too
		if($this->conf['preselected_filters'] && count($this->preselectedFilter) == 0) {
			$preselectedArray = TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['preselected_filters'], true);
			foreach ($preselectedArray as $option) {
				$option = intval($option);
				$fields = '
					tx_kesearch_filters.uid as filteruid,
					tx_kesearch_filteroptions.uid as optionuid,
					tx_kesearch_filteroptions.tag
				';
				$table = 'tx_kesearch_filters, tx_kesearch_filteroptions';
				$where = $GLOBALS['TYPO3_DB']->listQuery('tx_kesearch_filters.options', $option, 'tx_kesearch_filters');
				$where .= ' AND tx_kesearch_filteroptions.uid = ' . $option;
				$where .= $this->cObj->enableFields('tx_kesearch_filters');
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields,$table,$where,$groupBy='',$orderBy='',$limit='');
				while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					//$this->preselectedFilter[$row['filteruid']][] = $row['tag'];
					$this->preselectedFilter[$row['filteruid']][$row['optionuid']] = $row['tag'];
				}
			}
		}
	}


	/**
	 * function isEmptySearch
	 * checks if an empty search was loaded / submitted
	 *
	 * @return boolean true if no searchparams given; otherwise false
	 */
	public function isEmptySearch() {
		// check if searchword is emtpy or equal with default searchbox value
		$emptySearchword = (empty($this->sword) || $this->sword == $this->pi_getLL('searchbox_default_value')) ? true : false;

		// check if filters are set
		$filters = $this->filters->getFilters();
		$filterSet = false;
		if(is_array($filters))  {
			//TODO: piVars filter is a multidimensional array
			foreach($filters as $filter)  {
				if(!empty($this->piVars['filter'][$filter['uid']])) $filterSet = true;
			}
		}

		if($emptySearchword && !$filterSet) return true;
		else return false;
	}


	/**
	 * function includeJavascript
	 */
	public function addHeaderParts() {
		// build target URL if not result page
		unset($linkconf);
		$linkconf['parameter'] = $this->conf['resultPage'];
		$linkconf['additionalParams'] = '';
		$linkconf['useCacheHash'] = false;
		$targetUrl = TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($this->cObj->typoLink_URL($linkconf));

		$content = $this->cObj->getSubpart($this->templateCode, '###JS_SEARCH_ALL###');
		if($this->conf['renderMethod'] != 'static' ) {
			$content .= $this->cObj->getSubpart($this->templateCode, '###JS_SEARCH_NON_STATIC###');
		}

		// include js for "ajax after page reload" mode
		if ($this->conf['renderMethod'] == 'ajax_after_reload') {
			$content .= $this->cObj->getSubpart($this->templateCode, '###JS_SEARCH_AJAX_RELOAD###');
		}

		// loop through LL and fill $markerArray
		array_key_exists($this->LLkey, $this->LOCAL_LANG) ? $langKey = $this->LLkey : $langKey = 'default';
		foreach($this->LOCAL_LANG[$langKey] as $key => $value) {
			$markerArray['###' . strtoupper($key) . '###'] = $value;
		}

		// define some additional markers
		$markerArray['###SITE_REL_PATH###'] = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extKey);
		$markerArray['###TARGET_URL###'] = $targetUrl;
		$markerArray['###PREFIX_ID###'] = $this->prefixId;
		$markerArray['###SEARCHBOX_DEFAULT_VALUE###'] = $this->pi_getLL('searchbox_default_value');
		$markerArray['###DOMREADYACTION###'] = $this->onDomReady;

		$content = $this->cObj->substituteMarkerArray($content, $markerArray);

		// add JS to page header
		$GLOBALS['TSFE']->getPageRenderer()->addHeaderData($content);
	}


	public function sortArrayRecursive($array, $field) {

		$sortArray = Array();
		$mynewArray = Array();

		$i=1;
		foreach ($array as $point) {
			$sortArray[] = $point[$field].$i;
			$i++;
		}
		rsort($sortArray);

		foreach ($sortArray as $sortet) {
			$i=1;
			foreach ($array as $point) {
				$newpoint[$field]= $point[$field].$i;
				if ($newpoint[$field]==$sortet) $mynewArray[] = $point;
				$i++;
			}
		}
		return $mynewArray;

	}


	public function sortArrayRecursive2($wert_a, $wert_b) {
		// Sortierung nach dem zweiten Wert des Array (Index: 1)
		$a = $wert_a[2];
		$b = $wert_b[2];

		if ($a == $b) {
			return 0;
		}

		return ($a < $b) ? -1 : +1;
	}

	/**
	 * implements a recursive in_array function
	 *
	 * @param mixed $needle
	 * @param array $array
	 * @return boolean
	 * @author Christian Bülter <buelter@kennziffer.com>
	 * @since 11.07.12
	 */
	public function in_multiarray($needle, $haystack) {
		foreach ($haystack as $value) {
			if (is_array($value)) {
				if ($this->in_multiarray($needle, $value)) {
					return true;
				}
			} else if ($value == $needle) {
				return true;
			}
		}
		return false;
	}


	/*
	 * Sort array by given column
	 *
	 * @param array $arr	the array
	 * @param string $col	the column
	 * @return void
	 */
	public function sortArrayByColumn(&$arr, $col) {

		$sort_col = array();
		foreach ($arr as $key => $row) {
			$sort_col[$key] = strtoupper($row[$col]);
		}
		asort($sort_col, SORT_LOCALE_STRING);

		foreach($sort_col as $key => $val) {
			$newArray[$key] = $arr[$key];
		}

		$arr = $newArray;
	}
}
