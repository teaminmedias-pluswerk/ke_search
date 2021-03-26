<?php
declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use TeaminmediasPluswerk\KeSearch\Widgets\Provider\IndexOverviewDataProvider;
use TeaminmediasPluswerk\KeSearch\Widgets\Provider\TrendingSearchphrasesDataProvider;
use TeaminmediasPluswerk\KeSearch\Widgets\StatusWidget;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Dashboard\Widgets\BarChartWidget;
use TYPO3\CMS\Dashboard\Widgets\ListWidget;

return function (ContainerConfigurator $configurator, ContainerBuilder $containerBuilder) {
    $services = $configurator->services();

    if (ExtensionManagementUtility::isLoaded('dashboard')) {
        $services->set('dashboard.widget.ke_search_indexer_status')
            ->class(StatusWidget::class)
            ->arg('$view', new Reference('dashboard.views.widget'))
            ->tag(
                'dashboard.widget',
                [
                    'identifier' => 'keSearchStatus',
                    'groupNames' => 'ke_search',
                    'title' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_dashboard.xml:widgets.keSearchStatus.title',
                    'description' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_dashboard.xml:widgets.keSearchStatus.description',
                    'iconIdentifier' => 'ext-kesearch-wizard-icon',
                    'height' => 'small',
                    'width' => 'small'
                ]
            );

        $services->set('dashboard.widget.ke_search_index_overview')
            ->class(BarChartWidget::class)
            ->arg('$dataProvider', new Reference(IndexOverviewDataProvider::class))
            ->arg('$view', new Reference('dashboard.views.widget'))
            ->tag(
                'dashboard.widget',
                [
                    'identifier' => 'keSearchIndexOverview',
                    'groupNames' => 'ke_search',
                    'title' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_dashboard.xml:widgets.keSearchIndexOverview.title',
                    'description' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_dashboard.xml:widgets.keSearchIndexOverview.description',
                    'iconIdentifier' => 'ext-kesearch-wizard-icon',
                    'height' => 'medium',
                    'width' => 'medium'
                ]
            );

        $services->set('dashboard.widget.ke_search_trending_searchphrases')
            ->class(ListWidget::class)
            ->arg('$dataProvider', new Reference(TrendingSearchphrasesDataProvider::class))
            ->arg('$view', new Reference('dashboard.views.widget'))
            ->tag(
                'dashboard.widget',
                [
                    'identifier' => 'keSearchTrendingSearchphrases',
                    'groupNames' => 'ke_search',
                    'title' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_dashboard.xml:widgets.keSearchTrendingSearchphrases.title',
                    'description' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_dashboard.xml:widgets.keSearchTrendingSearchphrases.description',
                    'iconIdentifier' => 'ext-kesearch-wizard-icon',
                    'height' => 'medium',
                    'width' => 'medium'
                ]
            );
    }
};