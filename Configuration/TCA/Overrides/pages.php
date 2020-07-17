<?php
defined('TYPO3_MODE') || die();

// add tag field to pages
$tempColumns = [
    'tx_kesearch_tags' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:pages.tx_kesearch_tags',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingleBox',
            'size' => 10,
            'minitems' => 0,
            'maxitems' => 100,
            'items' => [],
            'allowNonIdValues' => true,
            'itemsProcFunc' => 'TeaminmediasPluswerk\KeSearch\Backend\Filterlist->getListOfAvailableFiltersForTCA',
        ]
    ],
    'tx_kesearch_abstract' => [
        'exclude' => 1,
        'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:pages.tx_kesearch_abstract',
        'config' => [
            'type' => 'text',
            'cols' => 40,
            'rows' => 15
        ]
    ],
    'tx_kesearch_resultimage' => [
        'exclude' => true,
        'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:pages.tx_kesearch_resultimage',
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

// add the new fields to tab "search" and include the core field "no_search"
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);
TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:pages.tx_kesearch_label,no_search;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.no_search_formlabel, tx_kesearch_tags,tx_kesearch_abstract,tx_kesearch_resultimage'
);

// remove field "no_search" from "miscellaneous" palette of the "Behaviour" tab
// first use API to replace it with a dummy field
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette(
    'pages',
    'miscellaneous',
    'tx_kesearch_thisfielddoesnotexist',
    'replace:no_search'
);

// then delete the dummy field in order to clean up the TCA
$GLOBALS['TCA']['pages']['palettes']['miscellaneous']['showitem'] =
    str_replace(
        'tx_kesearch_thisfielddoesnotexist,',
        '',
        $GLOBALS['TCA']['pages']['palettes']['miscellaneous']['showitem']
    );