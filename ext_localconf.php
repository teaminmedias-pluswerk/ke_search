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

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ke_search/pageTSconfig.txt">'
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    $_EXTKEY,
    'pi1/class.tx_kesearch_pi1.php',
    '_pi1',
    'list_type',
    0
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    $_EXTKEY,
    'pi2/class.tx_kesearch_pi2.php',
    '_pi2',
    'list_type',
    0
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
    $_EXTKEY,
    'pi3/class.tx_kesearch_pi3.php',
    '_pi3',
    'list_type',
    0
);

// use hooks for generation of sortdate values
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerAdditionalFields'][] =
    'EXT:ke_search/Classes/hooks/class.user_kesearchhooks.php:user_kesearch_sortdate';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyPagesIndexEntry'][] =
    'EXT:ke_search/Classes/hooks/class.user_kesearchhooks.php:user_kesearch_sortdate';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyYACIndexEntry'][] =
    'EXT:ke_search/Classes/hooks/class.user_kesearchhooks.php:user_kesearch_sortdate';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyContentIndexEntry'][] =
    'EXT:ke_search/Classes/hooks/class.user_kesearchhooks.php:user_kesearch_sortdate';

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyTemplaVoilaIndexEntry'][] =
    'EXT:ke_search/Classes/hooks/class.user_kesearchhooks.php:user_kesearch_sortdate';

// add scheduler task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_kesearch_indexertask'] = array(
    'extension' => $_EXTKEY,
    'title' => 'Indexing process for ke_search',
    'description' => 'This task updates the ke_search index'
);

// Custom validators for TCA (eval)
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']
['TeaminmediasPluswerk\\KeSearch\\UserFunction\\CustomFieldValidation\\FilterOptionTagValidator'] =
    'EXT:ke_search/Classes/UserFunction/CustomFieldValidation/FilterOptionTagValidator.php';
