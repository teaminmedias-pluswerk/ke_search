<?php
namespace TeaminmediasPluswerk\KeSearch\Domain\Repository;

use Doctrine\DBAL\FetchMode;
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
class GenericRepository {


    /**
     * Tries to find a table matching the type, either by checking hardcoded values or if the type is the same
     * as the table name.
     * Returns the record with the given uid.
     *
     * @param int|string $uid
     * @param string $type
     * @return false|mixed
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findByUidAndType($uid, string $type)
    {
        $uid = intval($uid);
        if ($uid<=0) return false;

        $row = false;
        $tableName = '';
        switch ($type) {
            case 'page':
                $tableName = 'pages';
                break;
            case 'news':
                $tableName = 'tx_news_domain_model_news';
                break;
            default:
                // check if a table exists that matches the type name
                $tableNameToCheck = strip_tags(htmlentities($type));
                /** @var ConnectionPool $connectionPool */
                $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                $connection = $connectionPool->getConnectionForTable($tableNameToCheck);
                $statement = $connection->prepare('SHOW TABLES LIKE "' . $tableNameToCheck . '"');
                $statement->execute();
                if ($statement->fetch(FetchMode::ASSOCIATIVE)) {
                    $tableName = $tableNameToCheck;
                }
        }
        // hook to add a custom types
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['GenericRepositoryTablename'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['GenericRepositoryTablename'] as $_classRef) {
                $_procObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($_classRef);
                $tableName = $_procObj->getTableName($type);
            }
        }
        if (!empty($tableName)) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($tableName);
            $row =  $queryBuilder
                ->select('*')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();
        }
        return $row;
    }
}
