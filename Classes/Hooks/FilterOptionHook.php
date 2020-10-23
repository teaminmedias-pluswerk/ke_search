<?php
declare(strict_types=1);
namespace TeaminmediasPluswerk\KeSearch\Hooks;

use TeaminmediasPluswerk\KeSearch\Domain\Repository\CategoryRepository;
use TeaminmediasPluswerk\KeSearch\Domain\Repository\FilterOptionRepository;
use TeaminmediasPluswerk\KeSearch\Domain\Repository\FilterRepository;
use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
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
     * @param $status
     * @param string $table table name
     * @param $recordUid id of the record
     * @param array $fields fieldArray
     * @param DataHandler $parentObject parent Object
     */
    public function processDatamap_afterDatabaseOperations(
        $status,
        string $table,
        $recordUid,
        array $fields,
        DataHandler $parentObject
    ) {
        if ($table === 'sys_category') {
            $recordUid =
                isset($parentObject->substNEWwithIDs[$recordUid])
                    ? $parentObject->substNEWwithIDs[$recordUid]
                    : $recordUid;
            // Create and update always if a category is edited
            $this->updateFilterOptionsForCategoryAndSubCategories($recordUid);
            // Cleanup (delete) filter options only only if something changed regarding the assigned filters
            if (isset($fields['tx_kesearch_filter']) || isset($fields['tx_kesearch_filtersubcat'])) {
                $this->cleanupFilterOptions($recordUid);
            }
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
            $filterOptionRepository->deleteByTag(
                SearchHelper::createTagnameFromSystemCategoryUid($id)
            );
        }
    }

    /**
     * @param int $categoryUid
     */
    public function updateFilterOptionsForCategoryAndSubCategories(int $categoryUid)
    {
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);
        $category = $categoryRepository->findOneByUid($categoryUid);

        if ($category['tx_kesearch_filter']) {
            $filters = GeneralUtility::trimExplode(',', $category['tx_kesearch_filter']);
            $this->createOrUpdateFilterOptions($filters, $category);
        }
        if ($category['tx_kesearch_filtersubcat']) {
            $filters = GeneralUtility::trimExplode(',', $category['tx_kesearch_filtersubcat']);
            $subCats = $categoryRepository->findAllSubcategoriesByParentUid($categoryUid);
            if ($subCats) {
                foreach ($subCats as $subCat) {
                    $this->createOrUpdateFilterOptions($filters, $subCat);
               }
            }
        }
        if ($category['parent']) {
            $parentCat = $categoryRepository->findOneByUid($category['parent']);
            if ($parentCat['tx_kesearch_filtersubcat']) {
                $filters = GeneralUtility::trimExplode(',', $parentCat['tx_kesearch_filtersubcat']);
                $this->createOrUpdateFilterOptions($filters, $category);
            }
        }
    }

    /**
     * Deletes orphaned filter options.
     * Those will arise when the connection between a category and a filter  will be removed.
     */
    public function cleanupFilterOptions()
    {
        /** @var FilterOptionRepository $filterOptionRepository */
        $filterOptionRepository = GeneralUtility::makeInstance(FilterOptionRepository::class);
        /** @var FilterRepository $filterRepository */
        $filterRepository = GeneralUtility::makeInstance(FilterRepository::class);
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);

        // get all filter options in default language which are connected to system categories
        $filterOptions = $filterOptionRepository->findByTagPrefixAndLanguage(SearchHelper::$systemCategoryPrefix, 0);
        if ($filterOptions) {
            foreach ($filterOptions as $filterOption) {
                $filterIsConnectedToCategory = false;
                // get the category connected to this filter option
                $category = $categoryRepository->findByTag($filterOption['tag']);
                // get the filter this filter option is assigned to
                $filter = $filterRepository->findByAssignedFilterOption($filterOption['uid']);
                if ($filter) {
                    // Check if this category has this filter assigned in field "tx_kesearch_filter"
                    if (GeneralUtility::inList($category['tx_kesearch_filter'], $filter['uid'])) {
                        $filterIsConnectedToCategory = true;
                    }
                    // Check if parent category has this filter assigned in field "tx_kesearch_filtersubcat"
                    if ($category['parent']) {
                        $parentCat = $categoryRepository->findOneByUid($category['parent']);
                        if ($parentCat['tx_kesearch_filtersubcat']) {
                            if (GeneralUtility::inList($parentCat['tx_kesearch_filtersubcat'], $filter['uid'])) {
                                $filterIsConnectedToCategory = true;
                            }
                        }
                    }
                }
                if (!$filterIsConnectedToCategory) {
                    // deleting by tag will also delete localized tags
                    $filterOptionRepository->deleteByTag($filterOption['tag']);
                }
            }
        }
    }

    /**
     * Creates/updates filter options for given filters from the given category data
     * removes all filter options with the matching tag in filters which are not connected to the category
     *
     * @param array $filters list of filter UIDs
     * @param array $category
     */
    public function createOrUpdateFilterOptions(array $filters, array $category)
    {
        /** @var FilterOptionRepository $filterOptionRepository */
        $filterOptionRepository = GeneralUtility::makeInstance(FilterOptionRepository::class);
        /** @var FilterRepository $filterRepository */
        $filterRepository = GeneralUtility::makeInstance(FilterRepository::class);
        /** @var CategoryRepository $categoryRepository */
        $categoryRepository = GeneralUtility::makeInstance(CategoryRepository::class);

        // If this category record is in default language, we need to create/update the matching
        // filter option records. If it is in a different language, we need to create/update the
        // localized filter option.

        if (in_array($category['sys_language_uid'], [0,-1])) {
            $tag = SearchHelper::createTagnameFromSystemCategoryUid($category['uid']);
            foreach ($filters as $filterUid) {
                $filterOptions = $filterOptionRepository->findByFilterUidAndTag($filterUid, $tag);
                if (empty($filterOptions)) {
                    // create
                    $filterOptionRepository->create(
                        (int)$filterUid,
                        ['title' => $category['title'], 'tag' => $tag]
                    );
                } else {
                    // update
                    foreach ($filterOptions as $filterOption){
                        $filterOptionRepository->update(
                            $filterOption['uid'],
                            ['title' => $category['title']]
                        );
                    }
                }
            }
        } else {
            if ($category['l10n_parent']) {
                $l10nParentCategory = $categoryRepository->findOneByUid($category['l10n_parent']);
                if ($l10nParentCategory) {
                    $origTag = SearchHelper::createTagnameFromSystemCategoryUid($l10nParentCategory['uid']);
                    $origFilterOptions = $filterOptionRepository->findByTagAndLanguage($origTag, 0);
                    if (!empty($origFilterOptions)) {
                        foreach ($origFilterOptions as $origFilterOption) {
                            $localizedFilterOptions = $filterOptionRepository->findByL10nParent($origFilterOption['uid']);
                            if (!$localizedFilterOptions) {
                                // create
                                $localizedFilterOption = [
                                    'pid' => $origFilterOption['pid'],
                                    'title' => $category['title'],
                                    'tag' => $origTag,
                                    'sys_language_uid' => $category['sys_language_uid'],
                                    'l10n_parent' => $origFilterOption['uid'],
                                ];
                                foreach ($filters as $origFilter) {
                                    $localizedFilter = $filterRepository->findByL10nParent((int)$origFilter);
                                    $filterOptionRepository->create(
                                        (int)$localizedFilter['uid'],
                                        $localizedFilterOption
                                    );
                                }
                            } else {
                                // update
                                foreach ($localizedFilterOptions as $localizedFilterOption){
                                    $filterOptionRepository->update(
                                        (int)$localizedFilterOption['uid'],
                                        ['title' => $category['title']]
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}