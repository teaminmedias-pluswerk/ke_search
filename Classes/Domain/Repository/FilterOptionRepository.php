<?php
namespace TeaminmediasPluswerk\KeSearch\Domain\Repository;

use Doctrine\DBAL\Driver\Statement;
use PDO;
use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
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

    /**
     * @var string
     */
    protected $parentTableName = 'tx_kesearch_filters';

    /**
     * @return mixed
     */
    public function findAll()
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->tableName);
        return $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->execute()
            ->fetchAll();
    }

    /**
     * @param string $tagPrefix
     * @return mixed[]
     */
    public function findByTagPrefix(string $tagPrefix)
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->tableName);
        return $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->like(
                    'tag',
                    $queryBuilder->createNamedParameter($tagPrefix . '%', PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();
    }

    /**
     * @param string $tagPrefix
     * @param int $sys_language_uid
     * @return mixed[]
     */
    public function findByTagPrefixAndLanguage(string $tagPrefix, int $sys_language_uid)
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->tableName);
        return $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->like(
                    'tag',
                    $queryBuilder->createNamedParameter($tagPrefix . '%', PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sys_language_uid, PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
    }

    /**
     * Returns all filter options for a given filter uid.
     *
     * @param $filterUid
     * @return array|mixed[]
     */
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

    /**
     * @param int $l10n_parent
     * @return mixed[]
     */
    public function findByL10nParent(int $l10n_parent)
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->tableName);
        return $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($l10n_parent, PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();
    }

    /**
     * @param string $tag
     * @return mixed[]
     */
    public function findByTag(string $tag)
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
                    $queryBuilder->createNamedParameter($tag, PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchAll();
    }

    /**
     * @param string $tag
     * @param int $sys_language_uid
     * @return mixed[]
     */
    public function findByTagAndLanguage(string $tag, int $sys_language_uid)
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
                    $queryBuilder->createNamedParameter($tag, PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($sys_language_uid, PDO::PARAM_INT)
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
    public function create(int $filterUid, array $additionalFields = [])
    {
        /** @var FilterRepository $filterRepository */
        $filterRepository = GeneralUtility::makeInstance(FilterRepository::class);
        $filter = $filterRepository->findByUid($filterUid);

        $newRecord = [
            'pid' => $filter['pid'],
            'crdate' => $GLOBALS['EXEC_TIME'],
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'cruser_id' => isset($GLOBALS['BE_USER']->user['uid']) ? (int)$GLOBALS['BE_USER']->user['uid'] : 0,
            'l10n_diffsource' => ''
        ];
        $additionalFields = array_intersect_key($additionalFields, $this->getTableFields());
        $newRecord = array_merge($newRecord, $additionalFields);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->tableName);
        $connection->insert(
            $this->tableName,
            $newRecord,
            ['l10n_diffsource' => Connection::PARAM_LOB]
        );
        $record = $newRecord;
        $record['uid'] = $connection->lastInsertId($this->tableName);

        // Create slug
        $this->update($record['uid'], ['slug'=> SearchHelper::createFilterOptionSlug($record)]);

        // add the new filter option to the filter
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
     * Removes the filter option with the given uid from all filters and deletes the record.
     *
     * @param int $filterOptionUid
     * @return Statement|int
     */
    public function deleteByUid(int $filterOptionUid)
    {
        /** @var FilterRepository $filterRepository */
        $filterRepository = GeneralUtility::makeInstance(FilterRepository::class);
        $filterRepository->removeFilterOptionFromFilter($filterOptionUid);

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->tableName);
        return $queryBuilder
            ->delete($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($filterOptionUid, PDO::PARAM_INT)
                )
            )
            ->execute();
    }

    /**
     * Removes the filter options with the given tag from all filters and deletes the record.
     *
     * @param string $tag
     */
    public function deleteByTag(string $tag)
    {
        $filterOptions = $this->findByTag($tag);
        if (!empty($filterOptions)) {
            foreach ($filterOptions as $filterOption) {
                $this->deleteByUid($filterOption['uid']);
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

    /**
     * @param int $uid
     * @param array $updateFields
     * @return mixed
     */
    public function update(int $uid, array $updateFields)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);
        $queryBuilder
            ->update($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, PDO::PARAM_INT)
                )
            );
        foreach ($updateFields as $key => $value) {
            $queryBuilder->set($key, $value);
        }
        return $queryBuilder->execute();
    }
}