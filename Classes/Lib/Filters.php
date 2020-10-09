<?php
namespace TeaminmediasPluswerk\KeSearch\Lib;

/***************************************************************
 *  Copyright notice
 *  (c) 2012 Stefan Froemken
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Faceted search - searchbox and filters' for the 'ke_search' extension.
 * @author    Stefan Froemken
 * @author    Christian Bülter
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class Filters
{

    /**
     * @var Pluginbase
     */
    protected $pObj;

    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected $cObj;

    /**
     * @var Db
     */
    protected $db;

    protected $tagChar = '#';
    protected $filters = array();
    protected $conf = array();
    protected $piVars = array();
    protected $extConf = array();
    protected $extConfPremium = array();
    protected $tagsInSearchResult = array();

    protected $startingPoints = '';

    /**
     * Initializes this object
     * @param Pluginbase $pObj
     * @return void
     */
    public function initialize(Pluginbase $pObj)
    {
        $this->pObj = $pObj;
        $this->cObj = $pObj->cObj;
        $this->db = GeneralUtility::makeInstance(Db::class);

        $this->conf = $this->pObj->conf;
        $this->piVars = $this->pObj->piVars;
        $this->startingPoints = $this->pObj->startingPoints;
        $this->tagChar = $this->pObj->extConf['prePostTagChar'];

        // get filters and filter options
        $this->filters = $this->getFiltersFromUidList(
            $this->combineLists($this->conf['filters'], $this->conf['hiddenfilters'])
        );

        // hook to modify filters
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilters'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilters'] as $_classRef) {
                $_procObj = GeneralUtility::makeInstance($_classRef);
                $_procObj->modifyFilters($this->filters, $this);
            }
        }

        // get list of selected filter options (via frontend or backend)
        foreach ($this->filters as $filter) {
            $this->filters[$filter['uid']]['selectedOptions'] = $this->getSelectedFilterOptions($filter);
        }
    }

    /**
     * Finds the selected filter options for a given filter.
     * Checks
     * - piVars one-dimensional filter
     * - piVars multi-dimensional filter
     * - backend preselected filter options
     * returns the filter options uids as values of an array or zero if no option has been selected.
     *
     * @param array $filter
     * @return array
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 09.09.14
     */
    public function getSelectedFilterOptions($filter)
    {
        $selectedOptions = array();

        // run through all the filter options and check if one of them
        // has been selected.
        foreach ($filter['options'] as $option) {
            // Is the filter option selected in the frontend via piVars
            // or in the backend via flexform configuration ("preselected filters")?
            $selected = false;

            if ($this->pObj->piVars['filter'][$filter['uid']] == $option['tag']) {
                $selected = true;
            } elseif (is_array($this->pObj->piVars['filter'][$filter['uid']])) { // if a this filter is set
                // test pre selected filter again
                if (is_array($this->pObj->preselectedFilter)
                    && $this->pObj->in_multiarray($option['tag'], $this->pObj->preselectedFilter)) {
                    $selected = true;
                    // add preselected filter to piVars
                    $this->pObj->piVars['filter'][$filter['uid']][$option['uid']] = $option['tag'];
                } else { // else test all other filter
                    $isInArray = in_array($option['tag'], $this->pObj->piVars['filter'][$filter['uid']]);
                    if ($isInArray) {
                        $selected = true;
                    }
                }
            } elseif (!isset($this->pObj->piVars['filter'][$filter['uid']])
                && !is_array($this->pObj->piVars['filter'][$filter['uid']])
            ) {
                if (is_array($this->pObj->preselectedFilter)
                    && $this->pObj->in_multiarray($option['tag'], $this->pObj->preselectedFilter)
                ) {
                    $selected = true;
                    // add preselected filter to piVars
                    $this->pObj->piVars['filter'][$filter['uid']] = array($option['uid'] => $option['tag']);
                }
            }

            if ($selected) {
                $selectedOptions[] = $option['uid'];
            }
        }

        return $selectedOptions;
    }

    /**
     * combines two string comma lists
     * @param string $list1
     * @param string $list2
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 23.07.13
     * @return string
     */
    public function combineLists($list1 = '', $list2 = '')
    {
        if (!empty($list2) && !empty($list2)) {
            $list1 .= ',';
        }
        $list1 .= $list2;
        $returnValue = GeneralUtility::uniqueList($list1);
        return $returnValue;
    }

    /**
     * get filters and options as associative array
     *
     * @return array Filters with including Options
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * get the filter records from DB which are configured in FlexForm
     *
     * @param string $filterUids A commaseperated list of filter uids
     * @return array Array with filter records
     */
    public function getFiltersFromUidList($filterUids)
    {
        if (empty($filterUids)) {
            return array();
        }

        // @Todo quotes ($this->startingPoints, filterUids)
        $table = 'tx_kesearch_filters';
        $where = 'pid in (' . $this->startingPoints . ')';
        $where .= ' AND find_in_set(uid, "' . $filterUids . '")';

        $queryBuilder = Db::getQueryBuilder('tx_kesearch_filters');
        $filterQuery = $queryBuilder
            ->select('*')
            ->from($table)
            ->add('where', $where)
            ->add('orderBy', 'find_in_set(uid, "' . $filterUids . '")')
            ->execute();

        $filterRows = [];
        while ($row = $filterQuery->fetch()) {
            $filterRows[$row['uid']] = $row;
        }

        $filterRows = $this->languageOverlay($filterRows, $table);
        return $this->addOptionsToFilters($filterRows);
    }


    /**
     * get the option records from DB which are configured as commaseperate list within the filter records
     * @param string $optionUids A commaseperated list of option uids
     * @return array Array with option records
     */
    public function getOptionsFromUidList($optionUids)
    {
        if (empty($optionUids)) {
            return array();
        }

        // @Todo quotes ($optionsUids, $this->startingPoints)
        $table = 'tx_kesearch_filteroptions';
        $where = 'FIND_IN_SET(uid, "' . $optionUids . '")';
        $where .= ' AND pid in (' . $this->startingPoints . ')';

        $queryBuilder = Db::getQueryBuilder('tx_kesearch_filteroptions');
        $optionsQuery = $queryBuilder
            ->select('*')
            ->from($table)
            ->add('where', $where)
            ->add('orderBy', 'FIND_IN_SET(uid, "' . $optionUids . '")')
            ->execute();

        $optionsRows = [];
        while ($row = $optionsQuery->fetch()) {
            $optionsRows[$row['uid']] = $row;
        }

        return $this->languageOverlay(
            $optionsRows,
            $table
        );
    }


    /**
     * replace the commaseperated option list with the original option records from DB
     * @param array $rows The filter records as array
     * @return array The filter records where the option value was replaced with the option records as array
     */
    public function addOptionsToFilters(array $rows)
    {
        if (is_array($rows) && count($rows)) {
            foreach ($rows as $key => $row) {
                if (!empty($row['options'])) {
                    $rows[$key]['options'] = $this->getOptionsFromUidList($row['options']);
                } else {
                    $rows[$key]['options'] = array();
                }
            }
            return $rows;
        } else {
            return array();
        }
    }


    /**
     * Translate the given records
     * @param array $rows The records which have to be translated
     * @param string $table Define the table from where the records come from
     * @return array The localized records
     */
    public function languageOverlay(array $rows, string $table): array
    {
        $languageAspect = GeneralUtility::makeInstance(Context::class)->getAspect('language');

        $LanguageUid = $languageAspect->getContentId();
        $LanguageMode = $languageAspect->getLegacyLanguageMode();

        // see https://github.com/teaminmedias-pluswerk/ke_search/issues/128
        if (GeneralUtility::hideIfNotTranslated($GLOBALS['TSFE']->page['l18n_cfg'])) {
            $LanguageMode = 'hideNonTranslated';
        }
        if (is_array($rows) && count($rows)) {
            foreach ($rows as $key => $row) {
                if (is_array($row) && $LanguageUid > 0) {
                    $row = $GLOBALS['TSFE']->sys_page->getRecordOverlay(
                        $table,
                        $row,
                        $LanguageUid,
                        $LanguageMode
                    );

                    if (is_array($row)) {
                        if ($table == "tx_kesearch_filters") {
                            $row['rendertype'] = $rows[$key]['rendertype'];
                        }
                        $rows[$key] = $row;
                    } else {
                        unset($rows[$key]);
                    }
                }
            }
            return $rows;
        } else {
            return [];
        }
    }


    /**
     * check if an allowed tag (defined in a filteroption) was found in the current result list
     *
     * @param string $tag The tag to match against the searchresult
     * @return boolean TRUE if tag was found. Else FALSE
     */
    public function checkIfTagMatchesRecords($tag)
    {
        // if tag list is empty, fetch them from the result list
        // otherwise use the cached result list
        if (!$this->tagsInSearchResult) {
            $this->tagsInSearchResult = $this->pObj->tagsInSearchResult = $this->db->getTagsFromSearchResult();
        }

        return array_key_exists($tag, $this->tagsInSearchResult);
    }

    /**
     * returns the tag char: a character which wraps tags in the database
     *
     * @return string
     */
    public function getTagChar()
    {
        return $this->tagChar;
    }
}
