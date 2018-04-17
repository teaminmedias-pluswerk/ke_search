<?php
defined('TYPO3_MODE') || die();

// add tag field to pages
$tempColumns = [
    'tx_kesearch_tags' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:ke_search/locallang_db.xml:pages.tx_kesearch_tags',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingleBox',
            'size' => 10,
            'minitems' => 0,
            'maxitems' => 100,
            'items' => [],
            'allowNonIdValues' => true,
            'itemsProcFunc' => 'user_filterlist->getListOfAvailableFiltersForTCA',
        ]
    ],
    'tx_kesearch_abstract' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:ke_search/locallang_db.xml:pages.tx_kesearch_abstract',
        'config' => [
            'type' => 'text',
            'cols' => 40,
            'rows' => 15
        ]
    ],
    'tx_kesearch_resultimage' => [
        'exclude' => true,
        'label' => 'LLL:EXT:ke_search/locallang_db.xml:pages.tx_kesearch_resultimage',
        'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
            'tx_kesearch_resultimage',
            [
                'overrideChildTca' => [
                    'types' => [
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                            'showitem' => '
                                    --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                    --palette--;;filePalette'
                        ]
                    ],
                ],
            ]
        )
    ],
];

// add new fields to tab "search" and move field "no_search" to "search tab"
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;LLL:EXT:ke_search/locallang_db.xml:pages.tx_kesearch_label,no_search;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.no_search_formlabel, tx_kesearch_tags,tx_kesearch_abstract,tx_kesearch_resultimage;;;;1-1-1'
);

// remove field "no_search" from "miscellaneous" palette
$GLOBALS['TCA']['pages']['palettes']['miscellaneous']['showitem'] = 'is_siteroot;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.is_siteroot_formlabel, php_tree_stop;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.php_tree_stop_formlabel';
