<?php
use \TYPO3\CMS\Backend\Utility\BackendUtility;

/***************************************************************
 *  Copyright notice
 *  (c) 2010 Andreas Kiefer, www.kennziffer.com GmbH <kiefer@kennziffer.com>
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
 * User class 'user_filterlist' for the 'ke_search' extension.
 * @author  Andreas Kiefer, www.kennziffer.com GmbH <kiefer@kennziffer.com>
 * @package TYPO3
 * @subpackage  tx_kesearch
 */
class user_filterlist
{

    /**
     * compiles a list of filters in order to display them to in the backend plugin configuration (pi1)
     * @param $config
     */
    public function getListOfAvailableFiltersForFlexforms(&$config)
    {
        $pidList = $this->getConfiguredPagesFromPlugin($config);

        // print message if no startingpoint is set in plugin config
        if (empty($pidList)) {
            $config['items'][] = array('[SET STARTINGPOINT FIRST!]', '');
            return ;
        }

        // get filters
        $fields = '*';
        $table = 'tx_kesearch_filters';
        $where = 'pid IN(' . $pidList . ') ';
        $where .= BackendUtility::BEenableFields($table);
        $where .= BackendUtility::deleteClause($table);
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
        $count = $GLOBALS['TYPO3_DB']->sql_num_rows($res);

        if ($count) {
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                if (!$row['l10n_parent']) {
                    $config['items'][] = array($row['title'], $row['uid']);
                }
            }
        }
    }


    /**
     * compiles the list of available filter options in order to display them in the page record
     * in the backend, so that the editor can assign a tag to a page
     * @param $config
     */
    public function getListOfAvailableFiltersForTCA(&$config)
    {

        // get current pid
        if ($config['table'] == 'pages') {
            $currentPid = $config['row']['uid'];
        } else {
            $currentPid = $config['row']['pid'];
        }

        // get the page TSconfig
        $pageTSconfig = BackendUtility::GetPagesTSconfig($currentPid);
        $modTSconfig = $pageTSconfig['tx_kesearch.'];

        // get filters
        $fields = '*';
        $table = 'tx_kesearch_filters';

        // storage pid for filter options
        if (!empty($modTSconfig['filterStorage'])) {
            // storage pid is set in page ts config
            $where = 'pid IN (' . $modTSconfig['filterStorage'] . ') ';
        } else {
            // no storage pid set in page ts config
            $where = '1=1 ';
        }

        $where .= BackendUtility::BEenableFields($table);
        $where .= BackendUtility::deleteClause($table);

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
        $count = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
        if ($count) {
            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                if (!empty($row['options'])) {
                    $fields2 = '*';
                    $table2 = 'tx_kesearch_filteroptions';
                    $where2 = 'uid in (' . $row['options'] . ')';
                    $where2 .= BackendUtility::BEenableFields($table2);
                    $where2 .= BackendUtility::deleteClause($table2);

                    $res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields2, $table2, $where2);
                    while ($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2)) {
                        $config['items'][] = array($row['title'] . ': ' . $row2['title'], $row2['uid']);
                    }
                }
            }
        }
    }


    /**
     * compiles a list of filter options in order to display them to in plugin (pi1)
     * @param $config
     */
    public function getListOfAvailableFilteroptionsForFlexforms(&$config)
    {
        $pidList = $this->getConfiguredPagesFromPlugin($config);

        // print message if no startingpoint is set in plugin config
        if (empty($pidList)) {
            $config['items'][] = array('[SET STARTINGPOINT FIRST!]', '');
            return ;
        }

        // get filters
        $fields = '*';
        $table = 'tx_kesearch_filters';
        $where = 'pid IN(' . $pidList . ') ';
        $where .= BackendUtility::BEenableFields($table);
        $where .= BackendUtility::deleteClause($table);

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where);
        $count = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
        if ($count) {
            while ($rowFilter = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                if (!empty($rowFilter['options'])) {
                    // get filteroptions
                    $fieldsOpts = '*';
                    $tableOpts = 'tx_kesearch_filteroptions';
                    $whereOpts = 'uid in (' . $rowFilter['options'] . ')';
                    $whereOpts .= BackendUtility::BEenableFields($tableOpts);
                    $whereOpts .= BackendUtility::deleteClause($tableOpts);

                    $resOpts = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fieldsOpts, $tableOpts, $whereOpts);
                    while ($rowOpts = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resOpts)) {
                        $config['items'][] = array($rowFilter['title'] . ': ' . $rowOpts['title'], $rowOpts['uid']);
                    }
                }

                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilteroptionsForFlexforms'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilteroptionsForFlexforms'] as
                             $_classRef) {
                        $_procObj = &TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
                        $_procObj->modifyFilteroptionsForFlexforms($config, $rowFilter, $this);
                    }
                }
            }
        }
    }


    /**
     * Get configured pages from "pages" attribute in plugin's row
     * TYPO3 7.6 and 8.7 have different types in $config['flexParentDatabaseRow']['pages'].
     *
     * This method handles both.
     *
     * @param array $config
     * @return string
     */
    protected function getConfiguredPagesFromPlugin(array $config)
    {
        // check if the tt_content row is available and if not load it manually
        // flexParentDatabaseRow not set can be caused by compatibility6 extension
        if (!isset($config['flexParentDatabaseRow']) ||
            (isset($config['flexParentDatabaseRow']) && !is_array($config['flexParentDatabaseRow']))
        ) {
            $parentRow = BackendUtility::getRecord(
                'tt_content',
                $config['row']['uid']
            );
            if (is_array($parentRow)) {
                $config['flexParentDatabaseRow'] = $parentRow;
            } else {
                // tt_content row not found
                return '';
            }
        }

        $parentRow = $config['flexParentDatabaseRow'];
        $pages = $parentRow['pages'];

        $pids = [];
        if (is_string($pages)) {
            // TYPO3 7.6
            $pagesParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $pages, true);
            foreach ($pagesParts as $pagePart) {
                $a = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $pagePart);
                $b = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('_', $a[0]);
                $uid = end($b);
                $pids[] = $uid;
            }
            return implode(',', $pids);
        }

        // TYPO3 8.7
        foreach ($pages as $page) {
            $pids[] = $page['uid'];
        }
        return implode(',', $pids);
    }
}
