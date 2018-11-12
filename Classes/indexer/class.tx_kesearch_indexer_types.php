<?php
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

use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 * @author    Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 * @author    Stefan Froemken
 * @author    Christian Bülter <christian.buelter@inmedias.de>
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class tx_kesearch_indexer_types
{
    public $startMicrotime = 0;
    public $indexerConfig = array(); // current indexer configuration

    /**
     * @var tx_kesearch_indexer
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
     * Constructor of this object
     * @param $pObj
     */
    public function __construct($pObj)
    {
        $this->startMicrotime = microtime(true);
        $this->pObj = $pObj;
        $this->indexerConfig = $this->pObj->indexerConfig;
        $this->queryGen = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\QueryGenerator');
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
        $where = 'pages.uid IN (' . implode(',', $uids) . ') ';
        // index only pages which are searchable
        // index only page which are not hidden
        $where .= ' AND pages.no_search <> 1 AND pages.hidden=0 AND pages.deleted=0';

        // additional where clause
        $where .= $whereClause;

        $pages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            $fields,
            $table,
            $where,
            '',
            '',
            '',
            'uid'
        );

        return $pages;
    }


    /**
     * get a list of pids
     * @param string $startingPointsRecursive
     * @param string $singlePages
     * @param string $table
     * @return array Array containing uids of pageRecords
     */
    public function getPidList($startingPointsRecursive = '', $singlePages = '', $table = 'pages')
    {
        // get all pages. Regardless if they are shortcut, sysfolder or external link
        $indexPids = $this->getPagelist($startingPointsRecursive, $singlePages);

        // add complete page record to list of pids in $indexPids
        $where = ' AND ' . $table . '.pid = pages.uid ';
        $where .= BackendUtility::BEenableFields($table);
        $where .= BackendUtility::deleteClause($table);
        $this->pageRecords = $this->getPageRecords($indexPids, $where, 'pages,' . $table, 'pages.*');
        if (!empty($this->pageRecords)) {
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
     * @return array extended array with uids and tags for records
     */
    public function addTagsToRecords($uids, $pageWhere = '1=1')
    {
        $tagChar = $this->pObj->extConf['prePostTagChar'];

        // add tags which are defined by page properties
        $fields = 'pages.*, GROUP_CONCAT(CONCAT("'
            . $tagChar
            . '", tx_kesearch_filteroptions.tag, "'
            . $tagChar
            . '")) as tags';
        $table = 'pages, tx_kesearch_filteroptions';
        $where = 'pages.uid IN (' . implode(',', $uids) . ')';
        $where .= ' AND pages.tx_kesearch_tags <> "" ';
        $where .= ' AND FIND_IN_SET(tx_kesearch_filteroptions.uid, pages.tx_kesearch_tags)';
        $where .= BackendUtility::BEenableFields('tx_kesearch_filteroptions');
        $where .= BackendUtility::deleteClause('tx_kesearch_filteroptions');

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where, 'pages.uid', '', '');
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $this->pageRecords[$row['uid']]['tags'] = $row['tags'];
        }

        // add system categories as tags
        foreach ($uids as $page_uid) {
            tx_kesearch_helper::makeSystemCategoryTags($this->pageRecords[$page_uid]['tags'], $page_uid, 'pages');
        }

        // add tags which are defined by filteroption records
        $fields = 'automated_tagging, automated_tagging_exclude, tag';
        $table = 'tx_kesearch_filteroptions';
        $where = 'automated_tagging <> "" ';
        $where .= BackendUtility::BEenableFields('tx_kesearch_filteroptions');
        $where .= BackendUtility::deleteClause('tx_kesearch_filteroptions');

        $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, $where);

        $where = $pageWhere . ' AND no_search <> 1 ';

        foreach ($rows as $row) {
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
            $messages = array();

            foreach ($this->errors as $errorMessage) {
                /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
                $message = GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    $errorMessage,
                    '',
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                    false
                );

                $messages[] = $this->renderFlashMessage($message);
            }

            return implode('<br>', $messages);
        } else {
            return '';
        }
    }

    /**
     * Renders the flash message.
     *
     * @param \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage
     * @return string The flash message as HTML.
     */
    protected function renderFlashMessage(\TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage)
    {
        $title = '';
        if (!empty($this->title)) {
            $title = '<h4 class="alert-title">' . $flashMessage->getTitle() . '</h4>';
        }
        $message = '
			<div class="alert ' . $flashMessage->getClass() . '">
				<div class="media">
					<div class="media-left">
						<span class="fa-stack fa-lg">
							<i class="fa fa-circle fa-stack-2x"></i>
							<i class="fa fa-' . $flashMessage->getIconName() . ' fa-stack-1x"></i>
						</span>
					</div>
					<div class="media-body">
						' . $title . '
						<div class="alert-message">' . $flashMessage->getMessage() . '</div>
					</div>
				</div>
			</div>';
        return $message;
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


    /**
     * @param $tagUid
     * @param bool $clearText
     * @return mixed
     */
    public function getTag($tagUid, $clearText = false)
    {
        $fields = 'title,tag';
        $table = 'tx_kesearch_filteroptions';
        $where = 'uid="' . intval($tagUid) . '" ';
        $where .= BackendUtility::BEenableFields($table);
        $where .= BackendUtility::deleteClause($table);

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $table, $where, '', '', '1');
        $anz = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
        $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);

        if ($clearText) {
            return $row['title'];
        } else {
            return $row['tag'];
        }
    }
}
