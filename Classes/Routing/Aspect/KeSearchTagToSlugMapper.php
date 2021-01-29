<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TeaminmediasPluswerk\KeSearch\Routing\Aspect;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class KeSearchTagToSlugMapper implements StaticMappableAspectInterface
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function generate(string $value): ?string
    {
        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        $languageId =$context->getPropertyFromAspect('language', 'id');

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_filteroptions');
        $result = $queryBuilder
            ->select('slug')
            ->from('tx_kesearch_filteroptions')
            ->where(
                $queryBuilder->expr()->eq('tag', $queryBuilder->createNamedParameter($value)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId))
            )
            ->execute()
            ->fetch();
        if ($result) {
            return $result['slug'];
        }
        return $value;
    }

    public function resolve(string $value): ?string
    {
        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        $languageId =$context->getPropertyFromAspect('language', 'id');

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_kesearch_filteroptions');
        $result = $queryBuilder
            ->select('tag')
            ->from('tx_kesearch_filteroptions')
            ->where(
                $queryBuilder->expr()->eq('slug', $queryBuilder->createNamedParameter($value)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId))
            )
            ->execute()
            ->fetch();
        if ($result) {
            return $result['tag'];
        }
        return $value;
    }
}
