<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Stefan Froemken 
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
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
 * @author	Stefan Froemken 
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_lib_filters_textlinks {

	var $conf = array();
	var $templateArray = array();
	var $countActiveOptions = 0;
	var $contentOfHiddenFields = array();
	var $maxAllowedNormalOptions = 0;
	var $contentOfActiveOptions = array();
	var $contentOfNormalOptions = array();

	/**
	 * @var tx_kesearch_lib
	 */
	var $pObj;

	/**
	* @var tslib_cObj
	*/
	var $cObj;





	/**
	 * The constructor of this class
	 *
	 * @param tx_kesearch_lib $pObj
	 */
	public function __construct(tx_kesearch_lib $pObj) {
		// initializes this object
		$this->init($pObj);
	}


	/**
	 * Initializes this object
	 *
	 * @param tx_kesearch_lib $pObj
	 * @return void
	 */
	public function init(tx_kesearch_lib $pObj) {
		$this->pObj = $pObj;
		$this->cObj = $this->pObj->cObj;
		$this->conf = $this->pObj->conf;

		// reset global values
		$this->countActiveOptions = 0;
		$this->contentOfHiddenFields = array();
		$this->contentOfActiveOptions = array();
		$this->contentOfNormalOptions = array();

		// set template array
		$this->templateArray['filter'] = $this->cObj->getSubpart($this->pObj->templateCode, '###SUB_FILTER_TEXTLINKS###');
		$this->templateArray['options'] = $this->cObj->getSubpart($this->pObj->templateCode, '###SUB_FILTER_TEXTLINK_OPTION###');
	}


	/**
	 * The main entry point of this class
	 * It will return the complete HTML for textlinks
	 *
	 * @param integer $filterUid uid of the current loopd filter
	 * @param array $optionsOfSearchresult All found options in current search result
	 * @return string HTML
	 */
	public function renderTextlinks($filterUid, $optionsOfSearchresult) {
		$filters = $this->pObj->filters->getFilters();
		$filter = $filters[$filterUid];

		if(!is_array($filter) || count($filter) == 0) return '';

		// get options
		$optionsOfFilter = $this->getOptionsOfFilter($filter, $optionsOfSearchresult);
		if(!is_array($optionsOfFilter) || count($optionsOfFilter) == 0) return '';

		// alphabetical sorting of filter options
		if ($filter['alphabeticalsorting'] == 1) {
			$this->pObj->sortArrayByColumn($optionsOfFilter, 'title');
		}

		$this->maxAllowedNormalOptions = $filter['amount'];

		if(is_array($this->pObj->piVars['filter'][$filterUid]) && count($this->pObj->piVars['filter'][$filterUid])) {
			$piVarsOptionList = implode(',', array_keys($this->pObj->piVars['filter'][$filterUid]));
			$optionsOfFilter = $this->pObj->getFilterOptions($piVarsOptionList);
		}

		foreach($optionsOfFilter as $key => $data) {
			$this->saveRenderedTextlinkToGlobalArrays($filterUid, $data);
		}

		if(is_array($this->contentOfActiveOptions) && count($this->contentOfActiveOptions)) {
			foreach($this->contentOfActiveOptions as $option) {
				$contentOptions .= $option;
			}
		} else {
			foreach($this->contentOfNormalOptions as $option) {
				$contentOptions .= $option;
			}
		}

		// modify filter options by hook
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilterOptions'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilterOptions'] as $_classRef) {
				if (TYPO3_VERSION_INTEGER >= 7000000) {
					$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				} else {
					$_procObj = & t3lib_div::getUserObj($_classRef);
				}
				$contentOptions .= $_procObj->modifyFilterOptions(
					$filterUid,
					$contentOptions,
					count($optionsOfFilter),
					$this
				);
			}
		}

		unset($markerArray);

		// render filter
		$contentFilters = $this->cObj->substituteSubpart(
			$this->templateArray['filter'],
			'###SUB_FILTER_TEXTLINK_OPTION###',
			$contentOptions
		);

		// get title
		$filterTitle = $filter['title'];
		$filter['target_pid'] = ($filter['target_pid']) ? $filter['target_pid'] : $this->conf['resultPage'];

		// fill markers
		$markerArray['###FILTERTITLE###'] = htmlspecialchars($filterTitle);
		$markerArray['###HIDDEN_FIELDS###'] = implode(CHR(10), $this->contentOfHiddenFields);

		$exclude = 'tx_kesearch_pi1[page],tx_kesearch_pi1[multi],tx_kesearch_pi1[filter][' . $filterUid . ']';

		if($this->countActiveOptions) {
			$markerArray['###LINK_MULTISELECT###'] = '';
			$markerArray['###LINK_RESET_FILTER###'] = $this->cObj->typoLink(
				$this->pObj->pi_getLL('reset_filter'),
				array(
					'parameter' => $this->conf['resultPage'],
					'addQueryString' => 1,
					'addQueryString.' => array(
						'exclude' => $exclude
					)
				)
			);
		} else {
			// check if there is a special translation for current filter
			$linkTextMore = $this->pObj->pi_getLL('linktext_more_' . $filterUid, $this->pObj->pi_getLL('linktext_more'));
			$markerArray['###LINK_MULTISELECT###'] = $this->cObj->typoLink(
				sprintf($linkTextMore, $filterTitle),
				array(
					'parameter' => $filter['target_pid'],
					'addQueryString' => 1,
					'addQueryString.' => array(
						'exclude' => 'id,tx_kesearch_pi1[page],tx_kesearch_pi1[multi]'
					)
				)
			);
			$markerArray['###LINK_RESET_FILTER###'] = '';
		}

		$contentFilters = $this->cObj->substituteMarkerArray($contentFilters, $markerArray);
		return $contentFilters;
	}


	/**
	 * get options of given filter and regarding current search result
	 * Only options which are also found in result will be returned
	 *
	 * @param array $filter The current looped filter
	 * @param array $additionalOptionValues This is an array with some additional informations for each filteroption (title, tag, amount of records, selected)
	 * @return array A merged, sorted and complete Array with all option values we need
	 */
	public function getOptionsOfFilter($filter, $additionalOptionValues) {
		if(is_array($filter) && count($filter) && is_array($additionalOptionValues)) {
			// get all options
			$filters = $this->pObj->filters->getFilters();
			$allOptionsOfCurrentFilter = $filters[$filter['uid']]['options'];
			// build intersection of both arrays
			$optionsOfCurrentFilter = array_intersect_key($allOptionsOfCurrentFilter, $additionalOptionValues);
			// merge additional values into option array
			if (TYPO3_VERSION_INTEGER >= 7000000) {
				\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($optionsOfCurrentFilter, (array)$additionalOptionValues);
			} else {
				$optionsOfCurrentFilter = t3lib_div::array_merge_recursive_overrule((array)$optionsOfCurrentFilter, (array)$additionalOptionValues);
			}
			// return sorted option array
			return $this->sortMultiDimArray($optionsOfCurrentFilter);
		} else return array();
	}


	/**
	 * Sort multidimensional array
	 * Hint: This method keeps the original Array keys
	 *
	 * @param array $optionArray
	 * @return array Array with original Keys
	 */
	public function sortMultiDimArray($optionArray) {
		if(is_array($optionArray) && count($optionArray)) {
			// temporary saving all keys
			$allOptionKeys = array_keys($optionArray);

			// prepare our array for sorting
			foreach((array)$optionArray as $key => $array) {
				$results[$key] = $array['results'];
				$tags[$key] = $array['tag'];
			}

			// sort multidim array
			array_multisort($results, SORT_DESC, SORT_NUMERIC, $tags, SORT_ASC, SORT_STRING, $allOptionKeys, SORT_DESC, SORT_NUMERIC, $optionArray);

			// after multisort all keys are 0,1,2,3. So we have to restore our old keys
			if(count($allOptionKeys) && count(array_values($optionArray))) {
				return array_combine($allOptionKeys, array_values($optionArray));
			} else {
				return array();
			}
		} else {
			return array();
		}
	}


	/**
	 * a little factory to decide which kind of textlink has to be rendered
	 * and save the result to global arrays
	 *
	 * @param integer $filterUid The filter uid
	 * @param array $option An array containing the current option record
	 * @return string The rendered text link
	 */
	public function saveRenderedTextlinkToGlobalArrays($filterUid, $option) {
		if($this->pObj->piVars['filter'][$filterUid][$option['uid']]) {
			$this->contentOfActiveOptions[] = $this->renderTextlinkForActiveOption($filterUid, $option);
		} elseif(empty($this->pObj->piVars['filter'][$filterUid])) {
			if(count($this->contentOfNormalOptions) < intval($this->maxAllowedNormalOptions)) {
				$this->contentOfNormalOptions[] = $this->renderTextlinkForNormalOption($filterUid, $option);
			}
		} else return '';
	}


	/**
	 * render textlink for active option
	 *
	 * @param integer $filterUid The filter uid
	 * @param array $option An array containing the option
	 * @return string A HTML formatted textlink
	 */
	public function renderTextlinkForActiveOption($filterUid, $option) {
		// if multi is set AND option(s) of current filter is set by piVars
		// then more than one entry can be selected
		//if($this->pObj->piVars['multi'] && $this->pObj->piVars['filter'][$filterUid][$option['uid']]) {
		$this->countActiveOptions++;
		$markerArray['###CLASS###'] = 'active';
		$markerArray['###TEXTLINK###'] = htmlspecialchars($option['title']);
		$this->contentOfHiddenFields[] = $this->renderHiddenField($filterUid, $option);
		return $this->cObj->substituteMarkerArray($this->templateArray['options'], $markerArray);
	}


	/**
	 * render textlink for normal option
	 *
	 * @param integer $filterUid The filter uid
	 * @param array $option An array containing the option
	 * @return string A HTML formatted textlink
	 */
	public function renderTextlinkForNormalOption($filterUid, $option) {
		// if multi is set AND option(s) of current filter is set by piVars
		// then more than one entry can be selected
		//if($this->pObj->piVars['multi'] && $this->pObj->piVars['filter'][$filterUid][$option['uid']]) {
		$markerArray['###CLASS###'] = 'normal';
		$markerArray['###TEXTLINK###'] = $this->generateLink($filterUid, $option);
		return $this->cObj->substituteMarkerArray($this->templateArray['options'], $markerArray);
	}


	/**
	 * generate the link for normal textlinks
	 *
	 * @param string $filterUid
	 * @param string $option
	 * @return string The complete link as A-Tag
	 */
	public function generateLink($filterUid, $option) {
		$filters = $this->pObj->filters->getFilters();
		$params = array();
		$params[] = '[page]=1';
		$params[] = '[filter][' . $filterUid . '][' . $option['uid'] . ']=' . $option['tag'];

		$excludes = array();
		$excludes[] = 'id';
		$excludes[] = 'tx_kesearch_pi1[multi]';

		// hook: modifyParamsForTextlinks
		// This is useful if you want to define special sortings for each textlink
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyParamsForTextlinks'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyParamsForTextlinks'] as $_classRef) {
				if (TYPO3_VERSION_INTEGER >= 7000000) {
					$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				} else {
					$_procObj = & t3lib_div::getUserObj($_classRef);
				}
				$_procObj->modifyParamsForTextlinks($params, $excludes, $option, $this->conf, $this->pObj);
			}
		}

		foreach($params as $key => $value) {
			$params[$key] = $this->cObj->wrap($value, $this->pObj->prefixId . '|');
		}

		$conf = array();
		$conf['parameter'] = $this->conf['resultPage'];
		$conf['addQueryString'] = '1';
		$conf['addQueryString.']['exclude'] = implode(',', $excludes);
		$conf['additionalParams'] = '&' . implode('&', $params);

		$number_of_results = $this->pObj->renderNumberOfResultsString($option['results'], $filters[$filterUid]);

		return $this->cObj->typoLink($option['title'] . $number_of_results, $conf);
	}


	/**
	 * render a hidden field
	 * They are needed to not forget setted options while search for another word
	 *
	 * @param integer $filterUid The filter uid
	 * @param array $option An array containing the option
	 * @return string A HTML formatted hidden input field
	 */
	public function renderHiddenField($filterUid, $option) {
		$attributes = array();
		$attributes['type'] = 'hidden';
		$attributes['name'] = 'tx_kesearch_pi1[filter][' . $filterUid . '][' . $option['uid'] . ']';
		$attributes['id'] = 'tx_kesearch_pi1_' . $filterUid . '_' . $option['uid'];
		$attributes['value'] = htmlspecialchars($option['tag']);

		foreach($attributes as $key => $attribut) {
			$attributes[$key] = $key . $this->cObj->wrap($attribut, '="|"');
		}

		return '<input ' . implode(' ', $attributes) . ' />';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/lib/filters/class.tx_kesearch_lib_filters_textlinks.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/lib/filters/class.tx_kesearch_lib_filters_textlinks.php']);
}
?>
