<?php

namespace TeaminmediasPluswerk\KeSearch\Lib;

/***************************************************************
 *  Copyright notice
 *  (c) 2010 Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TeaminmediasPluswerk\KeSearch\Domain\Repository\GenericRepository;
use TeaminmediasPluswerk\KeSearchPremium\KeSearchPremium;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;

/**
 * Parent class for plugins pi1 and pi2
 * @author    Andreas Kiefer
 * @author    Stefan Froemken
 * @author    Christian Bülter
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class Pluginbase extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    // Same as class name
    public $prefixId = 'tx_kesearch_pi1';

    // The extension key.
    public $extKey = 'ke_search';

    // cleaned searchword (karl-heinz => karl heinz)
    public $sword = '';

    // searchwords as array
    /**
     * @var array
     */
    public $swords;

    // searchphrase for boolean mode (+karl* +heinz*)
    public $wordsAgainst = '';

    // tagsphrase for boolean mode (+#category_213# +#city_42#)
    public $tagsAgainst = array();

    // searchphrase for score/non boolean mode (karl heinz)
    public $scoreAgainst = '';

    // true if no searchparams given; otherwise false
    public $isEmptySearch = true;

    // comma seperated list of startingPoints
    public $startingPoints = 0;

    // first entry in list of startingpoints
    public $firstStartingPoint = 0;

    // FlexForm-Configuration
    public $conf = array();

    // Extension-Configuration
    public $extConf = array();

    // Extension-Configuration of ke_search_premium if installed
    public $extConfPremium = array();

    // count search results
    public $numberOfResults = 0;

    // it's for 'USE INDEX ($indexToUse)' to speed up queries
    public $indexToUse = '';

    // contains all tags of current search result
    public $tagsInSearchResult = false;

    // preselected filters by flexform
    public $preselectedFilter = array();

    // array with filter-uids as key and whole data as value
    public $filtersFromFlexform = array();

    // contains a boolean value which represents if there are too short words in the searchstring
    public $hasTooShortWords = false;
    public $fileTypesWithPreviewPossible = array('pdf', 'jpg', 'jpeg', 'png', 'gif', 'bmp', 'tif', 'tiff');
    public $fluidTemplateVariables = array();

    /**
     * @var Db
     */
    public $db;

    /**
     * @var PluginBaseHelper
     */
    public $div;

    /**
     * @var TypoScriptService
     */
    public $typoScriptService;

    /**
     * @var KeSearchPremium keSearchPremium
     */
    public $keSearchPremium;

    /**
     * @var Searchresult
     */
    public $searchResult;

    /**
     * @var Filters
     */
    public $filters;

    /**
     * Initializes flexform, conf vars and some more
     * @return void
     */
    public function init()
    {
        // get some helper functions
        $this->div = GeneralUtility::makeInstance(PluginBaseHelper::class, $this);

        $this->typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        // set start of query timer
        if (!$GLOBALS['TSFE']->register['ke_search_queryStartTime']) {
            $GLOBALS['TSFE']->register['ke_search_queryStartTime'] = GeneralUtility::milliseconds();
        }

        // make settings from flexform available in general configuration ($this->conf)
        $this->moveFlexFormDataToConf();

        // in pi2 (the list plugin) fetch the configuration from pi1 (the search
        // box plugin) since all the configuration is done there
        if (!empty($this->conf['loadFlexformsFromOtherCE'])) {
            $data = $this->pi_getRecord('tt_content', intval($this->conf['loadFlexformsFromOtherCE']));
            $this->cObj->data['pi_flexform'] = $data['pi_flexform'];
            $this->moveFlexFormDataToConf();
        }

        // explode flattened piVars to multi-dimensional array
        $this->piVars = SearchHelper::explodePiVars($this->piVars);

        // clean piVars
        $this->piVars = $this->div->cleanPiVars($this->piVars);

        // get preselected filter from rootline
        $this->getFilterPreselect();

        // add stdWrap properties to each config value (not to arrays)
        foreach ($this->conf as $key => $value) {
            if (!is_array($this->conf[$key])) {
                $this->conf[$key] = $this->cObj->stdWrap($value, $this->conf[$key . '.']);
            }
        }

        // set some default values (this part has to be after stdWrap!!!)
        if (!$this->conf['resultPage']) {
            $this->conf['resultPage'] = $GLOBALS['TSFE']->id;
        }
        if (!isset($this->piVars['page'])) {
            $this->piVars['page'] = 1;
        }
        if (!empty($this->conf['additionalPathForTypeIcons'])) {
            $this->conf['additionalPathForTypeIcons'] = rtrim($this->conf['additionalPathForTypeIcons'], '/') . '/';
        }

        // hook: modifyFlexFormData
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFlexFormData'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFlexFormData'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $_procObj->modifyFlexFormData($this->conf, $this->cObj, $this->piVars);
            }
        }

        // prepare database object
        $this->db = GeneralUtility::makeInstance(Db::class, $this);

        // set startingPoints
        $this->startingPoints = $this->div->getStartingPoint();

        // get filter class
        $this->filters = GeneralUtility::makeInstance(Filters::class);

        // get extension configuration array
        $this->extConf = SearchHelper::getExtConf();
        $this->extConfPremium = SearchHelper::getExtConfPremium();

        // initialize filters
        $this->filters->initialize($this);

        // get first startingpoint
        $this->firstStartingPoint = $this->div->getFirstStartingPoint($this->startingPoints);

        // build words searchphrase
        /** @var Searchphrase $searchPhrase */
        $searchPhrase = GeneralUtility::makeInstance(Searchphrase::class);
        $searchPhrase->initialize($this);
        $searchWordInformation = $searchPhrase->buildSearchPhrase();

        // Hook: modifySearchWords
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifySearchWords'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifySearchWords'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'modifySearchWords')) {
                    $hookObj->modifySearchWords($searchWordInformation, $this);
                }
            }
        }

        // set searchword and tag information
        $this->sword = $searchWordInformation['sword'];
        $this->swords = $searchWordInformation['swords'];
        $this->wordsAgainst = $searchWordInformation['wordsAgainst'];
        $this->scoreAgainst = $searchWordInformation['scoreAgainst'];
        $this->tagsAgainst = $searchWordInformation['tagsAgainst'];

        $this->isEmptySearch = $this->isEmptySearch();

        // Since sorting for "relevance" in most cases ist the most useful option and
        // this sorting option is not available until a searchword is given, make it
        // the default sorting after a searchword has been given.
        // Set default sorting to "relevance" if the following conditions are true:
        // * sorting by user is allowed
        // * sorting for "relevance" is allowed (internal: "score")
        // * user did not select his own sorting yet
        // * a searchword is given
        $isInList = GeneralUtility::inList($this->conf['sortByVisitor'], 'score');
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

        // perform search at this point already if we need to calculate what
        // filters to display.
        if ($this->conf['checkFilterCondition'] != 'none' && !$this->allowEmptySearch()) {
            $this->db->getSearchResults();
        }

        // add cssTag to header if set
        if (!empty($this->conf['cssFile'])) {
            $filePathSanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
            $cssFile = $filePathSanitizer->sanitize($this->conf['cssFile']);
            if (!empty($cssFile)) {
                /** @var \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer */
                $pageRenderer = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
                $pageRenderer->addCssFile($cssFile);
            }
        }
    }

    /**
     * Move all FlexForm data of current record to conf array
     */
    public function moveFlexFormDataToConf()
    {
        // don't move this to init
        $this->pi_initPIflexForm();

        $piFlexForm = $this->cObj->data['pi_flexform'];
        if (is_array($piFlexForm['data'])) {
            foreach ($piFlexForm['data'] as $sheetKey => $sheet) {
                foreach ($sheet as $lang) {
                    foreach ($lang as $key => $value) {
                        // delete current conf value from conf-array
                        // when FF-Value differs from TS-Conf and FF-Value is not empty
                        $value = $this->fetchConfigurationValue($key, $sheetKey);
                        if ($this->conf[$key] != $value && !empty($value)) {
                            unset($this->conf[$key]);
                            $this->conf[$key] = $this->fetchConfigurationValue($key, $sheetKey);
                        }
                    }
                }
            }
        }
    }

    /**
     * creates the searchbox
     * fills fluid variables for the fluid template to $this->fluidTemplateVariables
     * @return void
     */
    public function getSearchboxContent()
    {
        // set page = 1 for every new search
        $pageValue = 1;
        $this->fluidTemplateVariables['page'] = $pageValue;

        // searchword input value
        $searchString = $this->piVars['sword'];

        $searchboxDefaultValue = LocalizationUtility::translate(
            'LLL:EXT:ke_search/Resources/Private/Language/locallang_searchbox.xml:searchbox_default_value',
            'KeSearch'
        );

        if (!empty($searchString) && $searchString != $searchboxDefaultValue) {
            $this->swordValue = $searchString;
        } else {
            $this->swordValue = '';
        }

        $this->fluidTemplateVariables['searchword'] = htmlspecialchars($this->swordValue);
        $this->fluidTemplateVariables['searchwordDefault'] = $searchboxDefaultValue;
        $this->fluidTemplateVariables['sortByField'] = $this->piVars['sortByField'];
        $this->fluidTemplateVariables['sortByDir'] = $this->piVars['sortByDir'];

        // get filters
        $this->renderFilters();

        // set form action pid
        $this->fluidTemplateVariables['targetpage'] = $this->conf['resultPage'];

        // set form action
        $siteUrl = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
        $lParam = GeneralUtility::_GET('L');
        $mpParam = GeneralUtility::_GET('MP');
        $typeParam = GeneralUtility::_GP('type');
        $actionUrl = $siteUrl . 'index.php';
        $this->fluidTemplateVariables['actionUrl'] = $actionUrl;

        // language parameter
        if (isset($lParam)) {
            $hiddenFieldValue = intval($lParam);
            $this->fluidTemplateVariables['lparam'] = $hiddenFieldValue;
        }

        // mountpoint parameter
        if (isset($mpParam)) {
            // the only allowed characters in the MP parameter are digits and , and -
            $hiddenFieldValue = preg_replace('/[^0-9,-]/', '', $mpParam);
            $this->fluidTemplateVariables['mpparam'] = $hiddenFieldValue;
        }

        // type param
        if ($typeParam) {
            $hiddenFieldValue = intval($typeParam);
            $this->fluidTemplateVariables['typeparam'] = $hiddenFieldValue;
        }

        // set reset link
        $this->fluidTemplateVariables['resetUrl'] = SearchHelper::searchLink($this->conf['resultPage']);

        // set isEmptySearch flag
        $this->fluidTemplateVariables['isEmptySearch'] = $this->isEmptySearch;
    }

    /**
     * loop through all available filters and compile the values for the fluid template rendering
     */
    public function renderFilters()
    {
        foreach ($this->filters->getFilters() as $filter) {
            // if the current filter is a "hidden filter", skip
            // rendering of this filter. The filter is only used
            // to add preselected filter options to the query and
            // must not be rendered.
            $isInList = GeneralUtility::inList($this->conf['hiddenfilters'], $filter['uid']);

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
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilterOptionsArray'] as
                         $_classRef) {
                    $_procObj = GeneralUtility::makeInstance($_classRef);
                    $options = $_procObj->modifyFilterOptionsArray($filter['uid'], $options, $this);
                }
            }

            // build link to reset this filter while keeping the others
            $resetLink= SearchHelper::searchLink($this->conf['resultPage'], $this->piVars, [$filter['uid']]);

            // set values for fluid template
            $filterData = $filter;
            $filterData['name'] = 'tx_kesearch_pi1[filter_' . $filter['uid'] . ']';
            $filterData['id'] = 'filter_' . $filter['uid'];
            $filterData['options'] = $options;
            $filterData['checkboxOptions'] = $this->compileCheckboxOptions($filter, $options);
            $filterData['optionCount'] = is_array($options) ? count($options) : 0;
            $filterData['resetLink'] = $resetLink;

            // special classes / custom code
            switch ($filter['rendertype']) {
                // use custom code for filter rendering
                // set $filterData['rendertype'] = 'custom'
                // and $filterData['rawHtmlContent'] to your pre-rendered filter code
                default:
                    // hook for custom filter renderer
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['customFilterRenderer'])) {
                        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['customFilterRenderer'] as
                                 $_classRef) {
                            $_procObj = GeneralUtility::makeInstance($_classRef);
                            $_procObj->customFilterRenderer($filter['uid'], $options, $this, $filterData);
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
     * @param integer $filterUid UID of the filter for which we need the checkboxes
     * @param array $options contains all options which are found in the search result
     * @return array list of checkboxes records
     */
    public function compileCheckboxOptions($filter, $options)
    {
        $allOptionsOfCurrentFilter = $filter['options'];

        // alphabetical sorting of filter options
        if ($filter['alphabeticalsorting'] == 1) {
            $this->sortArrayByColumn($allOptionsOfCurrentFilter, 'title');
        }

        // loop through options
        $checkboxOptions = array();
        if (is_array($allOptionsOfCurrentFilter)) {
            foreach ($allOptionsOfCurrentFilter as $key => $data) {
                $data['key'] = 'tx_kesearch_pi1[filter_' . $filter['uid'] . '_' . $key . ']';

                // check if current option (of searchresults) is in array of all possible options
                $isOptionInOptionArray = false;
                if (is_array($options)) {
                    foreach ($options as $optionInResultList) {
                        if ($optionInResultList['value'] == $data['tag']) {
                            $isOptionInOptionArray = true;
                            $data['results'] = $optionInResultList['results'];
                            break;
                        }
                    }
                }

                // if option is in optionArray, we have to mark the checkboxes
                if ($isOptionInOptionArray) {
                    // if user has selected a checkbox it must be selected on the resultpage, too.
                    // options which have been preselected in the backend are
                    // already in $this->piVars['filter'][$filter['uid]]
                    if ($this->piVars['filter'][$filter['uid']][$key]) {
                        $data['selected'] = 1;
                    }

                    // mark all checkboxes if that config options is set and no search string was given and there
                    // are no preselected filters given for that filter
                    if ($this->isEmptySearch
                        && $filter['markAllCheckboxes']
                        && empty($this->preselectedFilter[$filter['uid']])
                    ) {
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
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilterOptions'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $_procObj->modifyFilterOptions($filter, $checkboxOptions, $this);
            }
        }

        return $checkboxOptions;
    }

    /**
     * find out which filter options should be displayed for the given filter
     * check filter options availability and preselection status
     * @param array $filter
     * @return array
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 09.09.14
     */
    public function findFilterOptionsToDisplay($filter)
    {
        $optionsToDisplay = array();

        foreach ($filter['options'] as $option) {

            // build link which selects this option for this filter and keeps all the other filters
            $localPiVars = $this->piVars;
            $localPiVars['filter'][$filter['uid']] = $option['tag'];
            $optionLink = SearchHelper::searchLink($this->conf['resultPage'], $localPiVars);

            // Should we check if the filter option is available in the current search result?
            // multi --> Check for each filter option if it has results - display it only if it has results!
            // none --> Just show all filter options, no matter wether they have results or not.
            if ($this->conf['checkFilterCondition'] != 'none') {
                // Once one filter option has been selected, don't display the
                // others anymore since this leads to a strange behaviour (options are
                // only displayed if they have BOTH tags: the selected and the other filter option.
                if (
                    (!count($filter['selectedOptions']) || in_array($option['uid'], $filter['selectedOptions']))
                    && $this->filters->checkIfTagMatchesRecords($option['tag'])
                ) {

                    $optionsToDisplay[$option['uid']] = array(
                        'title' => $option['title'],
                        'value' => $option['tag'],
                        'results' => $this->tagsInSearchResult[$option['tag']],
                        'selected' =>
                            is_array($filter['selectedOptions'])
                            && !empty($filter['selectedOptions'])
                            && in_array($option['uid'], $filter['selectedOptions']),
                        'link' => $optionLink
                    );
                }
            } else {
                // do not process any checks; show all filter options
                $optionsToDisplay[$option['uid']] = array(
                    'title' => $option['title'],
                    'value' => $option['tag'],
                    'selected' =>
                        is_array($filter['selectedOptions'])
                        && !empty($filter['selectedOptions'])
                        && in_array($option['uid'], $filter['selectedOptions']),
                    'link' => $optionLink
                );

                // If no filter option has been selected yet, we can show the number of results per filter option.
                // After a filter option has been selected this does not make sense anymore because the number of
                // number of results per filter option is calculated from the current result set, not from the
                // full index but since 'checkFilterConditon' is set to 'none' at this point all filter options are shown.
                if ($filter['shownumberofresults'] && !count($filter['selectedOptions'])) {
                    if ($this->filters->checkIfTagMatchesRecords($option['tag'])) {
                        $optionsToDisplay[$option['uid']]['results'] = $this->tagsInSearchResult[$option['tag']];
                    } else {
                        $optionsToDisplay[$option['uid']]['results'] = 0;
                    }
                }
            }
        }

        return $optionsToDisplay;
    }

    /**
     * renders brackets around the number of results, returns an empty
     * string if there are no results or if an option for this filter already
     * has been selected.
     * @param integer $numberOfResults
     * @param array $filter
     * @return string
     */
    public function renderNumberOfResultsString($numberOfResults, $filter)
    {
        if ($filter['shownumberofresults'] && !count($filter['selectedOptions']) && $numberOfResults) {
            $returnValue = ' (' . $numberOfResults . ')';
        } else {
            $returnValue = '';
        }
        return $returnValue;
    }

    /**
     * set the text for "no results"
     */
    public function setNoResultsText()
    {
        // no results found
        if ($this->conf['showNoResultsText']) {
            // use individual text set in flexform
            $noResultsText = $this->pi_RTEcssText($this->conf['noResultsText']);
        } else {
            // use general text
            $noResultsText = $this->pi_getLL('no_results_found');
        }

        // hook to implement your own idea of a no result message
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['noResultsHandler'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['noResultsHandler'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $_procObj->noResultsHandler($noResultsText, $this);
            }
        }

        // fill the fluid template marker
        $this->fluidTemplateVariables['noResultsText'] = $noResultsText;
    }

    /**
     * creates the search result list
     * 1. does the actual searching (fetches the results to $rows)
     * 2. fills fluid variables for fluid templates to $this->fluidTemplateVariables
     * @return void
     */
    public function getSearchResults()
    {
        // set switch for too short words
        $this->fluidTemplateVariables['wordsTooShort'] = $this->hasTooShortWords ? 1 : 0;

        if($this->isEmptySearch() && !$this->allowEmptySearch()) {
            return;
        }

        // fetch the search results
        $limit = $this->db->getLimit();
        $rows = $this->db->getSearchResults();
        $this->fluidTemplateVariables['errors'] = $this->db->getErrors();

        // TODO: Check how Sphinx handles this, seems to return full result set
        if (count($rows) > $limit[1]) {
            $rows = array_slice($rows, $limit[0], $limit[1]);
        }

        // set number of results
        $this->numberOfResults = $this->db->getAmountOfSearchResults();

        // count search phrase in ke_search statistic tables
        if ($this->conf['countSearchPhrases']) {
            $this->countSearchPhrase($this->sword, $this->swords, $this->numberOfResults, $this->tagsAgainst);
        }

        // render "no results" text and stop here
        if ($this->numberOfResults == 0) {
            $this->setNoResultsText();
        }

        // init counter and loop through the search results
        $resultCount = 1;
        $this->searchResult = GeneralUtility::makeInstance(Searchresult::class, $this);

        $this->fluidTemplateVariables['resultrows'] = array();
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $this->searchResult->setRow($row);

                /** @var GenericRepository $genericRepository */
                $genericRepository = GeneralUtility::makeInstance(GenericRepository::class);
                $tempMarkerArray = array(
                    'orig_uid' => $row['orig_uid'],
                    'orig_pid' => $row['orig_pid'],
                    'orig_row' => $genericRepository->findByUidAndType($row['orig_uid'], $row['type']),
                    'title_text' => $row['title'],
                    'content_text' => $row['content'],
                    'title' => $this->searchResult->getTitle(),
                    'teaser' => $this->searchResult->getTeaser(),
                );

                // hook for additional markers in result row
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['additionalResultMarker'])) {
                    // make curent row number available to hook
                    $this->currentRowNumber = $resultCount;
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['additionalResultMarker'] as $_classRef) {
                        $_procObj = GeneralUtility::makeInstance($_classRef);
                        $_procObj->additionalResultMarker($tempMarkerArray, $row, $this);
                    }
                    unset($this->currentRowNumber);
                }

                // add type marker
                // for file results just use the "file" type, not the file extension (eg. "file:pdf")
                list($type) = explode(':', $row['type']);
                $tempMarkerArray['type'] = str_replace(' ', '_', $type);

                // use the markers array as a base for the fluid template values
                $resultrowTemplateValues = $tempMarkerArray;

                // set result url
                $resultUrl = $this->searchResult->getResultUrl($this->conf['renderResultUrlAsLink']);
                $resultrowTemplateValues['url'] = $resultUrl;

                // set result numeration
                $resultNumber = $resultCount
                    + ($this->piVars['page'] * $this->conf['resultsPerPage'])
                    - $this->conf['resultsPerPage'];
                $resultrowTemplateValues['number'] = $resultNumber;

                // set date (formatted and raw as a timestamp)
                $resultDate = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $row['sortdate']);
                $resultrowTemplateValues['date'] = $resultDate;
                $resultrowTemplateValues['date_timestamp'] = $row['sortdate'];

                // show tags?
                $tags = $row['tags'];
                $tags = str_replace('#', ' ', $tags);
                $resultrowTemplateValues['tags'] = $tags;

                // set preview image and/or type icons
                // for files we have the corresponding entry in sys_file as "orig_uid" available (not sys_file_reference)
                // for pages and news we have to fetch the file reference uid
                if ($type == 'file') {
                    if ($this->conf['showFilePreview']) {
                        $resultrowTemplateValues['filePreviewId'] = $row['orig_uid'];
                    }
                    $resultrowTemplateValues['treatIdAsReference'] = 0;
                } else {
                    $resultrowTemplateValues['filePreviewId'] = $this->getFileReference($row);
                    $resultrowTemplateValues['treatIdAsReference'] = 1;
                }

                // get the icon for the current record type
                $resultrowTemplateValues['typeIconPath'] = $this->getTypeIconPath($row['type']);

                // set end date for cal events
                if ($type == 'cal') {
                    $resultrowTemplateValues['cal'] = $this->getCalEventEnddate($row['orig_uid']);
                }

                // add result row to the variables array
                $this->fluidTemplateVariables['resultrows'][] = $resultrowTemplateValues;

                // increase result counter
                $resultCount++;
            }
        }
    }

    /**
     * get file reference for image rendering in fluid
     *
     * @param $row
     * @return int uid of preview image file reference
     * @author Andreas Kiefer <andreas.kiefer@pluswerk.ag>
     */
    public function getFileReference($row)
    {
        list($type) = explode(':', $row['type']);
        switch ($type) {
            case 'page':
                if ($this->conf['showPageImages']) {

                    // first check if "tx_kesearch_resultimage" is set
                    $result = $this->getFirstFalRelationUid(
                        'pages', 'tx_kesearch_resultimage', $row['orig_uid']
                    );

                    // fallback to standard "media" field
                    if (empty($result)) {
                        $result = $this->getFirstFalRelationUid(
                            'pages', 'media', $row['orig_uid']
                        );
                    }
                    return $result;
                }
                break;

            case 'tt_news':
                if ($this->conf['showNewsImages']) {
                    return $this->getFirstFalRelationUid(
                        'tt_news', 'image', $row['orig_uid']
                    );
                }
                break;

            case 'news':
                if ($this->conf['showNewsImages']) {
                    return $this->getFirstFalRelationUid(
                        'tx_news_domain_model_news', 'fal_media', $row['orig_uid']
                    );
                }
                break;

            default:
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['fileReferenceTypes'])
                    && isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['fileReferenceTypes'][$type])) {
                    return $this->getFirstFalRelationUid(
                        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['fileReferenceTypes'][$type]['table'], $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['fileReferenceTypes'][$type]['field'], $row['orig_uid']
                    );
                }
                break;
        }
    }

    /**
     * get path for type icon used for rendering in fluid
     *
     * @param $type
     * @return string the path to the type icon file
     */
    public function getTypeIconPath($typeComplete)
    {
        list($type) = explode(':', $typeComplete);
        $name = str_replace(':', '_', $typeComplete);

        if ($this->conf['resultListTypeIcon'][$name]) {
            // custom icons defined by typoscript
            return $this->conf['resultListTypeIcon'][$name]['file'];
        } else {
            // default icons from ext:ke_search
            $extensionIconPath = 'EXT:ke_search/Resources/Public/Icons/types/' . $name . '.gif';
            if (is_file(GeneralUtility::getFileAbsFileName($extensionIconPath))) {
                return $extensionIconPath;
            } else if ($type == 'file') {
                // fallback for file results: use default if no image for this file extension is available
                return 'EXT:ke_search/Resources/Public/Icons/types/file.gif';
            } else {
                // fallback if no icon found
                return 'EXT:ke_search/Resources/Public/Icons/types/default.gif';
            }
        }
    }

    /**
     * @param $table
     * @param $field
     * @param $uid
     * @return mixed
     */
    public function getFirstFalRelationUid($table, $field, $uid)
    {
        $queryBuilder = Db::getQueryBuilder($table);
        $row = $queryBuilder
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->quote($table, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->quote($field, \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->quote($uid, \PDO::PARAM_INT)
                )
            )
            ->orderBy('sorting_foreign', 'asc')
            ->setMaxResults(1)
            ->execute()
            ->fetch();

        if ($row !== NULL) {
            return $row['uid'];
        }

    }

    /**
     * Fetches configuration value given its name.
     * Merges flexform and TS configuration values.
     * @param    string $param Configuration value name
     * @return    string    Parameter value
     */
    public function fetchConfigurationValue($param, $sheet = 'sDEF')
    {
        $value = trim(
            $this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                $param,
                $sheet
            )
        );
        return $value ? $value : $this->conf[$param];
    }


    /**
     * function betterSubstr
     * better substring function
     * @param $str string
     * @param $length integer
     * @param $minword integer
     * @return string
     */
    public function betterSubstr($str, $length = 0, $minword = 3)
    {
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


    /**
     * render parts for the pagebrowser
     */
    public function renderPagebrowser()
    {
        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $view */
        $view = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $view->setTemplateRootPaths($this->conf['view']['templateRootPaths']);
        $view->setTemplate('Widget/Pagination');
        $pagination = [];

        $numberOfResults = $this->numberOfResults;
        $resultsPerPage = $this->conf['resultsPerPage'];
        $maxPages = $this->conf['maxPagesInPagebrowser'];

        // get total number of items to show
        // show pagebrowser only if there are more entries that are shown on one page
        if ($numberOfResults > $resultsPerPage) {
            $this->limit = $resultsPerPage;
        } else {
            return;
        }

        // set db limit
        $start = ($this->piVars['page'] * $resultsPerPage) - $resultsPerPage;
        $this->dbLimit = $start . ',' . $resultsPerPage;
        $end = ($start + $resultsPerPage > $numberOfResults) ? $numberOfResults : ($start + $resultsPerPage);

        // number of pages
        $pagesTotal = ceil($numberOfResults / $resultsPerPage);

        // calculate start and end page
        $startPage = $this->piVars['page'] - ceil(($maxPages / 2));
        $endPage = $startPage + $maxPages - 1;
        if ($startPage < 1) {
            $startPage = 1;
            $endPage = $startPage + $maxPages - 1;
        }
        if ($startPage > $pagesTotal) {
            $startPage = $pagesTotal - $maxPages + 1;
            $endPage = $pagesTotal;
        }
        if ($endPage > $pagesTotal) {
            $startPage = $pagesTotal - $maxPages + 1;
            $endPage = $pagesTotal;
        }

        // create pages list, previous, current and next for pagination widget
        $pages = [];
        for ($i = 1; $i <= $pagesTotal; $i++) {
            if ($i >= $startPage && $i <= $endPage) {
                $pages[] = $i;
            }
        }
        $pagination['pages'] = $pages;

        if ($this->piVars['page'] > 1) {
            $pagination['previous'] = $this->piVars['page'] - 1;
        }

        if ($this->piVars['page'] < $pagesTotal) {
            $pagination['next'] = $this->piVars['page'] + 1;
        }

        $pagination['currentPage'] = $this->piVars['page'];
        $pagination['sword'] = $this->sword;
        $view->assign('pagination', $pagination);

        // render pagebrowser content and pass it together with some variables to fluid template
        $markerArray = array(
            'current' => $this->piVars['page'],
            'pages_total' => $pagesTotal,
            'pages_list' => $view->render(),
            'start' => $start + 1,
            'end' => $end,
            'total' => $numberOfResults,
            'results' => $this->pi_getLL('results'),
            'until' => $this->pi_getLL('until'),
            'of' => $this->pi_getLL('of'),
        );

        // hook for additional markers in pagebrowser
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['pagebrowseAdditionalMarker'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['pagebrowseAdditionalMarker'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $_procObj->pagebrowseAdditionalMarker(
                    $markerArray,
                    $this
                );
            }
        }

        $this->fluidTemplateVariables['pagebrowser'] = $markerArray;
    }


    public function renderOrdering()
    {
        /** @var Sorting $sortObj */
        $sortObj = GeneralUtility::makeInstance(Sorting::class, $this);
        $sortObj->renderSorting($this->fluidTemplateVariables);
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
    public function countSearchPhrase($searchPhrase, $searchWordsArray, $hits, $tagsAgainst)
    {

        // prepare language aspect
        $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');

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
                'searchphrase' => $searchPhrase,
                'tstamp' => time(),
                'hits' => $hits,
                'tagsagainst' => $tagsAgainst,
                'language' => $languageAspect->getId(),
            );
            $queryBuilder = Db::getQueryBuilder($table);
            $queryBuilder
                ->insert($table)
                ->values($fields_values)
                ->execute();
        }

        // count single words
        foreach ($searchWordsArray as $searchWord) {
            if (extension_loaded('mbstring')) {
                $searchWord = mb_strtolower($searchWord, 'UTF-8');
            } else {
                $searchWord = strtolower($searchWord);
            }
            $table = 'tx_kesearch_stat_word';
            if (!empty($searchWord)) {
                $queryBuilder = Db::getQueryBuilder($table);
                $fields_values = array(
                    'pid' => $this->firstStartingPoint,
                    'word' => $searchWord,
                    'tstamp' => time(),
                    'pageid' => $GLOBALS['TSFE']->id,
                    'resultsfound' => $hits ? 1 : 0,
                    'language' => $languageAspect->getId(),
                );
                $queryBuilder
                    ->insert($table)
                    ->values($fields_values)
                    ->execute();
            }
        }
    }


    /**
     * gets all preselected filters from flexform
     * returns nothing but fills global var with needed data
     * @return void
     */
    public function getFilterPreselect()
    {
        // get definitions from plugin settings
        // and proceed only when preselectedFilter was not set
        // this reduces the amount of sql queries, too
        if ($this->conf['preselected_filters'] && count($this->preselectedFilter) == 0) {
            $preselectedArray = GeneralUtility::trimExplode(',', $this->conf['preselected_filters'], true);
            foreach ($preselectedArray as $option) {
                $option = intval($option);
                $queryBuilder = Db::getQueryBuilder('tx_kesearch_filters');
                $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
                $filterRows = $queryBuilder
                    ->add(
                        'select',
                        '`tx_kesearch_filters`.`uid` AS filteruid, `tx_kesearch_filteroptions`.`uid` AS optionuid, `tx_kesearch_filteroptions`.`tag`'
                    )
                    ->from('tx_kesearch_filters')
                    ->from('tx_kesearch_filteroptions')
                    ->add(
                        'where',
                        'FIND_IN_SET("' . $option . '",tx_kesearch_filters.options)'
                        . ' AND `tx_kesearch_filteroptions`.`uid` = ' . $option
                        . $pageRepository->enableFields('tx_kesearch_filters')
                        . $pageRepository->enableFields('tx_kesearch_filteroptions')
                    )
                    ->execute()
                    ->fetchAll();

                foreach ($filterRows as $row) {
                    $this->preselectedFilter[$row['filteruid']][$row['optionuid']] = $row['tag'];
                }
            }
        }
    }


    /**
     * function isEmptySearch
     * checks if an empty search was loaded / submitted
     * @return boolean true if no searchparams given; otherwise false
     */
    public function isEmptySearch()
    {
        // check if searchword is emtpy or equal with default searchbox value
        $emptySearchword = empty($this->sword) || $this->sword == $this->pi_getLL('searchbox_default_value');

        // check if filters are set
        $filters = $this->filters->getFilters();
        $filterSet = false;
        if (is_array($filters)) {
            foreach ($filters as $filter) {
                if (!empty($this->piVars['filter'][$filter['uid']])) {
                    $filterSet = true;
                }
            }
        }

        if ($emptySearchword && !$filterSet) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $eventUid The uid is passed as string, but we know that for Cal this is an integer
     * @return array
     */
    public function getCalEventEnddate($eventUid)
    {
        $table = 'tx_cal_event';
        $queryBuilder = Db::getQueryBuilder($table);
        $row = $queryBuilder
            ->select('end_date', 'end_time', 'allday', 'start_date')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch(0);

        return array(
            'end_timestamp' => strtotime($row['end_date']) + $row['end_time'],
            'end_date' => strtotime($row['end_date']),
            'end_time' => $row['end_time'],
            'allday' => $row['allday'],
            'sameday' => ($row['end_date'] == $row['start_date']) ? 1 : 0
        );
    }

    /**
     * @param array $array
     * @param string $field
     * @return array
     */
    public function sortArrayRecursive($array, $field)
    {
        $sortArray = array();
        $mynewArray = array();

        $i = 1;
        foreach ($array as $point) {
            $sortArray[] = $point[$field] . $i;
            $i++;
        }
        rsort($sortArray);

        foreach ($sortArray as $sortet) {
            $i = 1;
            foreach ($array as $point) {
                $newpoint[$field] = $point[$field] . $i;
                if ($newpoint[$field] == $sortet) {
                    $mynewArray[] = $point;
                }
                $i++;
            }
        }
        return $mynewArray;
    }

    /**
     * @param array $wert_a
     * @param array $wert_b
     * @return int
     */
    public function sortArrayRecursive2($wert_a, $wert_b)
    {
        // sort using the second value of the array (index: 1)
        $a = $wert_a[2];
        $b = $wert_b[2];

        if ($a == $b) {
            return 0;
        }

        return ($a < $b) ? -1 : +1;
    }

    /**
     * implements a recursive in_array function
     * @param mixed $needle
     * @param array $haystack
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 11.07.12
     * @return bool
     */
    public function in_multiarray($needle, $haystack)
    {
        foreach ($haystack as $value) {
            if (is_array($value)) {
                if ($this->in_multiarray($needle, $value)) {
                    return true;
                }
            } else {
                if ($value == $needle) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Sort array by given column
     * @param array $arr the array
     * @param string $col the column
     * @return void
     */
    public function sortArrayByColumn(&$arr, $col)
    {
        $newArray = [];
        $sort_col = array();
        foreach ($arr as $key => $row) {
            $sort_col[$key] = strtoupper($row[$col]);
        }
        asort($sort_col, SORT_LOCALE_STRING);

        foreach ($sort_col as $key => $val) {
            $newArray[$key] = $arr[$key];
        }

        $arr = $newArray;
    }

    private function allowEmptySearch()
    {
        /*
         * check for inequality to null to maintain functionality even if unset
         */
        if ($this->extConf['allowEmptySearch'] != null && $this->extConf['allowEmptySearch'] == false) {
            return false;
        }
        return true;
    }
}
