<?php
namespace TeaminmediasPluswerk\KeSearch\Domain\Repository;

use PDO;
use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
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
 * @author Christian Bülter
 * @package TYPO3
 * @subpackage ke_search
 */
class CategoryRepository {
    /**
     * @var string
     */
    protected $tableName = 'sys_category';

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
     * @param $categoryUid
     * @return mixed
     */
    public function findOneByUid($categoryUid)
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
                    $queryBuilder->createNamedParameter($categoryUid, PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
    }

    /**
     * @param $categoryUid
     * @return mixed
     */
    public function findAllSubcategoriesByParentUid($categoryUid)
    {
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable($this->tableName);
        return $queryBuilder
            ->select('*')
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'parent',
                    $queryBuilder->createNamedParameter($categoryUid, PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();
    }

    /**
     * @param string $tag
     */
    public function findByTag(string $tag)
    {
        $uid = intval(str_replace(SearchHelper::$systemCategoryPrefix, '', $tag));
        return $this->findOneByUid($uid);
    }
}
