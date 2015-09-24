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
	TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath('web_txkesearchM1', $extPath . 'mod1/');
	TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('web', 'txkesearchM1', '', $extPath . 'mod1/');
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

$GLOBALS['TCA']['tx_kesearch_filters'] = array(
    'ctrl' => array(
	'title' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters',
	'label' => 'title',
	'tstamp' => 'tstamp',
	'crdate' => 'crdate',
	'cruser_id' => 'cruser_id',
	'languageField' => 'sys_language_uid',
	'transOrigPointerField' => 'l10n_parent',
	'transOrigDiffSourceField' => 'l10n_diffsource',
	'default_sortby' => 'ORDER BY crdate',
	'delete' => 'deleted',
	'type' => 'rendertype',
	'enablecolumns' => array(
	    'disabled' => 'hidden',
	),
	'dynamicConfigFile' => $extPath . 'tca.php',
	'iconfile' => $extRelPath . 'res/img/table_icons/icon_tx_kesearch_filters.gif',
	'searchFields' => 'title'
    ),
);

$GLOBALS['TCA']['tx_kesearch_filteroptions'] = array(
    'ctrl' => array(
	'title' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filteroptions',
	'label' => 'title',
	'tstamp' => 'tstamp',
	'crdate' => 'crdate',
	'cruser_id' => 'cruser_id',
	'languageField' => 'sys_language_uid',
	'transOrigPointerField' => 'l10n_parent',
	'transOrigDiffSourceField' => 'l10n_diffsource',
	'sortby' => 'sorting',
	'delete' => 'deleted',
	'enablecolumns' => array(
	    'disabled' => 'hidden',
	),
	'dynamicConfigFile' => $extPath . 'tca.php',
	'iconfile' => $extRelPath . 'res/img/table_icons/icon_tx_kesearch_filteroptions.gif',
	'searchFields' => 'title,tag'
    ),
);

$GLOBALS['TCA']['tx_kesearch_index'] = array(
    'ctrl' => array(
	'title' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_index',
	'label' => 'title',
	'tstamp' => 'tstamp',
	'crdate' => 'crdate',
	'cruser_id' => 'cruser_id',
	'default_sortby' => 'ORDER BY crdate',
	'enablecolumns' => array(
	    'starttime' => 'starttime',
	    'endtime' => 'endtime',
	    'fe_group' => 'fe_group',
	),
	'dynamicConfigFile' => $extPath . 'tca.php',
	'iconfile' => $extRelPath . 'res/img/table_icons/icon_tx_kesearch_index.gif',
    ),
);

// class for displaying the category tree for tt_news in BE forms.
if (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tt_news')) {
	include_once(TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('tt_news') . 'lib/class.tx_ttnews_TCAform_selectTree.php');
}

$GLOBALS['TCA']['tx_kesearch_indexerconfig'] = array(
    'ctrl' => array(
	'title' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig',
	'label' => 'title',
	'tstamp' => 'tstamp',
	'crdate' => 'crdate',
	'cruser_id' => 'cruser_id',
	'default_sortby' => 'ORDER BY crdate',
	'delete' => 'deleted',
	'enablecolumns' => array(
	    'disabled' => 'hidden',
	),
	'dynamicConfigFile' => $extPath . 'tca.php',
	'iconfile' => $extRelPath . 'res/img/table_icons/icon_tx_kesearch_indexerconfig.gif',
	'searchFields' => 'title'
    ),
);

if (TYPO3_MODE == 'BE') {
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_kesearch_pi1_wizicon'] = $extPath . 'pi1/class.tx_kesearch_pi1_wizicon.php';
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_kesearch_pi2_wizicon'] = $extPath . 'pi2/class.tx_kesearch_pi2_wizicon.php';
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_kesearch_pi3_wizicon'] = $extPath . 'pi3/class.tx_kesearch_pi3_wizicon.php';
}