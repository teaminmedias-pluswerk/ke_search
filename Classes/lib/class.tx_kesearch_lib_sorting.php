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
 * @author	Stefan Froemken 
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_lib_sorting {

	var $conf = array();
	var $subpartArray = array();
	var $sortBy = '';

	/**
	 * @var tx_kesearch_lib
	 */
	var $pObj;

	/**
	* @var tx_kesearch_db
	*/
	var $db;

	/**
	* @var tslib_cObj
	*/
	var $cObj;

	/**
	* @var tx_kesearch_lib_div
	*/
	var $div;





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
		$this->db = $this->pObj->db;
		$this->cObj = $this->pObj->cObj;
		$this->conf = $this->pObj->conf;

		// get subparts
		$this->subpartArray['###ORDERNAVIGATION###'] = $this->cObj->getSubpart($this->pObj->templateCode, '###ORDERNAVIGATION###');
		$this->subpartArray['###SORT_LINK###'] = $this->cObj->getSubpart($this->subpartArray['###ORDERNAVIGATION###'], '###SORT_LINK###');

		// get sorting values (sortdate, title, what ever...)
		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$this->sortBy = TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->conf['sortByVisitor'], true);
		} else {
			$this->sortBy = t3lib_div::trimExplode(',', $this->conf['sortByVisitor'], true);
		}
	}


	/**
	 * The main entry point of this class
	 * It will return the complete sorting HTML
	 *
	 * @return string HTML
	 */
	public function renderSorting() {
		// show sorting:
		// if show Sorting is activated in FlexForm
		// if a value to sortBy is set in FlexForm (title, relevance, sortdate, what ever...)
		// if there are any entries in current search results
		if($this->conf['showSortInFrontend'] && !empty($this->conf['sortByVisitor']) && $this->pObj->numberOfResults) {
			// loop all allowed orderings
			foreach($this->sortBy as $field) {
				// we can't sort by score if there is no sword given
				if($this->pObj->sword != '' || $field != 'score') {
					$sortByDir = $this->getDefaultSortingDirection($field);
					if (TYPO3_VERSION_INTEGER >= 7000000) {
						$dbOrdering = TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $this->db->getOrdering());
					} else {
						$dbOrdering = t3lib_div::trimExplode(' ', $this->db->getOrdering());
					}

					/* if ordering direction is the same change it
					 *
					 * Explaintation:
					 * No ordering is active. Default Ordering by db is "sortdate desc".
					 * Default ordering by current field is also "sortdate desc".
					 * So...if you click the link for sortdate it will sort the results by "sortdate desc" again
					 * To prevent this we change the default ordering here
					 */
					if($field == $dbOrdering[0] && $sortByDir == $dbOrdering[1]) {
						$sortByDir = $this->changeOrdering($sortByDir);
					}

					$markerArray['###FIELDNAME###'] = $field;
					$markerArray['###URL###'] = $this->generateSortingLink($field, $sortByDir);
					$markerArray['###CLASS###'] = $this->getClassNameForUpDownArrow($field, $dbOrdering);

					$links .= $this->cObj->substituteMarkerArray($this->subpartArray['###SORT_LINK###'], $markerArray);
				}
			}

			$content = $this->cObj->substituteSubpart($this->subpartArray['###ORDERNAVIGATION###'], '###SORT_LINK###', $links);
			$content = $this->cObj->substituteMarker($content, '###LABEL_SORT###', $this->pObj->pi_getLL('label_sort'));

			return $content;
		} else {
			return '';
		}
	}


	/**
	 * get default sorting direction
	 * f.e. default sorting for sortdate should be DESC. The most current records at first
	 * f.e. default sorting for relevance should be DESC. The best relevance at first
	 * f.e. default sorting for title should be ASC. Alphabetic order begins with A.
	 *
	 * @param string The field name to sort by
	 * @return string The default sorting (asc/desc) for given field
	 */
	public function getDefaultSortingDirection($field) {
		if(!empty($field) && is_string($field)) {
			switch($field) {
				case 'sortdate':
				case 'score':
					$orderBy = 'desc';
					break;
				case 'title':
				default:
					$orderBy = 'asc';
					break;
			}
			return $orderBy;
		} else return 'asc';
	}


	/**
	 * change ordering
	 * f.e. asc to desc and desc to asc
	 *
	 * @param string $direction asc or desc
	 * @return string desc or asc. If you call this function with a not allowed string, exactly this string will be returned. Short: The function do nothing
	 */
	public function changeOrdering($direction) {
		$allowedDirections = array('asc', 'desc');
		$direction = strtolower($direction);
		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$isInArray = TYPO3\CMS\Core\Utility\GeneralUtility::inArray($allowedDirections, $direction);
		} else {
			$isInArray = t3lib_div::inArray($allowedDirections, $direction);
		}
		if(!empty($direction) && $isInArray) {
			if($direction == 'asc') {
				$direction = 'desc';
			} else $direction = 'asc';
		} return $direction;
	}


	/**
	 * get a class name for up and down arrows of sorting links
	 *
	 * @param string $field current field to sort by
	 * @param array $dbOrdering An array containing the field and ordering of current DB Ordering
	 * @return string The class name
	 */
	public function getClassNameForUpDownArrow($field, $dbOrdering) {
		$className = '';
		if(is_array($dbOrdering) && count($dbOrdering)) {
			if($field == $dbOrdering[0]) {
				if($dbOrdering[1] == 'asc') {
					$className = 'up';
				} else $className = 'down';
			}
		}
		return $className;
	}


	/**
	 * generate the link for the given sorting value
	 *
	 * @param string $field
	 * @param string $sortByDir
	 * @return string The complete link as A-Tag
	 */
	public function generateSortingLink($field, $sortByDir) {
		$params = array();
		$params['sortByField'] = $field;
		$params['sortByDir'] = $sortByDir;

		foreach($params as $key => $value) {
			$params[$key] = $this->cObj->wrap($value, $this->pObj->prefixId . '[' . $key . ']=|');
		}

		$conf = array();
		$conf['parameter'] = $GLOBALS['TSFE']->id;
		$conf['addQueryString'] = '1';
		$conf['addQueryString.']['exclude'] = 'id,tx_kesearch_pi1[multi],cHash';
		$conf['additionalParams'] = '&' . implode('&', $params);

		return $this->cObj->typoLink(
			$this->pObj->pi_getLL('orderlink_' . $field, $field),
			$conf
		);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/lib/class.tx_kesearch_lib_sorting.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/lib/class.tx_kesearch_lib_sorting.php']);
}
?>
