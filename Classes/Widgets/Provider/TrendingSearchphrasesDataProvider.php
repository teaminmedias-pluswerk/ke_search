<?php declare(strict_types=1);

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

namespace TeaminmediasPluswerk\KeSearch\Widgets\Provider;

use TeaminmediasPluswerk\KeSearch\Domain\Repository\SearchPhraseStatisticsRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\ListDataProviderInterface;

class TrendingSearchphrasesDataProvider implements ListDataProviderInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @inheritDoc
     */
    public function getItems(): array
    {
        /** @var SearchPhraseStatisticsRepository $searchPhraseRepository */
        $searchPhraseRepository = GeneralUtility::makeInstance(SearchPhraseStatisticsRepository::class);
        $searchPhrases = $searchPhraseRepository->findAllByNumberOfDays();

        $data = [];
        $count = 0;
        if (!empty($searchPhrases)) {
            foreach ($searchPhrases as $searchPhrase) {
                $count++;
                $data[] = [
                    'count' => $count,
                    'searchphrase' => $searchPhrase['searchphrase'],
                    'num' => $searchPhrase['num'],
                    'language' => $searchPhrase['language'],
                ];
            }
        }
        return $data;
    }

}