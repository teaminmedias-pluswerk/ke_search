<?php
namespace TeaminmediasPluswerk\KeSearch\Command;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Armin Vieweg <armin.vieweg@pluswerk.ag>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FacetRelationCommandController
 */
class KeSearchCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController
{
    /**
     * @var array
     */
    protected $_tagCache = array();

    /**
     * Creates MM relations from tx_kesearch_index tags field.
     *
     * @param bool $truncateRelationsBefore If true (default) the mm table is truncated before
     * @param int $stackSize
     * @return string
     */
    public function updateFacetRelationsCommand($truncateRelationsBefore = true, $stackSize = 1000)
    {
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection $db */
        $db = $GLOBALS['TYPO3_DB'];

        if ($truncateRelationsBefore) {
            $db->exec_TRUNCATEquery('tx_kesearch_index_filteroptions_mm');
        }

        /** @var \tx_kesearch_indexer $indexer */
        $indexer = GeneralUtility::makeInstance('tx_kesearch_indexer');

        $amount = reset($db->exec_SELECTgetRows('count(*) as amount', 'tx_kesearch_index', '1=1'));
        $amount = (int) $amount['amount'];

        $stackAmount = ceil($amount / $stackSize);
        echo "Processing $amount index rows within $stackAmount stacks..." . PHP_EOL;

        for ($i = 0; $i < $stackAmount; $i++) {
            $res = $db->exec_SELECTquery(
                '*',
                'tx_kesearch_index',
                '1=1',
                '',
                'uid asc',
                ($i * $stackSize) . ', ' . $stackSize
            );

            while ($indexRow = $db->sql_fetch_assoc($res)) {
                $indexer->createFilterOptionRelations($indexRow);
            }

            $db->sql_free_result($res);
            echo "- $i / $stackAmount (" . ceil($i / $stackAmount * 100) . "%)" . PHP_EOL;
        } // eo for
    }


    /**
     * Find duplicate tags in filter options. Run before update facet relations command!
     *
     * When $force is true, duplicates getting merged together. Also comma separated relations in filter are updated.
     * And, when case issues appeared, also the index column "tags" is getting updated.
     *
     * @param bool $force
     * @return string
     */
    public function findDuplicateFilterOptionTagsCommand($force = false)
    {
        // Check for duplicates
        /** @var \TYPO3\CMS\Core\Database\DatabaseConnection $db */
        $db = $GLOBALS['TYPO3_DB'];
        $res = $db->sql_query('
            SELECT tx_kesearch_filteroptions.*
            FROM `tx_kesearch_filteroptions`
            INNER JOIN (SELECT tag
                        FROM tx_kesearch_filteroptions
                        GROUP BY tag
                        HAVING COUNT(uid) > 1) dup
                        ON tx_kesearch_filteroptions.tag = dup.tag
            WHERE deleted = 0 AND hidden = 0
            ORDER BY tx_kesearch_filteroptions.tag asc
        ');

        $filterOptions = array();
        while ($row = $db->sql_fetch_assoc($res)) {
            if (!is_array($filterOptions[$row['tag']])) {
                $filterOptions[$row['tag']] = array();
            } else {
                $continue = false;
                foreach ($filterOptions[strtolower($row['tag'])] as $filterOption) {
                    if ($filterOption['pid'] != $row['pid']) {
                        $continue = true;
                        break;
                    }
                }
                if ($continue) {
                    continue; // while
                }
            }
            $filterOptions[strtolower($row['tag'])][] = $row;
        }

        $unsetTags = array();
        foreach ($filterOptions as $tag => $filterOptionRows) {
            if (count($filterOptionRows) < 2) {
                $unsetTags[] = $tag;
            }
        }
        foreach ($unsetTags as $unsetTag) {
            unset($filterOptions[$unsetTag]);
        }


        if (count($filterOptions) === 0) {
            echo 'No duplicates found.' . PHP_EOL;
            return;
        }

        // identify case issues
        $caseIssueTags = array();
        foreach ($filterOptions as $tag => $rows) {
            $firstRow = array_shift($rows);
            $tagName = $firstRow['tag'];
            foreach ($rows as $row) {
                if ($row['tag'] !== $tagName) {
                    $caseIssueTags[] = $row['tag'];
                }
            }
        }

        echo 'Found ' . count($filterOptions) . ' duplicate tags';
        if (count($caseIssueTags) > 0) {
            echo ' and ' . count($caseIssueTags) . ' case issues.' . PHP_EOL;
        } else {
            echo '.' . PHP_EOL;
        }
        echo PHP_EOL;

        $i = 1;
        foreach ($filterOptions as $tag => $rows) {
            echo str_pad($i++, strlen(count($filterOptions)), '0', STR_PAD_LEFT) . '. tag "' . $tag . '":' . PHP_EOL;
            $lastRow = null;
            foreach ($rows as $row) {
                $caseIssueLabel = '';
                if ($lastRow && $lastRow['tag'] !== $row['tag']) {
                    $caseIssueLabel = ' <- case issue';
                }
                echo str_repeat(' ', strlen(count($filterOptions))) . '  -> uid: ' . $row['uid'] . ' (pid: ' . $row['pid'] . ', title: "' . $row['title'] . '", tag: "' . $row['tag'] . '")' . $caseIssueLabel . PHP_EOL;
                $lastRow = $row;
            }
        }
        echo PHP_EOL;
        if (!$force) {
            echo 'To merge duplicate filter options together, append --force to this command.' . PHP_EOL;
            return;
        }

        // Continue when --force is given
        echo 'Merging duplicates...' . PHP_EOL;

        // Rename duplicates
        foreach ($filterOptions as $tag => $filterOptionRows) {
            $baseFilterOption = array_shift($filterOptionRows);
            foreach ($filterOptionRows as $duplicateFilterOptionRow) {
                // Find index entries which references to this duplicate and update tag
                if ($duplicateFilterOptionRow['tag'] !== $baseFilterOption['tag']) {
                    echo "Update case issue..." . PHP_EOL;
                    $affectedIndexRows = $db->exec_SELECTgetRows(
                        'uid,tags',
                        'tx_kesearch_index',
                        'FIND_IN_SET("_' . $duplicateFilterOptionRow['tag'] . '_", tags)'
                    );
                    foreach ($affectedIndexRows as $affectedIndexRow) {
                        $tags = GeneralUtility::trimExplode(',', $affectedIndexRow['tags'], true);
                        foreach ($tags as $index => $tag) {
                            if (trim($tag, ' _') === $duplicateFilterOptionRow['tag']) {
                                $tags[$index] = $baseFilterOption['tag'];
                                break;
                            }
                        }
                        if ($affectedIndexRow['tags'] !== implode(',', $tags)) {
                            $db->exec_UPDATEquery(
                                'tx_kesearch_index',
                                'uid = ' . $affectedIndexRow['uid'],
                                array(
                                    'tags' => implode(',', $tags)
                                )
                            );
                            echo '"Updated tag "' . $duplicateFilterOptionRow['tag'] . '" in index row with uid "' . $affectedIndexRow['uid'] . PHP_EOL;
                        }
                    }
                }

                // Find affected filters and update its options
                $affectedFilters = $db->exec_SELECTgetRows(
                    'uid,options',
                    'tx_kesearch_filters',
                    'FIND_IN_SET("' . $duplicateFilterOptionRow['uid'] . '", options)'
                );

                foreach ($affectedFilters as $affectedFilter) {
                    $optionsArray = GeneralUtility::trimExplode(',', $affectedFilter['options'], true);
                    $newOptionsArray = array();
                    foreach ($optionsArray as $optionUid) {
                        if ($optionUid == $duplicateFilterOptionRow['uid']) {
                            $newOptionsArray[] = $baseFilterOption['uid'];
                        } else {
                            $newOptionsArray[] = $optionUid;
                        }
                    }

                    // Update option in affected filter
                    $db->exec_UPDATEquery(
                        'tx_kesearch_filters',
                        'uid=' . $affectedFilter['uid'],
                        array(
                            'options' => implode(',', $newOptionsArray)
                        )
                    );
                    echo "Updated options in affected filter with uid " . $affectedFilter['uid'] . PHP_EOL;
                }

                $db->exec_DELETEquery(
                    'tx_kesearch_filteroptions',
                    'uid=' . $duplicateFilterOptionRow['uid']
                );
                echo "Deleted duplicate filter option with uid " . $duplicateFilterOptionRow['uid'] . PHP_EOL;
            }
        }
    }
}
