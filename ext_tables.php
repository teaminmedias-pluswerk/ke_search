<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$extPath = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ke_search');

// add help file
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'tx_kesearch_filters',
    'EXT:ke_search/locallang_csh.xml'
);

// Configure FlexForm field
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $_EXTKEY . '_pi1',
    'FILE:EXT:ke_search/pi1/flexform_pi1.xml'
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $_EXTKEY . '_pi2',
    'FILE:EXT:ke_search/pi2/flexform_pi2.xml'
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $_EXTKEY . '_pi3',
    'FILE:EXT:ke_search/pi3/flexform_pi3.xml'
);

// add module
if (TYPO3_MODE == 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'TeaminmediasPluswerk.' . $_EXTKEY,
        'web',          // Main area
        'backend_module',         // Name of the module
        '',             // Position of the module
        array(          // Allowed controller action combinations
            'BackendModule' =>
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

if (TYPO3_MODE == 'BE') {
    $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_kesearch_pi1_wizicon'] =
        $extPath . 'pi1/class.tx_kesearch_pi1_wizicon.php';

    $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_kesearch_pi2_wizicon'] =
        $extPath . 'pi2/class.tx_kesearch_pi2_wizicon.php';

    $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_kesearch_pi3_wizicon'] =
        $extPath . 'pi3/class.tx_kesearch_pi3_wizicon.php';
}

/** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'ext-kesearch-wizard-icon',
    'TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider',
    ['source' => 'EXT:ke_search/Resources/Public/Icons/ce_wiz.gif']
);
