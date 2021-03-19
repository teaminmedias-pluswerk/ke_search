<?php declare(strict_types=1);

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

namespace TeaminmediasPluswerk\KeSearch\Widgets;

use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

class StatusWidget implements WidgetInterface
{
    /**
     * @var Registry
     */
    public $registry;

    /**
     * @var WidgetConfigurationInterface
     */
    private $configuration;

    /**
     * @var StandaloneView
     */
    private $view;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        StandaloneView $view
    ) {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->registry = GeneralUtility::makeInstance(Registry::class);
    }

    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('Widget/StatusWidget');
        $indexerStartTime = SearchHelper::getIndexerStartTime();
        $indexerRunningTime = $indexerStartTime ? (time() - $indexerStartTime) : 0;
        $indexerRunningTimeHMS =
            $indexerRunningTime ?
                [
                    's' => round($indexerRunningTime / 3600),
                    'm' => $indexerRunningTime / 60 % 60,
                    's' => $indexerRunningTime % 60
                ]
                : [];
        $this->view->assignMultiple([
            'configuration' => $this->configuration,
            'indexerStartTime' => $indexerStartTime,
            'indexerRunningTime' => $indexerRunningTime,
            'indexerRunningTimeHMS' => $indexerRunningTimeHMS,
        ]);

        $lastRun = $this->registry->get('tx_kesearch', 'lastRun');
        if (!empty($lastRun)) {
            $lastRunIndexingTimeHMS =
                $lastRun['indexingTime'] ?
                [
                    's' => round($lastRun['indexingTime'] / 3600),
                    'm' => $lastRun['indexingTime'] / 60 % 60,
                    's' => $lastRun['indexingTime'] % 60
                ]
                : [];
            $this->view->assignMultiple([
                'lastRunStartTime' => $lastRun['startTime'],
                'lastRunEndTime' => $lastRun['endTime'],
                'lastRunIndexingTime' => $lastRun['indexingTime'],
                'lastRunIndexingTimeHMS' => $lastRunIndexingTimeHMS,
            ]);
        }

        return $this->view->render();
    }
}
