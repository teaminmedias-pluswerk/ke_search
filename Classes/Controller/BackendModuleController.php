<?php

/***************************************************************
 *  Copyright notice
 *  (c) 2010 Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 *  (c) 2016 Bernhard Berger <bernhard.berger@gmail.com>
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

namespace TeaminmediasPluswerk\KeSearch\Controller;

use TeaminmediasPluswerk\KeSearch\Indexer\IndexerRunner;
use TeaminmediasPluswerk\KeSearch\Lib\Db;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class BackendModuleController
 * @package Teaminmedias\KeSearch\Controller
 */
class BackendModuleController extends AbstractBackendModuleController
{
    /**
     * @var array
     */
    protected $pageinfo;

    /**
     * @var array
     */
    protected $extConf;

    /**
     * @var string
     */
    protected $do;

    /**
     * @var \TYPO3\CMS\Core\Page\PageRenderer
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $pageRenderer;

    /**
     * @var \TYPO3\CMS\Core\Registry
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $registry;

    /**
     * @var string
     */
    protected $perms_clause;

    /**
     *
     */
    public function initializeAction()
    {
        parent::initializeAction();

        $this->extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ke_search'];

        $this->do = GeneralUtility::_GET('do');

        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);

        // check access and redirect accordingly
        $access = is_array($this->pageinfo) ? 1 : 0;

        if (($this->id && $access) || ($this->getBackendUser()->user['admin'] && !$this->id)) {
            //proceed normally
        } else {
            if ($this->getActionName() !== 'alert') {
                $this->redirect('alert', $this->getControllerName());
            }
        }
    }

    /**
     *
     */
    public function alertAction()
    {
        // just render the view
    }

    /**
     *
     */
    public function startIndexingAction()
    {
        // make indexer instance and init
        /* @var $indexer IndexerRunner */
        $indexer = GeneralUtility::makeInstance(IndexerRunner::class);

        // get indexer configurations
        $indexerConfigurations = $indexer->getConfigurations();

        // get uri builder
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $content = '';

        // action: start indexer or remove lock
        if ($this->do == 'startindexer') {
            // start indexing in verbose mode with cleanup process
            $content .= $indexer->startIndexing(true, $this->extConf);
        } else {
            if ($this->do == 'rmLock') {
                // remove lock from registry - admin only!
                if ($this->getBackendUser()->user['admin']) {
                    $this->registry->removeAllByNamespace('tx_kesearch');
                } else {
                    $content .=
                        '<p>'
                        . LocalizationUtility::translate(
                            'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:not_allowed_remove_indexer_lock',
                            'KeSearch'
                        )
                        . '</p>';
                }
            }
        }


        // check for index process lock in registry
        // remove lock if older than 12 hours
        $lockTime = $this->registry->get('tx_kesearch', 'startTimeOfIndexer');
        $compareTime = time() - (60 * 60 * 12);
        if ($lockTime !== null && $lockTime < $compareTime) {
            // lock is older than 12 hours
            // remove lock and show "start index" button
            $this->registry->removeAllByNamespace('tx_kesearch');
            $lockTime = null;
        }

        // show information about indexer configurations and number of records
        // if action "start indexing" is not selected
        if ($this->do != 'startindexer') {
            $content .= $this->printNumberOfRecords();
            $content .= $this->printIndexerConfigurations($indexerConfigurations);
        }

        // show "start indexing" or "remove lock" button
        if ($lockTime !== null) {
            if (!$this->getBackendUser()->user['admin']) {
                // print warning message for non-admins
                $content .= '<br /><p style="color: red; font-weight: bold;">WARNING!</p>';
                $content .= '<p>The indexer is already running and can not be started twice.</p>';
            } else {
                // show 'remove lock' button for admins
                $content .= '<br /><p>The indexer is already running and can not be started twice.</p>';
                $content .= '<p>The indexing process was started at ' . strftime('%c', $lockTime) . '.</p>';
                $content .= '<p>You can remove the lock by clicking the following button.</p>';
                $moduleUrl = $uriBuilder->buildUriFromRoute(
                    'web_KeSearchBackendModule',
                    [
                        'id' => $this->id,
                        'do' => 'rmLock'
                    ]
                );
                $content .= '<br /><a class="lock-button" href="' . $moduleUrl . '">RemoveLock</a>';
            }
        } else {
            // no lock set - show "start indexer" link if indexer configurations have been found
            if ($indexerConfigurations) {
                $moduleUrl = $uriBuilder->buildUriFromRoute(
                    'web_KeSearchBackendModule',
                    [
                        'id' => $this->id,
                        'do' => 'startindexer'
                    ]
                );
                $content .= '<br /><a class="index-button" href="' . $moduleUrl . '">'
                    .
                    LocalizationUtility::translate(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:start_indexer',
                        'KeSearch'
                    )
                    . '</a>';
            } else {
                $content .=
                    '<div class="alert alert-info">'
                    .
                    LocalizationUtility::translate(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:no_indexer_configurations',
                        'KeSearch'
                    )
                    . '</div>';
            }
        }

        $this->view->assign('content', $content);
    }

    /**
     *
     */
    public function indexedContentAction()
    {
        if ($this->id) {
            // page is selected: get indexed content
            $content = '<h2>Index content for page ' . $this->id . '</h2>';
            $content .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.path')
                . ': '
                .
                GeneralUtility::fixed_lgd_cs($this->pageinfo['_thePath'], -50);
            $content .= $this->getIndexedContent($this->id);
        } else {
            // no page selected: show message
            $content = '<div class="alert alert-info">'
                . LocalizationUtility::translate(
                    'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:select_a_page',
                    'KeSearch'
                )
                . '</div>';
        }

        $this->view->assign('content', $content);
    }

    /**
     *
     */
    public function indexTableInformationAction()
    {
        $content = $this->renderIndexTableInformation();

        $this->view->assign('content', $content);
    }

    /**
     *
     */
    public function searchwordStatisticsAction()
    {
        // days to show
        $days = 30;
        $content = $this->getSearchwordStatistics($this->id, $days);

        $this->view->assign('days', $days);
        $this->view->assign('content', $content);
    }

    /**
     *
     */
    public function clearSearchIndexAction()
    {
        // get uri builder
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        $content = '';

        // admin only access
        if ($this->getBackendUser()->user['admin']) {
            if ($this->do == 'clear') {
                $databaseConnection = Db::getDatabaseConnection('tx_kesearch_index');
                $databaseConnection->truncate('tx_kesearch_index');
            }

            $content .= '<p>'
                .
                LocalizationUtility::translate(
                    'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:index_contains',
                    'KeSearch'
                )
                . ' '
                . $this->getNumberOfRecordsInIndex()
                . ' '
                . LocalizationUtility::translate(
                    'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:records',
                    'KeSearch'
                )
                . '.</p>';

            // show "clear index" link
            $moduleUrl = $uriBuilder->buildUriFromRoute(
                'web_KeSearchBackendModule',
                [
                    'id' => $this->id,
                    'do' => 'clear'
                ]
            );
            $content .= '<br /><a class="index-button" href="' . $moduleUrl . '">Clear whole search index!</a>';
        } else {
            $content .= '<p>Clear search index: This function is available to admins only.</p>';
        }

        $this->view->assign('content', $content);
    }

    /**
     *
     */
    public function lastIndexingReportAction()
    {
        $content = $this->showLastIndexingReport();
        $this->view->assign('content', $content);
    }

    /**
     * shows report from sys_log
     * @return string
     * @author Christian Bülter <christian.buelter@inmedias.de>
     * @since 29.05.15
     */
    public function showLastIndexingReport()
    {

        $queryBuilder = Db::getQueryBuilder('sys_log');
        $logResults = $queryBuilder
            ->select('*')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->like(
                    'details',
                    $queryBuilder->quote('[ke_search]%', \PDO::PARAM_STR)
                )
            )
            ->orderBy('tstamp',  'DESC')
            ->setMaxResults(1)
            ->execute()
            ->fetchAll();

        if (count($logResults)) {
            $content = '<div class="summary"><pre>' . $logResults[0]['details'] . '</pre></div>';
        } else {
            $content = 'No report found.';
        }

        return $content;
    }

    /**
     * returns the number of records the index contains
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 26.03.15
     * @return integer
     */
    public function getNumberOfRecordsInIndex()
    {
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_index');
        return $queryBuilder
            ->count('*')
            ->from('tx_kesearch_index')
            ->execute()
            ->fetchColumn(0);
    }

    /**
     * prints the indexer configurations available
     * @param array $indexerConfigurations
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 28.04.15
     * @return string
     */
    public function printIndexerConfigurations($indexerConfigurations)
    {
        $content = '';
        // show indexer names
        if ($indexerConfigurations) {
            $content .= '<ol class="orderedlist">';
            foreach ($indexerConfigurations as $indexerConfiguration) {
                $content .= '<li class="summary infobox">'
                    . '<span class="title">' . $indexerConfiguration['title'] . '</span>'

                    . ' <span class="tagsmall">'
                    . $indexerConfiguration['type']
                    . '</span>'

                    . ' <span class="tagsmall">'
                    . 'UID ' . $indexerConfiguration['uid'] . '</span>'
                    . '</span>'

                    . ' <span class="tagsmall">'
                    . 'PID ' . $indexerConfiguration['pid'] . '</span>'
                    . '</span>'

                    . '</li>';
            }
            $content .= '</ol>';
        }

        return $content;
    }

    /**
     * prints number of records in index
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 28.04.15
     */
    public function printNumberOfRecords()
    {
        $content = '';
        $numberOfRecords = $this->getNumberOfRecordsInIndex();
        if ($numberOfRecords) {
            $content .=
                '<p class="box infobox"><i>'
                . LocalizationUtility::translate(
                    'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:index_contains',
                    'KeSearch'
                )
                . ' '
                . $numberOfRecords
                . ' '
                . LocalizationUtility::translate(
                    'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:records',
                    'KeSearch'
                )
                . ': ';

            $results_per_type = $this->getNumberOfRecordsInIndexPerType();
            $first = true;
            foreach ($results_per_type as $type => $count) {
                if (!$first) {
                    $content .= ', ';
                }
                $content .= $type . ' (' . $count . ')';
                $first = false;
            }
            $content .= '.<br/>';
            $content .=
                LocalizationUtility::translate(
                    'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:last_indexing',
                    'KeSearch'
                )
                . ' '
                . $this->getLatestRecordDate() . '.';

            $content .= '</i></p>';
        }

        return $content;
    }

    /**
     * returns number of records per type in an array
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 28.04.15
     * @return array
     */
    public function getNumberOfRecordsInIndexPerType()
    {
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_index');
        $typeCount = $queryBuilder
            ->select('type')
            ->addSelectLiteral(
                $queryBuilder->expr()->count('tx_kesearch_index.uid', 'count')
            )
            ->from('tx_kesearch_index')
            ->groupBy('tx_kesearch_index.type')
            ->execute();

        $resultsPerType = [];
        while ($row = $typeCount->fetch()) {
            $resultsPerType[$row['type']] = $row['count'];
        }

        return $resultsPerType;
    }

    /**
     * returns the date of the lates record (formatted in a string)
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 28.04.15
     * @return string
     */
    public function getLatestRecordDate()
    {
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_index');
        $latestDate = $queryBuilder
            ->select('tstamp')
            ->from('tx_kesearch_index')
            ->orderBy('tstamp', 'desc')
            ->setMaxResults(1)
            ->execute();

        while ($row = $latestDate->fetch()) {
            return date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $row['tstamp']) . ' ' . date('H:i', $row['tstamp']);
        }

    }

    /*
     * function renderIndexTableInformation
     */
    public function renderIndexTableInformation()
    {
        $table = 'tx_kesearch_index';

        // get table status
        $databaseConnection = Db::getDatabaseConnection($table);
        $tableStatusQuery = 'SHOW TABLE STATUS';
        $tableStatusRows = $databaseConnection->fetchAll($tableStatusQuery);
        $content = '';

        foreach ($tableStatusRows as $row) {
            if ($row['Name'] == $table) {
                $dataLength = $this->formatFilesize($row['Data_length']);
                $indexLength = $this->formatFilesize($row['Index_length']);
                $completeLength = $this->formatFilesize($row['Data_length'] + $row['Index_length']);

                $content .= '
					<table class="statistics">
						<tr>
							<td class="infolabel">Records: </td>
							<td>' . $row['Rows'] . '</td>
						</tr>
						<tr>
							<td class="infolabel">Data size: </td>
							<td>' . $dataLength . '</td>
						</tr>
						<tr>
							<td class="infolabel">Index size: </td>
							<td>' . $indexLength . '</td>
						</tr>
						<tr>
							<td class="infolabel">Complete table size: </td>
							<td>' . $completeLength . '</td>
						</tr>';
            }
        }

        $results_per_type = $this->getNumberOfRecordsInIndexPerType();
        if (count($results_per_type)) {
            foreach ($results_per_type as $type => $count) {
                $content .= '<tr><td>' . $type . '</td><td>' . $count . '</td></tr>';
            }
        }
        $content .= '</table>';

        return $content;
    }

    /**
     * format file size from bytes to human readable format
     */
    public function formatFilesize($size, $decimals = 0)
    {
        $sizes = array(" B", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
        if ($size == 0) {
            return ('n/a');
        } else {
            return (round($size / pow(1024, ($i = floor(log($size, 1024)))), $decimals) . $sizes[$i]);
        }
    }

    /*
     * function getIndexedContent
     * @param $pageUid page uid
     */
    public function getIndexedContent($pageUid)
    {
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_index');
        $contentRows = $queryBuilder
            ->select('*')
            ->from('tx_kesearch_index')
            ->where(
                $queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter('page')),
                $queryBuilder->expr()->eq('targetpid', intval($pageUid))
            )
            ->orWhere(
                $queryBuilder->expr()->neq('type', $queryBuilder->createNamedParameter('page')),
                $queryBuilder->expr()->eq('pid', intval($pageUid))
            )
            ->execute();

        while ($row = $contentRows->fetch()) {
            // build tag table
            $tagTable = '<div class="tags" >';
            $tags = GeneralUtility::trimExplode(',', $row['tags'], true);
            foreach ($tags as $tag) {
                $tagTable .= '<span class="tag">' . $tag . '</span>';
            }
            $tagTable .= '</div>';

            // build content
            $timeformat = '%d.%m.%Y %H:%M';
            $content .=
                '<div class="summary">'
                . '<span class="title">' . $row['title'] . '</span>'
                . '<div class="clearer">&nbsp;</div>'
                . $this->renderFurtherInformation('Type', $row['type'])
                . $this->renderFurtherInformation('Words', str_word_count($row['content']))
                . $this->renderFurtherInformation('Language', $row['language'])
                . $this->renderFurtherInformation('Created', strftime($timeformat, $row['crdate']))
                . $this->renderFurtherInformation('Modified', strftime($timeformat, $row['tstamp']))
                . $this->renderFurtherInformation(
                    'Sortdate',
                    ($row['sortdate'] ? strftime($timeformat, $row['sortdate']) : '')
                )
                . $this->renderFurtherInformation(
                    'Starttime',
                    ($row['starttime'] ? strftime($timeformat, $row['starttime']) : '')
                )
                . $this->renderFurtherInformation(
                    'Endtime',
                    ($row['endtime'] ? strftime($timeformat, $row['endtime']) : '')
                )
                . $this->renderFurtherInformation('FE Group', $row['fe_group'])
                . $this->renderFurtherInformation('Target Page', $row['targetpid'])
                . $this->renderFurtherInformation('URL Params', $row['params'])
                . $this->renderFurtherInformation('Original PID', $row['orig_pid'])
                . $this->renderFurtherInformation('Original UID', $row['orig_uid'])
                . $this->renderFurtherInformation('Path', $row['directory'])
                . '<div class="clearer">&nbsp;</div>'
                . '<div class="box"><div class="headline">Abstract</div><div class="content">'
                . nl2br($row['abstract']) . '</div></div>'
                . '<div class="box"><div class="headline">Content</div><div class="content">'
                . nl2br($row['content']) . '</div></div>'
                . '<div class="box"><div class="headline">Tags</div><div class="content">' . $tagTable . '</div></div>'
                . '</div>';
        }

        return $content;
    }

    /**
     * @param string $label
     * @param string $content
     * @return string
     */
    public function renderFurtherInformation($label, $content)
    {
        return
            '<div class="info"><span class="infolabel">'
            . $label
            . ': </span><span class="value">'
            . $content
            . '</span></div>';
    }

    /**
     * @param integer $pageUid
     * @param integer $days
     * @return string
     */
    public function getSearchwordStatistics($pageUid, $days)
    {
        if (!$pageUid) {
            $content =
                '<div class="alert alert-info">'
                . LocalizationUtility::translate(
                    'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:select_a_page',
                    'KeSearch'
                )
                . '</div>';

            return $content;
        }

        // calculate statistic start
        $timestampStart = time() - ($days * 60 * 60 * 24);

        // get data from sysfolder or from single page?
        $isSysFolder = $this->checkSysfolder();

        // get languages
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_stat_word');
        $queryBuilder->getRestrictions()->removeAll();
        $languageResult = $queryBuilder
            ->select('language')
            ->from('tx_kesearch_stat_word')
            ->where(
                $queryBuilder->expr()->gt(
                    'tstamp',
                    $queryBuilder->quote($timestampStart, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $isSysFolder ? 'pid' : 'pageid',
                    $queryBuilder->quote($pageUid, \PDO::PARAM_INT)
                )
            )
            ->groupBy('language')
            ->execute()
            ->fetchAll();

        $content = '';
        if (!count($languageResult)) {
            $content .=
                '<div class="alert alert-info">'
                . 'No statistic data found! Please select the sysfolder where your index is stored or the page '
                . 'where your search plugin is placed.</div>';

            return $content;
        }

        foreach($languageResult as $languageRow) {
            $content .= '<h1 style="clear:left; padding-top:1em;">Language ' . $languageRow['language'] . '</h1>';
            if ($isSysFolder) {
                $content .= $this->getAndRenderStatisticTable(
                    'tx_kesearch_stat_search',
                    $languageRow['language'],
                    $timestampStart,
                    $pidWhere,
                    'searchphrase'
                );
            } else {
                $content .=
                    '<i>Please select the sysfolder where your index is stored for a list of search phrases</i>';
            }
            $content .= $this->getAndRenderStatisticTable(
                'tx_kesearch_stat_word',
                $languageRow['language'],
                $timestampStart,
                $pidWhere,
                'word'
            );
        }
        $content .= '<br style="clear:left;" />';

        return $content;
    }

    /**
     * @param string $table
     * @param integer $language
     * @param integer $timestampStart
     * @param string $pidWhere
     * @param string $tableCol
     * @return string
     */
    public function getAndRenderStatisticTable($table, $language, $timestampStart, $pidWhere, $tableCol)
    {
        $content = '<div style="width=50%; float:left; margin-right:1em;">';
        $content .= '<h2 style="margin:0em;">' . $tableCol . 's</h2>';

        // get statistic data from db
        $queryBuilder = Db::getQueryBuilder($table);
        $queryBuilder->getRestrictions()->removeAll();
        $statisticData = $queryBuilder
            ->add('select', 'count(' . $tableCol . ') as num, ' . $tableCol)
            ->from($table)
            ->add(
                'where',
                'tstamp > ' . $queryBuilder->quote($timestampStart, \PDO::PARAM_INT) .
                ' AND language=' . $queryBuilder->quote($language, \PDO::PARAM_INT) . ' ' .
                $pidWhere
            )
            ->add('groupBy', $tableCol . ' HAVING count(' . $tableCol . ')>0')
            ->add('orderBy', 'num desc')
            ->execute()
            ->fetchAll();

        $numResults = count($statisticData);

        // Todo: render statistics in fluid
        // get statistic
        $i = 1;
        $rows = '';
        if ($numResults) {
            foreach ($statisticData as $row) {
                $cssClass = ($i % 2 == 0) ? 'even' : 'odd';
                $rows .= '<tr>';
                $rows .= '	<td class="' . $cssClass . '">' . $row[$tableCol] . '</td>';
                $rows .= '	<td class="times ' . $cssClass . '">' . $row['num'] . '</td>';
                $rows .= '</tr>';
                $i++;
            }

            $content .=
                '<table class="statistics">
					<tr>
					<th>' . $tableCol . '</th>
					<th>counter</th>
					</tr>'
                . $rows .
                '</table>';
        }

        $content .= '</div>';

        return $content;
    }

    /*
     * check if selected page is a sysfolder
     *
     * @return boolean
     */
    public function checkSysfolder()
    {
        $queryBuilder = Db::getQueryBuilder('pages');
        $page = $queryBuilder
            ->select('doktype')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->quote($this->id, \PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch(0);

        return $page['doktype'] == 254 ? true : false;
    }

    /**
     * Returns the Backend User
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
