<?php
namespace TeaminmediasPluswerk\KeSearch\Backend;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TeaminmediasPluswerk\KeSearch\Lib\Db;

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
class Filterlist
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
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_filters');
        $res = $queryBuilder
            ->select('*')
            ->from('tx_kesearch_filters')
            ->where(
                $queryBuilder->expr()->in('pid', $pidList),
                $queryBuilder->expr()->in('sys_language_uid', '0,-1')
            )
            ->execute();

        if ($res->rowCount()) {
            while ($row = $res->fetch()) {
                $config['items'][] = array($row['title'], $row['uid']);
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

        $queryBuilder = Db::getQueryBuilder('tx_kesearch_filters');

        // storage pid for filter options
        if (!empty($modTSconfig['filterStorage'])) {
            // storage pid is set in page ts config
            $where = $queryBuilder->expr()->in(
                'pid',
                $modTSconfig['filterStorage']
            );
        } else {
            // no storage pid set in page ts config
            $where = null;
        }

        $res = $queryBuilder
            ->select($fields)
            ->from($table)
            ->where($where)
            ->execute();

        if ($res->rowCount()) {
            while ($row = $res->fetch()) {
                if (!empty($row['options'])) {
                    $queryBuilder = Db::getQueryBuilder('tx_kesearch_filteroptions');
                    $options = $queryBuilder
                        ->select('*')
                        ->from('tx_kesearch_filteroptions')
                        ->where(
                            $queryBuilder->expr()->in('uid', $row['options']),
                            $queryBuilder->expr()->in('sys_language_uid', '0,-1')
                        )
                        ->execute();

                    while ($optionRow = $options->fetch()) {
                        $config['items'][] = array($row['title'] . ': ' . $optionRow['title'], $optionRow['uid']);
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
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_filters');
        $res = $queryBuilder
            ->select('*')
            ->from('tx_kesearch_filters')
            ->where(
                $queryBuilder->expr()->in('pid', $pidList),
                $queryBuilder->expr()->in('sys_language_uid', '0,-1')
            )
            ->execute();

        if ($res->rowCount()) {
            while ($rowFilter = $res->fetch()) {
                if (!empty($rowFilter['options'])) {
                    // get filteroptions
                    $queryBuilder = Db::getQueryBuilder('tx_kesearch_filteroptions');
                    $fieldsOpts = '*';
                    $tableOpts = 'tx_kesearch_filteroptions';
                    $whereOpts = $queryBuilder->expr()->in('uid', $rowFilter['options']);

                    $resOpts = $queryBuilder
                        ->select($fieldsOpts)
                        ->from($tableOpts)
                        ->where($whereOpts)
                        ->execute();
                    while ($rowOpts = $resOpts->fetch()) {
                        $config['items'][] = array($rowFilter['title'] . ': ' . $rowOpts['title'], $rowOpts['uid']);
                    }
                }

                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilteroptionsForFlexforms'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyFilteroptionsForFlexforms'] as
                             $_classRef) {
                        $_procObj = GeneralUtility::makeInstance($_classRef);
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
