<?php
namespace TeaminmediasPluswerk\KeSearch\Domain\Repository;

use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
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
class SearchPhraseStatisticsRepository {
    /**
     * @var string
     */
    protected $tableName = 'tx_kesearch_stat_search';

    /**
     * @param int $uid
     * @return mixed
     */
    public function findByUid(int $uid)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);
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
     * @param integer $uid
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

    /**
     * Returns all search phrases (system wide, ignores in which folder the data is) for the given days.
     * Returns the search phrase itself, the number of times it has been used and the language it has been used in.
     *
     * @param int $days
     * @return array
     */
    public function findAllByNumberOfDays($days = 7): array
    {
        $startTime = time() - $days * 24 * 60 * 60;
        $col = 'searchphrase';
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName);

        $queryBuilder->getRestrictions()->removeAll();

        $statisticData = $queryBuilder
            ->add('select', 'count(' . $col . ') as num, language, ' . $col)
            ->from($this->tableName)
            ->add(
                'where',
                'tstamp > ' . $queryBuilder->quote($startTime, PDO::PARAM_INT)
            )
            ->add('groupBy', $col . ' HAVING count(' . $col . ')>0')
            ->add('orderBy', 'num desc')
            ->execute()
            ->fetchAll();

        return $statisticData;
    }
}