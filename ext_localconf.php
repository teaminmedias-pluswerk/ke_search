<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTKEY = 'ke_search';

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

// logging
$extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('ke_search');
$loglevel = !empty($extConf['loglevel']) ? $extConf['loglevel'] : 'ERROR';
$GLOBALS['TYPO3_CONF_VARS']['LOG']['TeaminmediasPluswerk']['KeSearch']['Indexer']['writerConfiguration'] = array(
    \TYPO3\CMS\Core\Log\LogLevel::normalizeLevel($loglevel) => array(
        'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => array(
            'logFileInfix' => 'kesearch'
        )
    )
);
