<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

(function () {

    // add Searchbox Plugin, override class name with namespace
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43('ke_search', '', '_pi1');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'tx_kesearch',
        'setup',
        'plugin.tx_kesearch_pi1.userFunc = TeaminmediasPluswerk\KeSearch\Plugins\SearchboxPlugin->main'
    );

    // add Resultlist Plugin, override class name with namespace
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43('ke_search', '', '_pi2');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'tx_kesearch',
        'setup',
        'plugin.tx_kesearch_pi2.userFunc = TeaminmediasPluswerk\KeSearch\Plugins\ResultlistPlugin->main'
    );

    // add page TSconfig (Content element wizard icons, hide index table)
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ke_search/Configuration/TSconfig/Page/pageTSconfig.txt">'
    );

    // use hooks for generation of sortdate values
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerAdditionalFields'][] =
        \TeaminmediasPluswerk\KeSearch\Hooks\AdditionalFields::class;

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPagesIndexEntry'][] =
        \TeaminmediasPluswerk\KeSearch\Hooks\AdditionalFields::class;

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentIndexEntry'][] =
        \TeaminmediasPluswerk\KeSearch\Hooks\AdditionalFields::class;

    // Custom validators for TCA (eval)
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']
    ['TeaminmediasPluswerk\\KeSearch\\UserFunction\\CustomFieldValidation\\FilterOptionTagValidator'] =
        'EXT:ke_search/Classes/UserFunction/CustomFieldValidation/FilterOptionTagValidator.php';

    // logging
    $extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
    )->get('ke_search');
    $loglevel = !empty($extConf['loglevel']) ? $extConf['loglevel'] : 'ERROR';

    if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) <
        \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('10.0')
    ) {
        $loglevel = \TYPO3\CMS\Core\Log\LogLevel::normalizeLevel($loglevel);
    } else {
        $loglevel = strtolower($loglevel);
    }

    $GLOBALS['TYPO3_CONF_VARS']['LOG']['TeaminmediasPluswerk']['KeSearch']['writerConfiguration'] = [
        $loglevel => [
            'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => [
                'logFileInfix' => 'kesearch'
            ]
        ]
    ];

    // register "after save" hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']
    ['ke_search-filter-option'] = \TeaminmediasPluswerk\KeSearch\Hooks\FilterOptionHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']
    ['ke_search-filter-option'] = \TeaminmediasPluswerk\KeSearch\Hooks\FilterOptionHook::class;

    // Upgrade Wizards
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['keSearchMakeTagsAlphanumericUpgradeWizard']
        = \TeaminmediasPluswerk\KeSearch\Updates\MakeTagsAlphanumericUpgradeWizard::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['keSearchPopulateFilterOptionsSlugsUpgradeWizard']
        = \TeaminmediasPluswerk\KeSearch\Updates\PopulateFilterOptionSlugsUpgradeWizard::class;

    // Custom aspects for routing
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['KeSearchUrlEncodeMapper'] =
        \TeaminmediasPluswerk\KeSearch\Routing\Aspect\KeSearchUrlEncodeMapper::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['aspects']['KeSearchTagToSlugMapper'] =
        \TeaminmediasPluswerk\KeSearch\Routing\Aspect\KeSearchTagToSlugMapper::class;
})();