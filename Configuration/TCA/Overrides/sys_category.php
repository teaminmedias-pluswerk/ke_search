<?php
defined('TYPO3_MODE') || die();

// add field tx_keasearch_filter
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'sys_category',
    [
        'tx_kesearch_filter' => [
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'exclude' => 1,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:sys_category.tx_kesearch_filter',
            'config' => array(
                'default' => 0,
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_kesearch_filters',
                'foreign_table_where' => ' AND tx_kesearch_filters.sys_language_uid IN (-1,0)',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 99,
            )
        ],
        'tx_kesearch_filtersubcat' => [
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'exclude' => 1,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:sys_category.tx_kesearch_filtersubcat',
            'config' => array(
                'default' => 0,
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_kesearch_filters',
                'foreign_table_where' => ' AND tx_kesearch_filters.sys_language_uid IN (-1,0)',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 99,
            )
        ],
    ]
);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'sys_category',
    '--div--;LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:pages.tx_kesearch_label,tx_kesearch_filter,tx_kesearch_filtersubcat'
);
