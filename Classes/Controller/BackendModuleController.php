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

use TeaminmediasPluswerk\KeSearch\Domain\Repository\IndexRepository;
use TeaminmediasPluswerk\KeSearch\Indexer\IndexerBase;
use TeaminmediasPluswerk\KeSearch\Indexer\IndexerRunner;
use TeaminmediasPluswerk\KeSearch\Lib\Db;
use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Registry;
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
     * @var int
     */
    private $indexingMode;

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
     * initialize action
     */
    public function initializeAction()
    {
        parent::initializeAction();

        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ke_search');

        $this->do = GeneralUtility::_GET('do');

        if (!empty(GeneralUtility::_GET('indexingMode')) && intval(GeneralUtility::_GET('indexingMode')) == IndexerBase::INDEXING_MODE_INCREMENTAL) {
            $this->indexingMode = IndexerBase::INDEXING_MODE_INCREMENTAL;
        } else {
            $this->indexingMode = IndexerBase::INDEXING_MODE_FULL;
        }

        $this->perms_clause = $this->getBackendUser()->getPagePermsClause(1);
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);

        // check access and redirect accordingly
        $access = is_array($this->pageinfo) ? 1 : 0;

        if (($this->id && $access) || ($this->getBackendUser()->isAdmin() && !$this->id)) {
            //proceed normally
        } else {
            if ($this->getActionName() !== 'alert') {
                $this->redirect('alert', $this->getControllerName());
            }
        }
    }

    /**
     * alert action
     */
    public function alertAction()
    {
        // just render the view
    }

    /**
     * start indexing action
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
            $content .= $indexer->startIndexing(true, $this->extConf, '', $this->indexingMode);
        } else {
            if ($this->do == 'rmLock') {
                // remove lock from registry - admin only!
                if ($this->getBackendUser()->isAdmin()) {
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
        $lockTime = SearchHelper::getIndexerStartTime();
        $compareTime = time() - (60 * 60 * 12);
        if ($lockTime !== 0 && $lockTime < $compareTime) {
            // lock is older than 12 hours
            // remove lock and show "start index" button
            $this->registry->removeAllByNamespace('tx_kesearch');
            $lockTime = 0;
        }

        // show information about indexer configurations and number of records
        // if action "start indexing" is not selected
        if ($this->do != 'startindexer') {
            $content .= $this->printNumberOfRecords();
            $content .= $this->printIndexerConfigurations($indexerConfigurations);
        }

        // show "start indexing" or "remove lock" button
        if ($lockTime !== 0) {
            if (!$this->getBackendUser()->isAdmin()) {
                // print warning message for non-admins
                $content .= '<div class="alert alert-danger">';
                $content .= '<p>WARNING!</p>';
                $content .= '<p>The indexer is already running and can not be started twice.</p>';
                $content .= '</div>';
            } else {
                // show 'remove lock' button for admins
                $content .= '<div class="alert alert-info">';
                $content .= '<p>The indexer is already running and can not be started twice.</p>';
                $content .= '</div>';
                $content .= '<p>The indexing process was started at <strong>' . strftime('%c', $lockTime) . '.</p></strong>';
                $content .= '<p>You can remove the lock by clicking the following button.</p>';
                $moduleUrl = $uriBuilder->buildUriFromRoute(
                    'web_KeSearchBackendModule',
                    [
                        'id' => $this->id,
                        'do' => 'rmLock'
                    ]
                );
                $content .= '<p><a class="btn btn-danger" href="' . $moduleUrl . '">Remove Lock</a></p>';
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
                $content .= '<a class="btn btn-primary" href="' . $moduleUrl . '">'
                    . LocalizationUtility::translate('backend.start_indexer_full', 'ke_search')
                    . '</a> ';
                $moduleUrl = $uriBuilder->buildUriFromRoute(
                    'web_KeSearchBackendModule',
                    [
                        'id' => $this->id,
                        'do' => 'startindexer',
                        'indexingMode' => IndexerBase::INDEXING_MODE_INCREMENTAL
                    ]
                );
                $content .= '<a class="btn btn-default" href="' . $moduleUrl . '">'
                    . LocalizationUtility::translate('backend.start_indexer_incremental', 'ke_search')
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
     * indexed content action
     */
    public function indexedContentAction()
    {
        if ($this->id) {
            // page is selected: get indexed content
            $content = '<h3>Index content for PID ' . $this->id;
            $content .= '<span class="small">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:labels.path')
                . ': '
                .
                GeneralUtility::fixed_lgd_cs($this->pageinfo['_thePath'], -50) . '</span></h3>';
            $content .= '<div class="table-fit-wrap">';
            $content .= $this->getIndexedContent($this->id);
            $content .= '</div>';
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
     * index table information action
     */
    public function indexTableInformationAction()
    {
        $content = $this->renderIndexTableInformation();

        $this->view->assign('content', $content);
    }

    /**
     * searchword statistics action
     */
    public function searchwordStatisticsAction()
    {
        // days to show
        $days = 30;
        $data = $this->getSearchwordStatistics($this->id, $days);

        if ($data['error']) {
            $error = $data['error'];
            unset($data['error']);
        }

        $this->view->assign('days', $days);
        $this->view->assign('data', $data);
        $this->view->assign('error', $error);
        $this->view->assign('languages', $this->getLanguages());
    }

    /**
     * clear search index action
     */
    public function clearSearchIndexAction()
    {
        // get uri builder
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        // admin only access
        if ($this->getBackendUser()->isAdmin()) {
            if ($this->do == 'clear') {
                $databaseConnection = Db::getDatabaseConnection('tx_kesearch_index');
                $databaseConnection->truncate('tx_kesearch_index');
            }
        }

        // build "clear index" link
        $moduleUrl = $uriBuilder->buildUriFromRoute(
            'web_KeSearchBackendModule',
            [
                'id' => $this->id,
                'do' => 'clear'
            ]
        );

        $this->view->assign('moduleUrl', $moduleUrl);
        $this->view->assign('isAdmin', $this->getBackendUser()->isAdmin());
        $this->view->assign('indexCount', $this->getNumberOfRecordsInIndex());

    }

    /**
     * last indexing report action
     */
    public function lastIndexingReportAction()
    {
        $this->view->assign('logEntry', $this->getLastIndexingReport());
    }

    /**
     * get report from sys_log
     * @return string
     * @author Christian Bülter <christian.buelter@inmedias.de>
     * @since 29.05.15
     */
    public function getLastIndexingReport()
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
            ->orderBy('tstamp', 'DESC')
            ->setMaxResults(1)
            ->execute()
            ->fetchAll();

        return $logResults;
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
        $content = '<h2>Indexers</h2>';
        // show indexer names
        if ($indexerConfigurations) {
            $content .= '<div class="row"><div class="col-md-6">';
            $content .= '<table class="table table-striped table-hover">';
            $content .= '<colgroup><col><col width="100"><col width="100"><col width="100"></colgroup>';
            $content .= '<tr><th></th><th>Type</th><th>UID</th><th>PID</th></tr>';
            foreach ($indexerConfigurations as $indexerConfiguration) {
                $content .= '<tr>'
                    . '<th>' . $this->encode($indexerConfiguration['title']) . '</th>'
                    . '<td>'
                    . '<span class="label label-primary">' . $indexerConfiguration['type'] . '</span>'
                    . '</td>'
                    . '<td>'
                    . $indexerConfiguration['uid']
                    . '</td>'
                    . '<td>'
                    . $indexerConfiguration['pid']
                    . '</td>'
                    . '</tr>';
            }
            $content .= '</table>';
            $content .= '</div></div>';
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
        $content = '<h2>Index statistics</h2>';
        $numberOfRecords = $this->getNumberOfRecordsInIndex();

        if ($numberOfRecords) {
            $content .=
                '<p>'
                . LocalizationUtility::translate(
                    'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:index_contains',
                    'KeSearch'
                )
                . ' <strong>'
                . $numberOfRecords
                . '</strong> '
                . LocalizationUtility::translate(
                    'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:records',
                    'KeSearch'
                )
                . '</p>';

            $lastRun = $this->registry->get('tx_kesearch', 'lastRun');
            if ($lastRun) {
                $content .= '<p>'
                    . LocalizationUtility::translate(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:last_indexing',
                        'KeSearch'
                    )
                    . ' '
                    . SearchHelper::formatTimestamp($lastRun['endTime'])
                    . '.</p>';
            }

            $content .= '<div class="row"><div class="col-md-6">';
            $content .= '<table class="table table-striped table-hover">';
            $content .= '<colgroup><col><col width="100"></colgroup>';
            $content .= '<tr><th>Type</th><th>Count</th></tr>';

            /** @var IndexRepository $indexRepository */
            $indexRepository = GeneralUtility::makeInstance(IndexRepository::class);
            $results_per_type = $indexRepository->getNumberOfRecordsInIndexPerType();
            $first = true;
            foreach ($results_per_type as $type => $count) {
                $content .= '<tr><td><span class="label label-primary">' . $type . '</span></td><td>' . $count . '</td></tr>';
            }

            $content .= '</table>';
            $content .= '</div></div>';
        }

        return $content;
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
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-striped table-hover">
                            <colgroup><col><col width="100"></colgroup>
                            <tr>
                                <th>Records: </th>
                                <td>' . $row['Rows'] . '</td>
                            </tr>
                            <tr>
                                <th>Data size: </th>
                                <td>' . $dataLength . '</td>
                            </tr>
                            <tr>
                                <th>Index size: </th>
                                <td>' . $indexLength . '</td>
                            </tr>
                            <tr>
                                <th>Complete table size: </th>
                                <td>' . $completeLength . '</td>
                            </tr>
                        </table>
                    </div>
              </div>';
            }
        }

        /** @var IndexRepository $indexRepository */
        $indexRepository = GeneralUtility::makeInstance(IndexRepository::class);
        $results_per_type = $indexRepository->getNumberOfRecordsInIndexPerType();
        if (count($results_per_type)) {
            $content .= '<div class="row"><div class="col-md-6">';
            $content .= '<table class="table table-striped table-hover">';
            $content .= '<colgroup><col><col width="100"></colgroup>';
            foreach ($results_per_type as $type => $count) {
                $content .= '<tr><th><span class="label label-primary">' . $type . '</span></th><td>' . $count . '</td></tr>';
            }
            $content .= '</table>';
            $content .= '</div></div>';
        }

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
                $queryBuilder->expr()->neq('type', $queryBuilder->createNamedParameter('page')) .
                ' AND ' .
                $queryBuilder->expr()->eq('pid', intval($pageUid))
            )
            ->execute();

        $content = '<table class="table table-hover">'
            . '<thead>'
                . '<tr>'
                    . '<th>Title</th>'
                    . '<th>Type</th>'
                    . '<th>Language</th>'
                    . '<th>Words</th>'
                    . '<th>Created</th>'
                    . '<th>Modified</th>'
                    . '<th>Target Page</th>'
                    . '<th>URL Params</th>'
                    . '<th></th>'
                . '</tr>'
            . '</thead>';
        while ($row = $contentRows->fetch()) {
            // build tag table
            $tagTable = '';
            $tags = GeneralUtility::trimExplode(',', $row['tags'], true);
            foreach ($tags as $tag) {
                $tagTable .= '<span class="badge badge-info">' . $this->encode($tag) . '</span> ';
            }

            // build content
            $timeformat = '%d.%m.%Y %H:%M';
            $content .=
                '<tr>'
                    . '<td>' . $this->truncateMiddle($this->encode($row['title']), 100) . '</td>'
                    . '<td><span class="label label-primary">' . $this->encode($row['type']) . '</span></td>'
                    . '<td>' . $this->encode($row['language']) . '</td>'
                    . '<td>' . $this->encode(str_word_count($row['content'])) . '</td>'
                    . '<td>' . $this->encode(strftime($timeformat, $row['crdate'])) . '</td>'
                    . '<td>' . $this->encode(strftime($timeformat, $row['tstamp'])) . '</td>'
                    . '<td>' . $this->encode($row['targetpid']) . '</td>'
                    . '<td>' . $this->encode($row['params']) . '</td>'
                    . '<td><a class="btn btn-default" data-action="expand" data-toggle="collapse" data-target="#ke' . $row['uid'] . '" title="Expand record"><span class="t3js-icon icon icon-size-small icon-state-default icon-actions-document-info" data-identifier="actions-document-info"><span class="icon-markup"><img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/actions/actions-document-info.svg" width="16" height="16"></span></span></a></td>'
                . '</tr>'
                . '<tr class="collapse" id="ke' . $row['uid'] . '">'
                    . '<td colspan="9">'
                        . '<table class="table">'
                            . '<thead>'
                                . '<tr>'
                                    . '<th>Original PID</th>'
                                    . '<th>Original UID</th>'
                                    . '<th>FE Group</th>'
                                    . '<th>Sort Date</th>'
                                    . '<th>Start Date</th>'
                                    . '<th>End Date</th>'
                                    . '<th>Tags</th>'
                                . '</tr>'
                            . '</thead>'
                            . '<tr>'
                                . '<td>' . $this->encode($row['orig_pid']) . '</td>'
                                . '<td>' . $this->encode($row['orig_uid']) . '</td>'
                                . '<td>' . $this->encode($row['fe_group']) . '</td>'
                                . '<td>' . $this->encode($row['sortdate'] ? strftime($timeformat, $row['sortdate']) : '') . '</td>'
                                . '<td>' . $this->encode($row['starttime'] ? strftime($timeformat, $row['starttime']) : '') . '</td>'
                                . '<td>' . $this->encode($row['endtime'] ? strftime($timeformat, $row['endtime']) : '') . '</td>'
                                . '<td>' . $tagTable . '</td>'
                            . '</tr>'
                            . '<tr>'
                                . '<td colspan="7">'
                                    . ((trim($row['abstract'])) ? (
                                        '<p><strong>Abstract</strong></p>'
                                        . '<p>' . nl2br($this->encode($row['abstract'])) . '</p>'
                                    ) : '')
                                    . ((trim($row['content'])) ? (
                                        '<p><strong>Content</strong></p>'
                                        . '<p>' . nl2br($this->encode($row['content'])) . '</p>'
                                    ) : '')
                                . '</td>'
                            . '</tr>'
                        . '</table>'
                    . '</td>'
                . '</tr>';
        }

        return $content;
    }

    /**
     * @param string $label
     * @param string $content
     * @return string
     */
    public function encode($input)
    {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param integer $pageUid
     * @param integer $days
     * @return string
     */
    public function getSearchwordStatistics($pageUid, $days)
    {
        $statisticData = [];

        if (!$pageUid) {
            $statisticData['error'] = LocalizationUtility::translate(
                'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:select_a_page',
                'KeSearch'
            );
            return $statisticData;
        }

        // calculate statistic start
        $timestampStart = time() - ($days * 60 * 60 * 24);

        // get data from sysfolder or from single page?
        $isSysFolder = $this->checkSysfolder();

        // set folder or single page where the data is selected from
        $pidWhere = $isSysFolder ? ' AND pid=' . intval($pageUid) . ' ' : ' AND pageid=' . intval($pageUid) . ' ';

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
            $statisticData['error'] =
                'No statistic data found! Please select the sysfolder
                where your index is stored or the page where your search plugin is placed.';
            return $statisticData;
        }

        foreach ($languageResult as $languageRow) {
            if ($isSysFolder) {
                $statisticData[$languageRow['language']]['searchphrase'] = $this->getStatisticTableData(
                    'tx_kesearch_stat_search',
                    $languageRow['language'],
                    $timestampStart,
                    $pidWhere,
                    'searchphrase'
                );
            } else {
                $statisticData['error'] = 'Please select the sysfolder where your index is stored for a list of search phrases';
            }

            $statisticData[$languageRow['language']]['word'] = $this->getStatisticTableData(
                'tx_kesearch_stat_word',
                $languageRow['language'],
                $timestampStart,
                $pidWhere,
                'word'
            );
        }

        return $statisticData;
    }

    /**
     * @param string $table
     * @param integer $language
     * @param integer $timestampStart
     * @param string $pidWhere
     * @param string $tableCol
     * @return string
     */
    public function getStatisticTableData($table, $language, $timestampStart, $pidWhere, $tableCol)
    {
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

        return $statisticData;
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

    /**
     * @return array
     */
    protected function getLanguages()
    {
        $languages = [];
        $languages[0] = 'Default';

        $queryBuilder = Db::getQueryBuilder('sys_language');
        $languageRows = $queryBuilder
            ->select('uid', 'title')
            ->from('sys_language')
            ->execute()
            ->fetchAll();

        foreach ($languageRows as $row) {
            $languages[$row['uid']] = $row['title'];
        }

        return $languages;
    }

    /**
     * Removes characters from the middle of the string to ensure it is no more
     * than $maxLength characters long.
     *
     * Removed characters are replaced with "..."
     *
     * This method will give priority to the right-hand side of the string when
     * data is truncated.
     *
     * @param $string
     * @param $maxLength
     * @return string
     */
    protected function truncateMiddle($string, $maxLength) {
        // Early exit if no truncation necessary
        if (strlen($string) <= $maxLength) {
            return $string;
        }

        $numRightChars = ceil($maxLength * 0.7);
        $numLeftChars = floor($maxLength * 0.3) - 5; // to accommodate the "..."

        return sprintf(
            "%s[...]%s",
            substr($string, 0, intval($numLeftChars)),
            substr($string, intval(0 - $numRightChars))
        );
    }


}
