<?php
namespace TeaminmediasPluswerk\KeSearch\Lib;

use TeaminmediasPluswerk\KeSearch\Plugins\txkesearchpi1;

/***************************************************************
 *  Copyright notice
 *  (c) 2010 Stefan Froemken
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


/**
 * Helper class for ke_search Plugin
 *
 * @author    Stefan Froemken
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class PluginBaseHelper
{
    public $showShortMessage = false;

    /**
     * Contains the parent object
     * @var Pluginbase
     */
    public $pObj;

    public function __construct($pObj)
    {
        $this->pObj = $pObj;
    }

    public function getStartingPoint()
    {
        $startingpoint = array();

        // if loadFlexformsFromOtherCE is set
        // try to get startingPoint of given page
        if ($uid = intval($this->pObj->conf['loadFlexformsFromOtherCE'])) {
            $queryBuilder = Db::getQueryBuilder('tt_content');
            $queryBuilder->getRestrictions()->removeAll();
            $pageResult = $queryBuilder
                ->select('pages', 'recursive')
                ->from('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch(0);
            if (is_array($pageResult) && count($pageResult)) {
                $startingpoint['pages'] = $pageResult['pages'];
                $startingpoint['recursive'] = $pageResult['recursive'];
            }
        } else {
            // if loadFlexformsFromOtherCE is NOT set
            // get startingPoints of current page
            $startingpoint['pages'] = $this->pObj->cObj->data['pages'];
            $startingpoint['recursive'] = $this->pObj->cObj->data['recursive'];
        }

        // allow to override startingpoint with typoscript like this
        // plugin.tx_kesearch_pi1.overrideStartingPoint = 123
        // plugin.tx_kesearch_pi1.overrideStartingPointRecursive = 1
        if ($this->pObj->conf['overrideStartingPoint']) {
            $startingpoint['pages'] = $this->pObj->conf['overrideStartingPoint'];
            $startingpoint['recursive'] = $this->pObj->conf['overrideStartingPointRecursive'];
        }

        return $this->pObj->pi_getPidList($startingpoint['pages'], $startingpoint['recursive']);
    }

    /**
     * Get the first page of starting points
     *
     * @param string comma seperated list of page-uids
     * @return int first page uid
     */
    public function getFirstStartingPoint($pages = '')
    {
        $pageArray = explode(',', $pages);
        return intval($pageArray[0]);
    }

    /**
     * function cleanPiVars
     * cleans piVars
     * sword is not cleaned at this point.
     * This is done when outputting and querying the database.
     * htmlspecialchars(...) and / or intval(...)
     *
     * @param $piVars array        array containing all piVars
     * @return mixed
     */
    public function cleanPiVars($piVars)
    {
        // run through all piVars
        foreach ($piVars as $key => $value) {
            // process further cleaning regarding to param type
            switch ($key) {
                // integer - default 1
                case 'page':
                    $piVars[$key] = intval($value);
                    // set to "1" if no value set
                    if (!$piVars[$key]) {
                        $piVars[$key] = 1;
                    }
                    break;

                // integer
                case 'resetFilters':
                    $piVars[$key] = intval($value);
                    break;

                // array of strings. Defined in the TYPO3 backend
                // and posted as piVar. Should not contain any special
                // chars (<>"), but just to make sure we remove them here.
                case 'filter':
                    if (is_array($piVars[$key])) {
                        foreach ($piVars[$key] as $filterId => $filterValue) {
                            if (is_array($piVars[$key][$filterId])) {
                                foreach ($piVars[$key][$filterId] as $key => $value) {
                                    $piVars[$key][$filterId][$key] = htmlspecialchars($value, ENT_QUOTES);
                                }
                            } else {
                                if ($piVars[$key][$filterId] != null) {
                                    $piVars[$key][$filterId] = htmlspecialchars($filterValue, ENT_QUOTES);
                                }
                            }
                        }
                    }
                    break;

                // string, no further XSS cleaning here
                // cleaning is done on output
                case 'sword':
                    $piVars[$key] = trim($piVars[$key]);
                    break;

                // only characters
                case 'sortByField':
                case 'orderByField':
                    $piVars[$key] = preg_replace('/[^a-zA-Z0-9]/', '', $piVars[$key]);
                    break;

                // "asc" or "desc"
                case 'sortByDir':
                case 'orderByDir':
                    if ($piVars[$key] != 'asc' && $piVars[$key] != 'desc') {
                        $piVars[$key] = 'asc';
                    }
                    break;

                // remove all other piVars
                default:
                    unset($piVars[$key]);
                    break;
            }
        }

        // return cleaned piVars values
        return $piVars;
    }
}
