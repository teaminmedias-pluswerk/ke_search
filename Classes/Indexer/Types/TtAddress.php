<?php

namespace TeaminmediasPluswerk\KeSearch\Indexer\Types;

use TeaminmediasPluswerk\KeSearch\Domain\Repository\IndexRepository;
use TeaminmediasPluswerk\KeSearch\Domain\Repository\TtAddressRepository;
use TeaminmediasPluswerk\KeSearch\Indexer\IndexerBase;
use TeaminmediasPluswerk\KeSearch\Lib\Db;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 * @author    Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 * @author    Stefan Froemken
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class TtAddress extends IndexerBase
{

    /**
     * Initializes indexer for tt_address
     */
    public function __construct($pObj)
    {
        parent::__construct($pObj);
    }


    /**
     * This function was called from indexer object and saves content to index table
     * @return string content which will be displayed in backend
     */
    public function startIndexing()
    {

        // get all address records from pid set in indexerConfig
        $fields = '*';
        $table = 'tt_address';
        $indexPids = $this->getPidList(
            $this->indexerConfig['startingpoints_recursive'],
            $this->indexerConfig['sysfolder'],
            $table
        );

        if ($this->indexerConfig['index_use_page_tags']) {
            // add the tags of each page to the global page array
            $this->pageRecords = $this->getPageRecords($indexPids);
            $this->addTagsToRecords($indexPids);
        }

        $queryBuilder = Db::getQueryBuilder($table);
        $where = [];
        $where[] = $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($indexPids, Connection::PARAM_INT_ARRAY));

        // in incremental mode get only rows which have been modified since last indexing time
        if ($this->indexingMode == self::INDEXING_MODE_INCREMENTAL) {
            $where[] = $queryBuilder->expr()->gte('tstamp', $this->lastRunStartTime);
        }

        $addressRows = $queryBuilder
            ->select($fields)
            ->from($table)
            ->where(...$where)
            ->execute()
            ->fetchAll();

        // no address records found
        if (!count($addressRows)) {
            $content = 'No address records found!';
            return $content;
        } else {
            foreach ($addressRows as $addressRow) {
                $shouldBeIndexed = true;

                if (!$this->recordIsLive($addressRow)) {
                    $shouldBeIndexed = false;
                }

                if ($shouldBeIndexed) {
                    $this->pObj->logger->debug('Indexing tt_address record UID ' . $addressRow['uid']);
                } else {
                    $this->pObj->logger->debug('Skipping tt_address record UID ' . $addressRow['uid']);
                    continue;
                }

                $abstract = '';
                $content = '';

                // set title, use company if set, otherwise name
                $title = !empty($addressRow['company'])
                    ? $addressRow['company'] :
                    (!empty($addressRow['name']) ? $addressRow['name']
                        : ($addressRow['first_name'] . ' ' . $addressRow['last_name']));

                // use description as abstract if set
                if (!empty($addressRow['description'])) {
                    $abstract = $addressRow['description'];
                }

                // build content
                if (!empty($addressRow['company'])) {
                    $content .= $addressRow['company'] . "\n";
                }
                if (!empty($addressRow['title'])) {
                    $content .= $addressRow['title'] . ' ';
                }
                if (!empty($addressRow['name'])) {
                    $content .= $addressRow['name'] . "\n"; // name
                } else {
                    if (!empty($addressRow['first_name'])) {
                        $content .= $addressRow['first_name'] . ' ';
                    }
                    if (!empty($addressRow['middle_name'])) {
                        $content .= $addressRow['middle_name'] . ' ';
                    }
                    if (!empty($addressRow['last_name'])) {
                        $content .= $addressRow['last_name'] . ' ';
                    }
                    if (!empty($addressRow['last_name'])
                        || !empty($addressRow['middle_name'])
                        || !empty($addressRow['middle_name'])) {
                        $content .= "\n";
                    }
                }
                if (!empty($addressRow['address'])) {
                    $content .= $addressRow['address'] . "\n";
                }
                if (!empty($addressRow['zip'])) {
                    $content .= $addressRow['zip'] . "\n";
                }
                if (!empty($addressRow['city'])) {
                    $content .= $addressRow['city'] . "\n";
                }
                if (!empty($addressRow['country'])) {
                    $content .= $addressRow['country'] . "\n";
                }
                if (!empty($addressRow['region'])) {
                    $content .= $addressRow['region'] . "\n";
                }
                if (!empty($addressRow['email'])) {
                    $content .= $addressRow['email'] . "\n";
                }
                if (!empty($addressRow['phone'])) {
                    $content .= $addressRow['phone'] . "\n";
                }
                if (!empty($addressRow['fax'])) {
                    $content .= $addressRow['fax'] . "\n";
                }
                if (!empty($addressRow['mobile'])) {
                    $content .= $addressRow['mobile'] . "\n";
                }
                if (!empty($addressRow['www'])) {
                    $content .= $addressRow['www'];
                }

                // put content together
                $fullContent = $abstract . "\n" . $content;

                // generate detail view link, example:
                // index.php?id=123&tx_ttaddress_listview%5Baction%5D=show&tx_ttaddress_listview%5Baddress%5D=1&tx_ttaddress_listview%5Bcontroller%5D=Address
                $paramsSingleView['tx_ttaddress_listview']['address'] = $addressRow['uid'];
                $paramsSingleView['tx_ttaddress_listview']['controller'] = 'Address';
                $paramsSingleView['tx_ttaddress_listview']['action'] = 'show';
                $params = '&' . http_build_query($paramsSingleView, null, '&');
                $params = rawurldecode($params);

                // no tags yet
                if ($this->indexerConfig['index_use_page_tags']) {
                    $tagContent = $this->pageRecords[intval($addressRow['pid'])]['tags'];
                } else {
                    $tagContent = '';
                }

                // set additional fields for sorting
                $additionalFields = array(
                    'sortdate' => $addressRow['tstamp'],
                );

                // fill orig_uid
                if (isset($addressRow['uid']) && $addressRow['uid'] > 0) {
                    $additionalFields['orig_uid'] = $addressRow['uid'];
                }

                // fill orig_pid
                if (isset($addressRow['pid']) && $addressRow['pid'] > 0) {
                    $additionalFields['orig_pid'] = $addressRow['pid'];
                }

                // make it possible to modify the indexerConfig via hook
                $indexerConfig = $this->indexerConfig;

                // add some fields which you may set in your own hook
                $customfields = array(
                    'sys_language_uid' => $addressRow['sys_language_uid'],
                    'starttime' => 0,
                    'endtime' => 0,
                    'fe_group' => ''
                );

                // hook for custom modifications of the indexed data, e. g. the tags
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyAddressIndexEntry'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyAddressIndexEntry'] as $_classRef) {
                        $_procObj = GeneralUtility::makeInstance($_classRef);
                        $_procObj->modifyAddressIndexEntry(
                            $title,
                            $abstract,
                            $fullContent,
                            $params,
                            $tagContent,
                            $addressRow,
                            $additionalFields,
                            $indexerConfig,
                            $customfields
                        );
                    }
                }

                // store in index
                $this->pObj->storeInIndex(
                    $indexerConfig['storagepid'],       // storage PID
                    $title,                             // page/record title
                    'tt_address',                       // content type
                    $indexerConfig['targetpid'],        // target PID: where is the single view?
                    $fullContent,                       // indexed content, includes the title (linebreak after title)
                    $tagContent,                        // tags
                    $params,                            // typolink params for singleview
                    $abstract,                          // abstract
                    $customfields['sys_language_uid'],  // language uid
                    $customfields['starttime'],         // starttime
                    $customfields['endtime'],           // endtime
                    $customfields['fe_group'],          // fe_group
                    false,                              // debug only?
                    $additionalFields                   // additional fields added by hooks
                );

            }
        }

        return count($addressRows) . ' address records have been indexed.';
    }

    /**
     * @return string
     */
    public function startIncrementalIndexing(): string
    {
        $this->indexingMode = self::INDEXING_MODE_INCREMENTAL;
        $content = $this->startIndexing();
        $content .= $this->removeDeleted();
        return $content;
    }

    /**
     * Removes index records for the records which have been deleted since the last indexing.
     * Only needed in incremental indexing mode since there is a dedicated "cleanup" step in full indexing mode.
     *
     * @return string
     */
    public function removeDeleted(): string
    {
        /** @var IndexRepository $indexRepository */
        $indexRepository = GeneralUtility::makeInstance(IndexRepository::class);

        /** @var TtAddressRepository $ttAddressRepository */
        $ttAddressRepository = GeneralUtility::makeInstance(TtAddressRepository::class);

        // get the pages from where to index the tt_address records
        $folders = $this->getPagelist(
            $this->indexerConfig['startingpoints_recursive'],
            $this->indexerConfig['sysfolder']
        );

        // Fetch all records which have been deleted since the last indexing
        $records = $ttAddressRepository->findAllDeletedAndHiddenByPidListAndTimestampInAllLanguages($folders, $this->lastRunStartTime);

        // and remove the corresponding index entries
        $count = $indexRepository->deleteCorrespondingIndexRecords('tt_address', $records, $this->indexerConfig);
        $message = LF . 'Found ' . $count . ' deleted and hidden record(s).';
        return $message;
    }
}
