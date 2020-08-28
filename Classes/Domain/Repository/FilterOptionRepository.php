<?php
namespace TeaminmediasPluswerk\KeSearch\Domain\Repository;

use Doctrine\DBAL\Driver\Statement;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *  (c) 2020 Christian Bülter
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
 * Hooks for ke_search
 * @author Christian Bülter
 * @package TYPO3
 * @subpackage ke_search
 */
class FilterOptionRepository {

    /**
     * Internal storage for database table fields
     *
     * @var array
     */
    protected $tableFields = [];

    /**
     * @var string
     */
    protected $tableName = 'tx_kesearch_filteroptions';
    protected $parentTableName = 'tx_kesearch_filters';

    public function findByFilterUid($filterUid)
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        /** @var FilterRepository $filterRepository */
        $filterRepository = GeneralUtility::makeInstance(FilterRepository::class);
        $filter = $filterRepository->findByUid($filterUid);

        if (empty($filter) || empty($filter['options'])) {
            return [];
        }

        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->tableName);
        return $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->in('uid',
                    $queryBuilder->createNamedParameter(
                        GeneralUtility::trimExplode(',', $filter['options']),
                        Connection::PARAM_INT_ARRAY
                    )
                )
            )
            ->execute()
            ->fetchAll();
    }

    public function findByTag($tag)
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->tableName);
        return $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'tag',
                    $queryBuilder->createNamedParameter($tag, \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();
    }

    /**
     * Returns all the filter options of a given filter with the given tag
     *
     * @param $filterUid
     * @param $tag
     * @return array|mixed[]
     */
    public function findByFilterUidAndTag($filterUid, $tag)
    {
        $options = $this->findByFilterUid($filterUid);
        if (empty($options)) {
            return [];
        }

        foreach ($options as $key => $option) {
            if ($option['tag'] !== $tag) {
                unset($options[$key]);
            }
        }

        return $options;
    }

    /**
     * Creates a filter option record and adds it to the given filter
     *
     * @param int $filterUid
     * @param array $additionalFields
     * @return array
     */
    public function createFilterOptionRecord(int $filterUid, array $additionalFields = [])
    {
        /** @var FilterRepository $filterRepository */
        $filterRepository = GeneralUtility::makeInstance(FilterRepository::class);
        $filter = $filterRepository->findByUid($filterUid);

        $emptyRecord = [
            'pid' => $filter['pid'],
            'crdate' => $GLOBALS['EXEC_TIME'],
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'cruser_id' => isset($GLOBALS['BE_USER']->user['uid']) ? (int)$GLOBALS['BE_USER']->user['uid'] : 0,
            'l10n_diffsource' => ''
        ];
        $additionalFields = array_intersect_key($additionalFields, $this->getTableFields());
        $emptyRecord = array_merge($emptyRecord, $additionalFields);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->tableName);
        $connection->insert(
            $this->tableName,
            $emptyRecord,
            ['l10n_diffsource' => Connection::PARAM_LOB]
        );
        $record = $emptyRecord;
        $record['uid'] = $connection->lastInsertId($this->tableName);

        // add the new filter record to
        $updateFields = [
            'options' => $filter['options'],
        ];
        if (!empty($updateFields['options'])) {
            $updateFields['options'] .= ',';
        }
        $updateFields['options'] .= $record['uid'];
        $filterRepository->update($filterUid, $updateFields);
        return $record;
    }

    /**
     * @param int $filterOptionUid
     * @return Statement|int
     */
    public function deleteFilterOptionRecordByUid($filterOptionUid)
    {
        /** @var FilterRepository $filterRepository */
        $filterRepository = GeneralUtility::makeInstance(FilterRepository::class);
        $filterRepository->removeFilterOptionFromAllFilters($filterOptionUid);

        // delete the record
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->tableName);
        return $queryBuilder
            ->delete($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($filterOptionUid, \PDO::PARAM_INT)
                )
            )
            ->execute();
    }

    /**
     * @param string $tag
     */
    public function deleteFilterOptionRecordsByTag($tag)
    {
        $filterOptions = $this->findByTag($tag);
        if (!empty($filterOptions)) {
            foreach ($filterOptions as $filterOption) {
                $this->deleteFilterOptionRecordByUid($filterOption['uid']);
            }
        }
    }


    /**
     * Gets the fields that are available in the table
     *
     * @return array
     */
    protected function getTableFields(): array
    {
        if (empty($this->tableFields)) {
            $this->tableFields = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($this->tableName)
                ->getSchemaManager()
                ->listTableColumns($this->tableName);
        }
        return $this->tableFields;
    }
}
