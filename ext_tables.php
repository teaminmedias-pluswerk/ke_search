<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$extPath = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ke_search');
$extRelPath = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('ke_search');

if (TYPO3_MODE == 'BE') {
	require_once($extPath . 'Classes/lib/class.tx_kesearch_lib_items.php');
}

$tempColumns = array(
    'tx_kesearch_tags' => array(
	'exclude' => 0,
	'label' => 'LLL:EXT:ke_search/locallang_db.xml:pages.tx_kesearch_tags',
	'config' => array(
	    'type' => 'select',
		'renderType' => 'selectSingleBox',
	    'size' => 10,
	    'minitems' => 0,
	    'maxitems' => 100,
	    'items' => array(),
	    'allowNonIdValues' => true,
	    'itemsProcFunc' => 'user_filterlist->getListOfAvailableFiltersForTCA',
	)
    ),
);

// help file
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_kesearch_filters', 'EXT:ke_search/locallang_csh.xml');

// Show FlexForm field in plugin configuration
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1'] = 'pi_flexform';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi2'] = 'pi_flexform';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi3'] = 'pi_flexform';

// Configure FlexForm field
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY . '_pi1', 'FILE:EXT:ke_search/pi1/flexform_pi1.xml');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY . '_pi2', 'FILE:EXT:ke_search/pi2/flexform_pi2.xml');
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue($_EXTKEY . '_pi3', 'FILE:EXT:ke_search/pi3/flexform_pi3.xml');

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_kesearch_tags;;;;1-1-1');

if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TeaminmediasPluswerk.' . $_EXTKEY,
		'web',          // Main area
		'backend_module',         // Name of the module
		'',             // Position of the module
		array(          // Allowed controller action combinations
			'BackendModule' => 'startIndexing,indexedContent,indexTableInformation,searchwordStatistics,clearSearchIndex,lastIndexingReport,alert',
		),
		array(          // Additional configuration
			'access'    => 'user,group',
			'icon'      => 'EXT:ke_search/Resources/Public/Icons/moduleicon.gif',
			'labels'    => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml',
		)
	);
}

$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi2'] = 'layout,select_key';
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi3'] = 'layout,select_key';

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array(
	'LLL:EXT:ke_search/locallang_db.xml:tt_content.list_type_pi1',
	$_EXTKEY . '_pi1',
	$extRelPath . 'ext_icon.gif'), 'list_type'
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array(
	'LLL:EXT:ke_search/locallang_db.xml:tt_content.list_type_pi2',
	$_EXTKEY . '_pi2',
	$extRelPath . 'ext_icon.gif'), 'list_type'
);

TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array(
	'LLL:EXT:ke_search/locallang_db.xml:tt_content.list_type_pi3',
	$_EXTKEY . '_pi3',
	$extRelPath . 'ext_icon.gif'), 'list_type'
);

// class for displaying the category tree for tt_news in BE forms.
if (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tt_news')) {
	include_once(TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news') . 'lib/class.tx_ttnews_TCAform_selectTree.php');
}


if (TYPO3_MODE == 'BE') {
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_kesearch_pi1_wizicon'] = $extPath . 'pi1/class.tx_kesearch_pi1_wizicon.php';
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_kesearch_pi2_wizicon'] = $extPath . 'pi2/class.tx_kesearch_pi2_wizicon.php';
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_kesearch_pi3_wizicon'] = $extPath . 'pi3/class.tx_kesearch_pi3_wizicon.php';
}