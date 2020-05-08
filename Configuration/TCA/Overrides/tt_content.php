<?php
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'ke_search',
    'Configuration/TypoScript',
    'Faceted Search'
);

// show FlexForm field in plugin configuration
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['ke_search_pi1'] = 'pi_flexform';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['ke_search_pi2'] = 'pi_flexform';

// remove the old "plugin mode" configuration field
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['ke_search_pi1'] = 'select_key,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['ke_search_pi2'] = 'select_key,recursive,pages';

// add plugins
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array(
        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tt_content.list_type_pi1',
        'ke_search_pi1'
    ),
    'list_type',
    'ke_search'
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    array(
        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tt_content.list_type_pi2',
        'ke_search_pi2'
    ),
    'list_type',
    'ke_search'
);

// Configure FlexForm field
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'ke_search_pi1',
    'FILE:EXT:ke_search/Configuration/FlexForms/flexform_searchbox.xml'
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'ke_search_pi2',
    'FILE:EXT:ke_search/Configuration/FlexForms/flexform_resultlist.xml'
);
