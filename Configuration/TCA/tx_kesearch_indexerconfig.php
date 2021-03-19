<?php

$langGeneralPath = 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:';

if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) <
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('10.0')
) {
    $langGeneralPath = 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:';
}

return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'iconfile' => 'EXT:ke_search/Resources/Public/Icons/table_icons/icon_tx_kesearch_indexerconfig.gif',
        'searchFields' => 'title'
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden,title,storagepid,startingpoints_recursive,single_pages,sysfolder,'
            . 'type,index_content_with_restrictions,index_news_category_mode,'
            . 'index_news_category_selection,directories,fileext,filteroption,index_page_doctypes'
    ),
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => $langGeneralPath . 'LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'title' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.title',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'type' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.type',
            'onChange' => 'reload',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.type.I.0',
                        'page',
                        'EXT:ke_search/Resources/Public/Icons/types_backend/selicon_tx_kesearch_indexerconfig_type_0.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.type.I.12',
                        'news',
                        'EXT:ke_search/Resources/Public/Icons/types_backend/selicon_tx_kesearch_indexerconfig_type_12.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.type.I.5',
                        'tt_address',
                        'EXT:ke_search/Resources/Public/Icons/types_backend/selicon_tx_kesearch_indexerconfig_type_5.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.type.I.6',
                        'tt_content',
                        'EXT:ke_search/Resources/Public/Icons/types_backend/selicon_tx_kesearch_indexerconfig_type_6.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.type.I.7',
                        'file',
                        'EXT:ke_search/Resources/Public/Icons/types_backend/selicon_tx_kesearch_indexerconfig_type_7.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.type.I.13',
                        'a21glossary',
                        'EXT:ke_search/Resources/Public/Icons/types_backend/selicon_tx_kesearch_indexerconfig_type_13.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.type.I.14',
                        'cal',
                        'EXT:ke_search/Resources/Public/Icons/types_backend/selicon_tx_kesearch_indexerconfig_type_14.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.type.I.2',
                        'tt_news',
                        'EXT:ke_search/Resources/Public/Icons/types_backend/selicon_tx_kesearch_indexerconfig_type_2.gif'
                    ),
                ),
                'itemsProcFunc' => 'TeaminmediasPluswerk\KeSearch\Lib\Items->fillIndexerConfig',
                'size' => 1,
                'maxitems' => 1,
                'default' => 'page',
            )
        ),
        'storagepid' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.storagepid',
            'description' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.storagepid.description',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            )
        ),
        'targetpid' => array(
            'displayCond' => 'FIELD:type:!IN:page,tt_content,file,remote',
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.targetpid',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            )
        ),
        'startingpoints_recursive' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.startingpoints_recursive',
            'description' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.startingpoints_recursive.description',
            'displayCond' => 'FIELD:type:IN:page,tt_content,tt_address,news,a21glossary,cal,tt_news',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 99,
            )
        ),
        'single_pages' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.single_pages',
            'description' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.single_pages.description',
            'displayCond' => 'FIELD:type:IN:page,tt_content',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 99,
            )
        ),
        'sysfolder' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.sysfolder',
            'description' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.sysfolder.description',
            'displayCond' => 'FIELD:type:IN:tt_address,news,a21glossary,cal,tt_news',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 99,
            )
        ),
        'index_content_with_restrictions' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_content_with_restrictions',
            'displayCond' => 'FIELD:type:=:page',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.'
                        . 'index_content_with_restrictions.I.0',
                        'yes'
                    ),
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.'
                        . 'index_content_with_restrictions.I.1',
                        'no'
                    ),
                ),
                'size' => 1,
                'maxitems' => 1,
                'default' => 'no'
            )
        ),
        'index_news_category_mode' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_news_category_mode',
            'displayCond' => 'FIELD:type:IN:news',
            'onChange' => 'reload',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_news_category_mode.I.1',
                        '1'
                    ),
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_news_category_mode.I.2',
                        '2'
                    ),
                ),
                'default' => 1,
                'size' => 1,
                'maxitems' => 1,
            )
        ),
        'index_news_archived' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_news_archived',
            'displayCond' => 'FIELD:type:IN:news',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_news_archived.I.0',
                        '0'
                    ),
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_news_archived.I.1',
                        '1'
                    ),
                    array(
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_news_archived.I.2',
                        '2'
                    ),
                ),
                'size' => 1,
                'maxitems' => 1,
            )
        ),
        'index_extnews_category_selection' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_extnews_category_selection',
            'displayCond' => [
                'AND' => [
                    'FIELD:type:=:news',
                    'FIELD:index_news_category_mode:=:2',
                ]
            ],
            'config' => array(
                'type' => 'none',
                'behaviour' => [
                    'allowLanguageSynchronization' => true
                ]
            )
        ),
        'index_use_page_tags' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_use_page_tags',
            'displayCond' => 'FIELD:type:IN:tt_address,news,tt_news',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'directories' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.directories',
            'description' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.directories.description',
            'displayCond' => 'FIELD:type:IN:file',
            'config' => array(
                'type' => 'text',
                'cols' => 48,
                'rows' => 10,
                'eval' => 'trim',
            )
        ),
        'fileext' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.fileext',
            'displayCond' => 'FIELD:type:IN:file,page,tt_content,news,tt_news',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'default' => 'pdf,ppt,doc,xls,docx,xlsx,pptx'
            )
        ),
        'index_use_page_tags_for_files' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_use_page_tags_for_files',
            'displayCond' => 'FIELD:type:IN:page,tt_content',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'index_page_doctypes' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_page_doctypes',
            'displayCond' => 'FIELD:type:=:page',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'default' => '1,2,5'
            )
        ),
        'filteroption' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.filteroption',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('', 0),
                ),
                'itemsProcFunc' => 'TeaminmediasPluswerk\KeSearch\Backend\Filterlist->getListOfAvailableFiltersForTCA',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            )
        ),
        'fal_storage' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.fal_storage',
            'displayCond' => 'FIELD:type:=:file',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.fal_storage.dont_use_fal', 0),
                ),
                'size' => 1,
                'maxitems' => 1,
                'default' => 0,
                'foreign_table' => 'sys_file_storage',
                'allowNonIdValues' => 1
            )
        ),
        'contenttypes' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.contenttypes',
            'displayCond' => 'FIELD:type:IN:page,tt_content',
            'config' => array(
                'type' => 'text',
                'cols' => 48,
                'rows' => 10,
                'eval' => 'trim',
                'default' => 'text,textmedia,textpic,bullets,table,html,header,uploads'
            )
        ),
        'cal_expired_events' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.cal_expired_events',
            'displayCond' => 'FIELD:type:IN:cal',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'index_news_files_mode' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_news_files_mode',
            'displayCond' => 'FIELD:type:IN:news,tt_news',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_news_files_mode.I.0',
                        '0'
                    ],
                    [
                        'LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_indexerconfig.index_news_files_mode.I.1',
                        '1'
                    ]
                ],
                'size' => 1,
                'maxitems' => 1,
            )
        ]
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden,title,type,storagepid,targetpid,'
            . 'startingpoints_recursive,single_pages,sysfolder,index_content_with_restrictions,'
            . 'index_news_archived,index_news_category_mode,index_extnews_category_selection,'
            . 'index_use_page_tags,fal_storage,directories,fileext,index_page_doctypes,contenttypes,'
            . 'index_news_files_mode,'
            . 'filteroption,index_use_page_tags_for_files,cal_expired_events')
    )
);
