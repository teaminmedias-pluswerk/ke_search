<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$_EXTKEY = 'ke_search';

// add help file
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'tx_kesearch_filters',
    'EXT:ke_search/locallang_csh.xml'
);

// add module
if (TYPO3_MODE == 'BE') {

    if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >=
        \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('10.0')
    ) {
        $extensionName = 'ke_search';
        $controller = TeaminmediasPluswerk\KeSearch\Controller\BackendModuleController::class;
    } else {
        $extensionName = 'TeaminmediasPluswerk.ke_search';
        $controller = 'BackendModule';
    }

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        $extensionName,
        'web',          // Main area
        'backend_module',         // Name of the module
        '',             // Position of the module
        array(          // Allowed controller action combinations
            $controller =>
                'startIndexing,indexedContent,indexTableInformation,'
                . 'searchwordStatistics,clearSearchIndex,lastIndexingReport,alert',
        ),
        array(          // Additional configuration
            'access' => 'user,group',
            'icon' => 'EXT:ke_search/Resources/Public/Icons/moduleicon.svg',
            'labels' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml',
        )
    );
}

/** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'ext-kesearch-wizard-icon',
    'TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider',
    ['source' => 'EXT:ke_search/Resources/Public/Icons/moduleicon.svg']
);
