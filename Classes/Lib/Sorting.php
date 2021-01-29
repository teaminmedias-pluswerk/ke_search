<?php
namespace TeaminmediasPluswerk\KeSearch\Lib;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

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
 * Plugin 'Faceted search - searchbox and filters' for the 'ke_search' extension.
 * @author    Stefan Froemken
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class Sorting
{

    public $conf = array();
    public $subpartArray = array();
    public $sortBy = '';

    /**
     * @var Pluginbase
     */
    public $pObj;

    /**
     * @var Db
     */
    public $db;

    /**
     * @var ContentObjectRenderer
     */
    public $cObj;

    /**
     * @var PluginBaseHelper
     */
    public $div;

    /**
     * The constructor of this class
     * @param Pluginbase $pObj
     */
    public function __construct(Pluginbase $pObj)
    {
        // initializes this object
        $this->init($pObj);
    }


    /**
     * Initializes this object
     * @param Pluginbase $pObj
     * @return void
     */
    public function init(Pluginbase $pObj)
    {
        $this->pObj = $pObj;
        $this->db = $this->pObj->db;
        $this->cObj = $this->pObj->cObj;
        $this->conf = $this->pObj->conf;

        // get sorting values (sortdate, title, what ever...)
        $this->sortBy = GeneralUtility::trimExplode(',', $this->conf['sortByVisitor'], true);
    }


    /**
     * The main entry point of this class
     *
     * @return void
     */
    public function renderSorting(&$fluidTemplateVariables)
    {
        $links = '';
        // show sorting:
        // if show Sorting is activated in FlexForm
        // if a value to sortBy is set in FlexForm (title, relevance, sortdate, what ever...)
        // if there are any entries in current search results
        if ($this->conf['showSortInFrontend'] && !empty($this->conf['sortByVisitor']) && $this->pObj->numberOfResults) {
            // loop all allowed orderings
            foreach ($this->sortBy as $field) {
                // we can't sort by score if there is no sword given
                if ($this->pObj->sword != '' || $field != 'score') {
                    $sortByDir = $this->getDefaultSortingDirection($field);
                    $dbOrdering = GeneralUtility::trimExplode(' ', $this->db->getOrdering());

                    /* if ordering direction is the same change it
                     *
                     * Explanation:
                     * No ordering is active. Default Ordering by db is "sortdate desc".
                     * Default ordering by current field is also "sortdate desc".
                     * So...if you click the link for sortdate it will sort the results by "sortdate desc" again
                     * To prevent this we change the default ordering here
                     */
                    if ($field == $dbOrdering[0] && $sortByDir == $dbOrdering[1]) {
                        $sortByDir = $this->changeOrdering($sortByDir);
                    }

                    $url = $this->generateSortingLink($field, $sortByDir);
                    $classname = $this->getClassNameForUpDownArrow($field, $dbOrdering);

                    $fluidTemplateVariables['sortingLinks'][] = array(
                        'field' => $field,
                        'url' => $url,
                        'class' => $classname
                    );
                }
            }
        }
    }


    /**
     * get default sorting direction
     * f.e. default sorting for sortdate should be DESC. The most current records at first
     * f.e. default sorting for relevance should be DESC. The best relevance at first
     * f.e. default sorting for title should be ASC. Alphabetic order begins with A.
     * @param string The field name to sort by
     * @return string The default sorting (asc/desc) for given field
     */
    public function getDefaultSortingDirection($field)
    {
        if (!empty($field) && is_string($field)) {
            switch ($field) {
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
        } else {
            return 'asc';
        }
    }


    /**
     * change ordering
     * f.e. asc to desc and desc to asc
     * @param string $direction asc or desc
     * @return string desc or asc. If you call this function with a not allowed string, exactly this
     * string will be returned. Short: The function do nothing
     */
    public function changeOrdering($direction)
    {
        $allowedDirections = array('asc', 'desc');
        $direction = strtolower($direction);
        $isInArray = in_array($direction, $allowedDirections, true);
        if (!empty($direction) && $isInArray) {
            if ($direction == 'asc') {
                $direction = 'desc';
            } else {
                $direction = 'asc';
            }
        }
        return $direction;
    }


    /**
     * get a class name for up and down arrows of sorting links
     * @param string $field current field to sort by
     * @param array $dbOrdering An array containing the field and ordering of current DB Ordering
     * @return string The class name
     */
    public function getClassNameForUpDownArrow($field, $dbOrdering)
    {
        $className = '';
        if (is_array($dbOrdering) && count($dbOrdering)) {
            if ($field == $dbOrdering[0]) {
                if ($dbOrdering[1] == 'asc') {
                    $className = 'up';
                } else {
                    $className = 'down';
                }
            }
        }
        return $className;
    }


    /**
     * generate the link for the given sorting value
     * @param string $field
     * @param string $sortByDir
     * @return string The complete link as A-Tag
     */
    public function generateSortingLink(string $field, string $sortByDir): string
    {
        $localPiVars = $this->pObj->piVars;
        $localPiVars['sortByField'] = $field;
        $localPiVars['sortByDir'] = $sortByDir;
        unset($localPiVars['page']);

        return SearchHelper::searchLink(
            $this->pObj->conf['resultPage'],
            $localPiVars,
            [],
            $this->pObj->pi_getLL('orderlink_' . $field, $field)
        );
    }
}
