<?php
namespace TeaminmediasPluswerk\KeSearch\Hooks;

use TeaminmediasPluswerk\KeSearch\Domain\Repository\FilterOptionRepository;
use TeaminmediasPluswerk\KeSearch\Domain\Repository\FilterRepository;
use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
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
class FilterOptionHook
{
    /**
     * Create and update ke_search filter options tied to system categories
     *
     * @param string $status status
     * @param string $table table name
     * @param int $recordUid id of the record
     * @param array $fields fieldArray
     * @param DataHandler $parentObject parent Object
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        $table,
        $recordUid,
        array $fields,
        DataHandler $parentObject
    ) {
        if ($table === 'sys_category') {
            $recordUid =
                isset($parentObject->substNEWwithIDs[$recordUid])
                    ? $parentObject->substNEWwithIDs[$recordUid]
                    : $recordUid;
            $this->updateFilterOptionsForCategory($recordUid);
        }
    }

    /**
     * Deletes matching filter options when a category is deleted
     *
     * @param $command
     * @param $table
     * @param $id
     * @param $value
     * @param $pObj
     */
    function processCmdmap_postProcess ($command,$table,$id,$value,$pObj) {
        if ($table === 'sys_category' && $command === 'delete') {
            /** @var FilterOptionRepository $filterOptionRepository */
            $filterOptionRepository = GeneralUtility::makeInstance(FilterOptionRepository::class);
            $filterOptionRepository->deleteFilterOptionRecordsByTag(
                SearchHelper::createTagnameFromSystemCategoryUid($id)
            );
        }
    }

    /**
     * Creates and updates filter options connected with the given category
     * removes all filter options with the matching tag in filters which are not connected to the category
     *
     * @param $categoryUid
     */
    public function updateFilterOptionsForCategory($categoryUid)
    {
        /** @var FilterOptionRepository $filterOptionRepository */
        $filterOptionRepository = GeneralUtility::makeInstance(FilterOptionRepository::class);
        /** @var FilterRepository $filterRepository */
        $filterRepository = GeneralUtility::makeInstance(FilterRepository::class);

        $category = $this->getCategoryData($categoryUid);
        $tag = SearchHelper::createTagnameFromSystemCategoryUid($categoryUid);

        // add filter options to selected filters
        if (isset($category['tx_kesearch_filter']) && !$category['tx_kesearch_filter'] == '0') {
            $filters = explode(',', $category['tx_kesearch_filter']);
            foreach ($filters as $filterUid) {
                $filterOptions = $filterOptionRepository->findByFilterUidAndTag($filterUid, $tag);
                if (empty($filterOptions)) {
                    $filterOption = [
                        'title' => $category['title'],
                        'tag' => $tag,
                    ];
                    $filterOptionRepository->createFilterOptionRecord($filterUid, $filterOption);
                }
            }
        }

        // Remove all matching filter options from other filters
        $filterOptions = $filterOptionRepository->findByTag($tag);
        if (!empty($filterOptions)) {
            foreach ($filterOptions as $filterOption) {
                $allFilters = $filterRepository->findByAssignedFilterOption($filterOption['uid']);
                foreach ($allFilters as $filter) {
                    if (!GeneralUtility::inList($category['tx_kesearch_filter'], $filter['uid'])) {
                        $filterRepository->removeFilterOptionFromFilter(
                            $filterOption['uid'],
                            $filter['uid']
                        );
                    }
                }
            }
        }
    }


    /**
     * @param $categoryUid
     * @return mixed
     */
    public function getCategoryData($categoryUid)
    {
        // Get category data
        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_category');
        return $queryBuilder
            ->select('*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($categoryUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
    }
}
