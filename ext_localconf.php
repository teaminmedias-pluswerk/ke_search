<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

// register cli-script
if (TYPO3_MODE == 'BE') {
    $TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array(
        'EXT:' . $_EXTKEY . '/cli/class.cli_kesearch.php',
        '_CLI_kesearch'
    );
}

// add Searchbox Plugin, override class name with namespace
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, '', '_pi1');
$overrideSetup = 'plugin.tx_kesearch_pi1.userFunc = TeaminmediasPluswerk\KeSearch\Plugins\SearchboxPlugin->main';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('tx_kesearch', 'setup', $overrideSetup);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, '', '_pi2');
$overrideSetup = 'plugin.tx_kesearch_pi2.userFunc = TeaminmediasPluswerk\KeSearch\Plugins\ResultlistPlugin->main';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript('tx_kesearch', 'setup', $overrideSetup);

// add page TSconfig (Content element wizard icons, hide index table)
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ke_search/Configuration/TSconfig/Page/pageTSconfig.txt">'
);

// use hooks for generation of sortdate values
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerAdditionalFields'][] =
    TeaminmediasPluswerk\KeSearch\Hooks\AdditionalFields::class;

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPagesIndexEntry'][] =
    TeaminmediasPluswerk\KeSearch\Hooks\AdditionalFields::class;

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentIndexEntry'][] =
    TeaminmediasPluswerk\KeSearch\Hooks\AdditionalFields::class;

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyTemplaVoilaIndexEntry'][] =
    TeaminmediasPluswerk\KeSearch\Hooks\AdditionalFields::class;

// add scheduler task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TeaminmediasPluswerk\KeSearch\Scheduler\IndexerTask::class] = array(
    'extension' => $_EXTKEY,
    'title' => 'Indexing process for ke_search',
    'description' => 'This task updates the ke_search index'
);

// Custom validators for TCA (eval)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']
['TeaminmediasPluswerk\\KeSearch\\UserFunction\\CustomFieldValidation\\FilterOptionTagValidator'] =
    'EXT:ke_search/Classes/UserFunction/CustomFieldValidation/FilterOptionTagValidator.php';
