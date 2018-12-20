<?php
namespace TeaminmediasPluswerk\KeSearch\Indexer;

/***************************************************************
 *  Copyright notice
 *  (c) 2010 Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
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

use TeaminmediasPluswerk\KeSearch\Lib\Db;
use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for indexer classes.
 *
 * @author    Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 * @author    Stefan Froemken
 * @author    Christian Bülter <christian.buelter@inmedias.de>
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class IndexerBase
{
    public $startMicrotime = 0;
    public $indexerConfig = array(); // current indexer configuration

    /**
     * @var IndexerRunner
     */
    public $pObj;

    /**
     * needed to get all recursive pids
     */
    public $queryGen;

    /**
     * @var array
     */
    protected $errors = array();

    /**
     * @var array
     */
    public $pageRecords;

    /**
     * Constructor of this object
     * @param $pObj
     */
    public function __construct($pObj)
    {
        $this->startMicrotime = microtime(true);
        $this->pObj = $pObj;
        $this->indexerConfig = $this->pObj->indexerConfig;
        $this->queryGen = GeneralUtility::makeInstance(QueryGenerator::class);
    }


    /**
     * get all recursive contained pids of given Page-UID
     * regardless if we need them or if they are sysfolders, links or what ever
     * @param string $startingPointsRecursive comma-separated list of pids of recursive start-points
     * @param string $singlePages comma-separated list of pids of single pages
     * @return array List of page UIDs
     */
    public function getPagelist($startingPointsRecursive = '', $singlePages = '')
    {
        // make array from list
        $pidsRecursive = GeneralUtility::trimExplode(',', $startingPointsRecursive, true);
        $pidsNonRecursive = GeneralUtility::trimExplode(',', $singlePages, true);

        // add recursive pids
        $pageList = '';
        foreach ($pidsRecursive as $pid) {
            $pageList .= $this->queryGen->getTreeList($pid, 99, 0, '1=1') . ',';
        }

        // add non-recursive pids
        foreach ($pidsNonRecursive as $pid) {
            $pageList .= $pid . ',';
        }

        // convert to array
        $pageUidArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $pageList, true);

        return $pageUidArray;
    }


    /**
     * get array with all pages
     * but remove all pages we don't want to have
     * @param array $uids Array with all page uids
     * @param string $whereClause Additional where clause for the query
     * @param string $table The table to select the fields from
     * @param string $fields The requested fields
     * @return array Array containing page records with all available fields
     */
    public function getPageRecords(array $uids, $whereClause = '', $table = 'pages', $fields = 'pages.*')
    {

        $queryBuilder = Db::getQueryBuilder('tx_kesearch_index');
        $where = [];
        $where[] = $queryBuilder->expr()->in('pages.uid', implode(',', $uids));
        // index only pages which are searchable
        // index only page which are not hidden
        $where[] = $queryBuilder->expr()->neq(
            'pages.no_search',
            $queryBuilder->createNamedParameter(1,\PDO::PARAM_INT)
        );
        $where[] = $queryBuilder->expr()->eq(
            'pages.hidden',
            $queryBuilder->createNamedParameter(0,\PDO::PARAM_INT)
        );
        $where[] = $queryBuilder->expr()->eq(
            'pages.deleted',
            $queryBuilder->createNamedParameter(0,\PDO::PARAM_INT)
        );

        $tables = GeneralUtility::trimExplode(',',$table);
        $query = $queryBuilder
            ->select($fields);
        foreach ($tables as $table) {
            $query->from($table);
        }
        $query->where(...$where);

        // add additional where clause
        if ($whereClause) {
            $query->add('where', $whereClause);
        }

        $pageRows = $query->execute()->fetchAll();

        $pages = [];
        foreach ($pageRows as $row) {
            $pages[$row['uid']] = $row;
        }

        return $pages;
    }


    /**
     * get a list of pids
     * @param string $startingPointsRecursive
     * @param string $singlePages
     * @param string $table
     * @return array Array containing uids of pageRecords
     * Todo enable fields
     */
    public function getPidList($startingPointsRecursive = '', $singlePages = '', $table = 'pages')
    {
        // get all pages. Regardless if they are shortcut, sysfolder or external link
        $indexPids = $this->getPagelist($startingPointsRecursive, $singlePages);

        // add complete page record to list of pids in $indexPids
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_index');
        $where = $queryBuilder->quoteIdentifier($table)
            . '.' . $queryBuilder->quoteIdentifier('pid')
            . ' = ' . $queryBuilder->quoteIdentifier('pages')
            . '.' . $queryBuilder->quoteIdentifier('uid');
        $this->pageRecords = $this->getPageRecords($indexPids, $where, 'pages,' . $table, 'pages.*');
        if (count($this->pageRecords)) {
            // create a new list of allowed pids
            return array_keys($this->pageRecords);
        } else {
            return array('0' => 0);
        }
    }


    /**
     * Add Tags to records array
     *
     * @param array $uids Simple array with uids of pages
     * @param string $pageWhere additional where-clause
     * @return void
     */
    public function addTagsToRecords($uids, $pageWhere = '1=1')
    {

        $tagChar = $this->pObj->extConf['prePostTagChar'];

        // add tags which are defined by page properties
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_filteroptions');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $fields = 'pages.*, GROUP_CONCAT(CONCAT("'
            . $tagChar
            . '", tx_kesearch_filteroptions.tag, "'
            . $tagChar
            . '")) as tags';

        $where = 'pages.uid IN (' . implode(',', $uids) . ')';
        $where .= ' AND pages.tx_kesearch_tags <> "" ';
        $where .= ' AND FIND_IN_SET(tx_kesearch_filteroptions.uid, pages.tx_kesearch_tags)';

        $tagQuery = $queryBuilder
            ->add('select', $fields)
            ->from('pages')
            ->from('tx_kesearch_filteroptions')
            ->add('where', $where)
            ->groupBy('pages.uid')
            ->execute();

        while ($row = $tagQuery->fetch()) {
            $this->pageRecords[$row['uid']]['tags'] = $row['tags'];
        }

        // add system categories as tags
        foreach ($uids as $page_uid) {
            SearchHelper::makeSystemCategoryTags($this->pageRecords[$page_uid]['tags'], $page_uid, 'pages');
        }

        // add tags which are defined by filteroption records
        $table = 'tx_kesearch_filteroptions';
        $queryBuilder = Db::getQueryBuilder($table);
        $filterOptionsRows = $queryBuilder
            ->select('automated_tagging', 'automated_tagging_exclude', 'tag')
            ->from($table)
            ->where(
                $queryBuilder->expr()->neq(
                    'automated_tagging',
                    $queryBuilder->quote("", \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();

        $where = $pageWhere . ' AND no_search <> 1 ';

        foreach ($filterOptionsRows as $row) {
            $tempTags = array();

            if ($row['automated_tagging_exclude'] > '') {
                $whereRow = $where . 'AND FIND_IN_SET(pages.pid, "' . $row['automated_tagging_exclude'] . '") = 0';
            } else {
                $whereRow = $where;
            }

            $pageList = array();
            $automated_tagging_arr = explode(',', $row['automated_tagging']);
            foreach ($automated_tagging_arr as $key => $value) {
                $tmpPageList = GeneralUtility::trimExplode(
                    ',',
                    $this->queryGen->getTreeList($value, 99, 0, $whereRow)
                );
                $pageList = array_merge($tmpPageList, $pageList);
            }

            foreach ($pageList as $uid) {
                if ($this->pageRecords[$uid]['tags']) {
                    $this->pageRecords[$uid]['tags'] .= ',' . $tagChar . $row['tag'] . $tagChar;
                } else {
                    $this->pageRecords[$uid]['tags'] = $tagChar . $row['tag'] . $tagChar;
                }
            }
        }
    }

    /**
     * shows time used
     *
     * @author  Christian Buelter <buelter@kennziffer.com>
     * @return  string
     */
    public function showTime()
    {
        // calculate duration of indexing process
        $endMicrotime = microtime(true);
        $duration = ceil(($endMicrotime - $this->startMicrotime) * 1000);

        // show sec or ms?
        if ($duration > 1000) {
            $duration /= 1000;
            $duration = intval($duration);
            return '<p><i>Indexing process for "'
            . $this->indexerConfig['title']
            . '" took '
            . $duration
            . ' s.</i> </p>'
            . "\n\n";
        } else {
            return '<p><i>Indexing process for "'
            . $this->indexerConfig['title']
            . '" took '
            . $duration
            . ' ms.</i> </p>'
            . "\n\n";
        }
    }

    /**
     * Prints errors which occured while indexing.
     *
     * @return string
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 26.11.13
     */
    public function showErrors()
    {
        if (count($this->errors)) {
            $messages = [];
            $messages[] = GeneralUtility::makeInstance(
                FlashMessage::class,
                'There were errors. Check ke_search log for details.'. "\n",
                '',
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                false
            );
            return $this->renderFlashMessages($messages);
        } else {
            return '';
        }
    }

    /**
     * Renders the flash message.
     *
     * @param array $flashMessages
     * @return string The flash message as HTML.
     */
    protected function renderFlashMessages($flashMessages)
    {
        return GeneralUtility::makeInstance(FlashMessageRendererResolver::class)
            ->resolve()
            ->render($flashMessages);
    }

    /**
     * adds an error to the error array
     * @param string or array of strings $errorMessage
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 26.11.13
     */
    public function addError($errorMessage)
    {
        if (is_array($errorMessage)) {
            if (count($errorMessage)) {
                foreach ($errorMessage as $message) {
                    $this->errors[] = $message;
                }
            }
        } else {
            $this->errors[] = $errorMessage;
        }
    }

    /**
     * @return array
     * @author Christian Bülter <buelter@kennziffer.com>
     * @since 26.11.13
     */
    public function getErrors()
    {
        return $this->errors;
    }

}
