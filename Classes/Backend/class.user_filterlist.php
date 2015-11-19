<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Andreas Kiefer, www.kennziffer.com GmbH <kiefer@kennziffer.com>
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
 * User class 'user_filterlist' for the 'ke_search' extension.
 *
 * @author  Andreas Kiefer, www.kennziffer.com GmbH <kiefer@kennziffer.com>
 * @package TYPO3
 * @subpackage  tx_kesearch
 */
class user_filterlist
{

    function getListOfAvailableFiltersForFlexforms(&$config)
    {


        if ($this->isTypo3LTS7()) {
            $parentRow = $config['flexParentDatabaseRow'];
        } else {
            $parentRow = $config['row'];
        }

        // get id from string
        if (strstr($parentRow['pages'], 'pages_')) {
            $intString = str_replace('pages_', '', $parentRow['pages']);
            $intString = substr($intString, 0, strpos($intString, '|'));
            $intString = intval($intString);
        } else {
            $intString = intval($parentRow['pages']);
        }

        // print message if no startingpoint is set in plugin config
        if (empty($intString)) {
            $config['items'][] = array('[SET STARTINGPOINT FIRST!]', '');
        }

        // get filters
        $fields = '*';
        $table = 'tx_kesearch_filters';
        $where = 'pid IN(' . $intString . ') ';
        $where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table, $inv = 0);
        $where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table, $inv = 0);
        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where, $groupBy = '', $orderBy = '',
            $limit = '');
        $anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $config['items'][] = array($row['title'], $row['uid']);
        }
    }


    function getListOfAvailableFiltersForTCA(&$config)
    {

        if ($this->isTypo3LTS7()) {
            $parentRow = $config['flexParentDatabaseRow'];
        } else {
            $parentRow = $config['row'];
        }
        // get current pid
        if ($config['table'] == 'pages') {
            $currentPid = $parentRow['uid'];
        } else {
            $currentPid = $parentRow['pid'];
        }

        // get the page TSconfig
        $this->pageTSconfig = \TYPO3\CMS\Backend\Utility\BackendUtility::GetPagesTSconfig($currentPid);
        $this->modTSconfig = $this->pageTSconfig['ke_search.'];

        // get filters
        $fields = '*';
        $table = 'tx_kesearch_filters';

        // storage pid for filter options
        if (!empty($this->modTSconfig['filterStorage'])) {
            // storage pid is set in page ts config
            $where = 'pid IN (' . $this->modTSconfig['filterStorage'] . ') ';
        } else {
            // no storage pid set in page ts config
            $where = '1=1 ';
        }

        $where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table, $inv = 0);
        $where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table, $inv = 0);

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where, $groupBy = '', $orderBy = '',
            $limit = '');
        $anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

            if (!empty($row['options'])) {
                $fields2 = '*';
                $table2 = 'tx_kesearch_filteroptions';
                $where2 = 'uid in (' . $row['options'] . ')';
                $where2 .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table2);
                $where2 .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table2);

                $res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields2, $table2, $where2);
                while ($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2)) {
                    $config['items'][] = array($row['title'] . ': ' . $row2['title'], $row2['uid']);
                }
            }
        }
    }


    function getListOfAvailableFilteroptionsForFlexforms(&$config)
    {

        if ($this->isTypo3LTS7()) {
            $parentRow = $config['flexParentDatabaseRow'];
        } else {
            $parentRow = $config['row'];
        }

        // get id from string
        if (strstr($parentRow['pages'], 'pages_')) {
            $intString = str_replace('pages_', '', $parentRow['pages']);
            $intString = substr($intString, 0, strpos($intString, '|'));
            $intString = intval($intString);
        } else {
            $intString = intval($parentRow['pages']);
        }

        // print message if no startingpoint is set in plugin config
        if (empty($intString)) {
            $config['items'][] = array('[SET STARTINGPOINT FIRST!]', '');
        }

        // get filters
        $fields = '*';
        $table = 'tx_kesearch_filters';
        $where = 'pid IN(' . $intString . ') ';
        $where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table, $inv = 0);
        $where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table, $inv = 0);

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where, $groupBy = '', $orderBy = '',
            $limit = '');
        while ($rowFilter = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

            if (!empty($rowFilter['options'])) {
                // get filteroptions
                $fieldsOpts = '*';
                $tableOpts = 'tx_kesearch_filteroptions';
                $whereOpts = 'uid in (' . $rowFilter['options'] . ')';
                $whereOpts .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($tableOpts, $inv = 0);
                $whereOpts .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($tableOpts, $inv = 0);

                $resOpts = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fieldsOpts, $tableOpts, $whereOpts, $groupBy = '',
                    $orderBy = '', $limit = '');
                while ($rowOpts = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resOpts)) {
                    $config['items'][] = array($rowFilter['title'] . ': ' . $rowOpts['title'], $rowOpts['uid']);
                }
            }
        }
    }

    /**
     * @return bool
     */
    private function isTypo3LTS7()
    {
        return (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(\TYPO3\CMS\Core\Utility\VersionNumberUtility::getNumericTypo3Version()) >= 7006000);
    }
}