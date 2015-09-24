<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Stefan Froemken
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
class tx_kesearch_lib_searchphrase {

	/**
	 * @var tx_kesearch_lib
	 */
	protected $pObj;





	/**
	 * initializes this object
	 *
	 * @param tx_kesearch_lib $pObj
	 */
	public function initialize(tx_kesearch_lib $pObj) {
		$this->pObj = $pObj;
	}


	/**
	 * build search phrase
	 *
	 * @return array Array containing different elements which helps to build the search query
	 */
	public function buildSearchPhrase() {
		$cleanSearchStringParts = array();
		$tagsAgainst = $this->buildTagsAgainst();
		$searchString = trim($this->pObj->piVars['sword']);
		$searchString = $this->checkAgainstDefaultValue($searchString);
		$searchStringParts = $this->explodeSearchPhrase($searchString);
		foreach($searchStringParts as $key => $part) {
			$part = trim($part, '\~+-*|"');
			if(!empty($part)) {
				$cleanSearchStringParts[$key] = $part;
			}
		}

		$searchArray = array(
			'sword' => implode(' ', $cleanSearchStringParts), // f.e. hello karl-heinz +mueller
			'swords' => $cleanSearchStringParts, // f.e. Array: hello|karl|heinz|mueller
			'wordsAgainst' => implode(' ', $searchStringParts), // f.e. +hello* +karl* +heinz* +mueller*
			'tagsAgainst' => $tagsAgainst, // f.e. Array: +#category_213# +#color_123# +#city_42#
			'scoreAgainst' => implode(' ', $cleanSearchStringParts) // f.e. hello karl heinz mueller
		);

		return $searchArray;
	}


	/**
	 * checks if the entered searchstring is the default value
	 *
	 * @param string $searchString
	 * @return string Returns the searchstring or an empty string
	 */
    public function checkAgainstDefaultValue($searchString) {
        $searchStringToLower = strtolower(trim($searchString));
        $defaultValueToLower = strtolower($this->pObj->pi_getLL('searchbox_default_value'));
        if ($searchStringToLower === $defaultValueToLower) $searchString = '';

        return $searchString;
    }


	/**
	 * explode search string and remove too short words
	 *
	 * @param type $searchString
	 * @return type
	 */
	public function explodeSearchPhrase($searchString) {
		preg_match_all('/(\+|\-|\~|<|>)?\".*?"|[^ ]+/', $searchString, $matches);
		list($searchParts) = $matches;
		if(is_array($searchParts) && count($searchParts)) {
			foreach($searchParts as $key => $word) {
				// check for boolean seperator
				if($word === '|') continue;

				// maybe we should check against the MySQL stoppword list:
				// Link: http://dev.mysql.com/doc/refman/5.0/en/fulltext-stopwords.html

				// don't check length if it is a phrase
				if(preg_match('/^(\+|\-|\~|<|>)?\"/', $word)) continue;

				// prepare word for next check
				$word = trim($word, '+-~<>');

				// check for word length
				$csconv = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
				$searchWordLength = $csconv->utf8_strlen($word);
				if($searchWordLength < $this->pObj->extConf['searchWordLength']) {
					$this->pObj->hasTooShortWords = true;
					$this->showShortMessage = true;
					unset($searchParts[$key]);
				}
			}
			foreach($searchParts as $key => $word) {
				if($word != '|') {

					// enable part searching by default. But be careful: Enabling this slows down the search engine
					if(!isset($this->pObj->extConf['enablePartSearch']) || $this->pObj->extConf['enablePartSearch']) {
						if($this->pObj->extConfPremium['enableInWordSearch']) {
							$searchParts[$key] = '*' . trim($searchParts[$key], '*') . '*';
						} else {
							$searchParts[$key] = rtrim($searchParts[$key], '*') . '*';
						}
					}

					// add + explicit to all search words to make the searchresults equal to sphinx search results
					if($this->pObj->extConf['enableExplicitAnd']) {
						$searchParts[$key] = '+' . ltrim($searchParts[$key], '+');
					}
				}

				// make the words save for the database
				$searchParts[$key] = $GLOBALS['TYPO3_DB']->quoteStr($searchParts[$key], 'tx_kesearch_index');
			}
			return array_values($searchParts);
		} return array();
	}


	/**
	 * build tags against
	 *
	 * @return array
	 */
	public function buildTagsAgainst() {
		$tagsAgainst = array();
		$this->buildPreselectedTagsAgainst($tagsAgainst);
		$this->buildPiVarsTagsAgainst($tagsAgainst);

		return $tagsAgainst;
	}


	/**
	 * add preselected filter options (preselected in the backend flexform)
	 *
	 * @param array $tagsAgainst
	 */
	public function buildPreselectedTagsAgainst(array &$tagsAgainst) {
		$tagChar = $this->pObj->extConf['prePostTagChar'];
		foreach($this->pObj->preselectedFilter as $key => $filterTags) {
			// add it only, if no other filter options of this filter has been
			// selected in the frontend
			if (!isset($this->pObj->piVars['filter'][$key]) && !is_array($this->pObj->piVars['filter'][$key])) {
				// Quote the tags for use in database query
				foreach ($filterTags as $k => $v) {
					$filterTags[$k] = $GLOBALS['TYPO3_DB']->quoteStr($v, 'tx_kesearch_index');
				}
				// if we are in checkbox mode
				if(count($this->pObj->preselectedFilter[$key]) >= 2) {
					$tagsAgainst[$key] .= ' "' . $tagChar . implode($tagChar . '" "' . $tagChar, $filterTags) . $tagChar . '"';
				// if we are in select or list mode
				} elseif(count($this->pObj->preselectedFilter[$key]) == 1) {
					$tagsAgainst[$key] .= ' +"' . $tagChar . array_shift($filterTags) . $tagChar . '"';
				}
			}
		}
	}

	/**
	 * add filter options (preselected by piVars)
	 *
	 * @param array $tagsAgainst
	 */
	public function buildPiVarsTagsAgainst(array &$tagsAgainst) {
		// add filter options selected in the frontend
		$tagChar = $this->pObj->extConf['prePostTagChar'];
		if(is_array($this->pObj->piVars['filter'])) {
			foreach($this->pObj->piVars['filter'] as $key => $tag)  {
				if(is_array($this->pObj->piVars['filter'][$key])) {
					foreach($this->pObj->piVars['filter'][$key] as $subkey => $subtag)  {
						// Don't add the tag if it is already inserted by preselected filters
						if(!empty($subtag) && strstr($tagsAgainst[$key], $subtag) === false) {
							// Don't add a "+", because we are here in checkbox mode. It's a OR.
							$tagsAgainst[$key] .= ' "' . $tagChar . $GLOBALS['TYPO3_DB']->quoteStr($subtag, 'tx_kesearch_index') . $tagChar . '"';
						}
					}
				} else {
					// Don't add the tag if it is already inserted by preselected filters
					if(!empty($tag) && strstr($tagsAgainst[$key], $subtag) === false) {
						$tagsAgainst[$key] .= ' +"' . $tagChar . $GLOBALS['TYPO3_DB']->quoteStr($tag, 'tx_kesearch_index') . $tagChar . '"';
					}
				}
			}
		}

		// hook for modifiying the tags to filter for
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyTagsAgainst'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyTagsAgainst'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$_procObj->modifyTagsAgainst($tagsAgainst, $this);
			}
		}
	}
}