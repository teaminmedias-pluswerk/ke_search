<?php
declare(strict_types=1);
namespace TeaminmediasPluswerk\KeSearch\Domain\Repository;

use PDO;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *  (c) 2021 Christian Bülter
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
class TtAddressRepository {
    /**
     * @var string
     */
    protected $tableName = 'tt_address';

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
     * @param $uid
     * @return mixed
     */
    public function findOneByUid($uid)
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->tableName);
        return $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($uid, PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
    }

    /**
     * @param array $pidList
     * @param int $tstamp
     * @return mixed[]
     */
    public function findAllDeletedByPidListAndTimestampInAllLanguages(array $pidList, int $tstamp)
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->tableName);
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($pidList,Connection::PARAM_INT_ARRAY))
            )
            ->orWhere(
                $queryBuilder->expr()->in('l10n_parent', $queryBuilder->createNamedParameter($pidList,Connection::PARAM_INT_ARRAY))
            )
            ->andWhere(
                $queryBuilder->expr()->eq('deleted', 1),
                $queryBuilder->expr()->gte('tstamp', $queryBuilder->createNamedParameter($tstamp,PDO::PARAM_INT))
            )
            ->execute()
            ->fetchAll();
    }
}
