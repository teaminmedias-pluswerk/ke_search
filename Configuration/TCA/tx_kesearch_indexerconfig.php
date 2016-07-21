<?php

$configurationArray = array(
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
        'iconfile' => 'EXT:ke_search/res/img/table_icons/icon_tx_kesearch_indexerconfig.gif',
        'searchFields' => 'title',
        'requestUpdate' => 'type'
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden,title,storagepid,startingpoints_recursive,single_pages,sysfolder,type,index_content_with_restrictions,index_passed_events,index_news_category_mode,index_news_category_selection,directories,fileext,filteroption,index_page_doctypes'
    ),
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'title' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.title',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'storagepid' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.storagepid',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest',
                    ),
                ),
            )
        ),
        'targetpid' => array(
            'displayCond' => 'FIELD:type:!IN:page,tt_content,file,templavoila,comments,remote',
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.targetpid',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest',
                    ),
                ),
            )
        ),
        'type' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.0',
                        'page',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_0.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.1',
                        'ke_yac',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_1.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.2',
                        'ttnews',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_2.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.5',
                        'tt_address',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_5.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.6',
                        'tt_content',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_6.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.7',
                        'file',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_7.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.8',
                        't3s_content',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_8.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.9',
                        'templavoila',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_9.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.10',
                        'mmforum',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_10.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.11',
                        'comments',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_11.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.12',
                        'news',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_12.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.13',
                        'a21glossary',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_13.gif'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.14',
                        'cal',
                        'EXT:ke_search/res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_14.gif'
                    ),
                ),
                'itemsProcFunc' => 'tx_kesearch_lib_items->fillIndexerConfig',
                'size' => 1,
                'maxitems' => 1,
                'default' => 'page',
            )
        ),
        'startingpoints_recursive' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.startingpoints_recursive',
            'displayCond' => 'FIELD:type:IN:page,tt_content,ttnews,tt_address,templavoila,comments,news,a21glossary,cal',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 99,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest',
                    ),
                ),
            )
        ),
        'single_pages' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.single_pages',
            'displayCond' => 'FIELD:type:IN:page,tt_content,templavoila',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 99,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest',
                    ),
                ),
            )
        ),
        'sysfolder' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.sysfolder',
            'displayCond' => 'FIELD:type:IN:ke_yac,ttnews,tt_address,mmforum,comments,news,a21glossary,cal',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 99,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest',
                    ),
                ),
            )
        ),
        'index_content_with_restrictions' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_content_with_restrictions',
            'displayCond' => 'FIELD:type:=:page',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_content_with_restrictions.I.0',
                        'yes'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_content_with_restrictions.I.1',
                        'no'
                    ),
                ),
                'size' => 1,
                'maxitems' => 1,
                'default' => 'no'
            )
        ),
        'index_passed_events' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_passed_events',
            'displayCond' => 'FIELD:type:=:ke_yac',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_passed_events.I.0',
                        'yes'
                    ),
                    array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_passed_events.I.1', 'no'),
                ),
                'size' => 1,
                'maxitems' => 1,
            )
        ),
        'index_news_category_mode' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_category_mode',
            'displayCond' => 'FIELD:type:IN:ttnews,news',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_category_mode.I.1',
                        '1'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_category_mode.I.2',
                        '2'
                    ),
                ),
                'size' => 1,
                'maxitems' => 1,
            )
        ),
        'index_news_archived' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_archived',
            'displayCond' => 'FIELD:type:IN:news',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_archived.I.0',
                        '0'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_archived.I.1',
                        '1'
                    ),
                    array(
                        'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_archived.I.2',
                        '2'
                    ),
                ),
                'size' => 1,
                'maxitems' => 1,
            )
        ),
        'index_news_category_selection' => Array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_category_selection',
            'displayCond' => 'FIELD:type:=:ttnews',
            'config' => Array(
                'type' => 'none',
            )
        ),
        'index_extnews_category_selection' => array(
            'exclude' => 1,
            'l10n_mode' => 'mergeIfNotBlank',
            'label' => 'LLL:EXT:news/Resources/Private/Language/locallang_db.xml:tx_news_domain_model_news.categories',
            'displayCond' => 'FIELD:type:=:news',
            'config' => array(
                'type' => 'none',
            )
        ),
        'index_news_useHRDatesSingle' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_useHRDatesSingle',
            'displayCond' => 'FIELD:type:=:ttnews',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'index_news_useHRDatesSingleWithoutDay' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_useHRDatesSingleWithoutDay',
            'displayCond' => 'FIELD:type:=:ttnews',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'index_use_page_tags' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_use_page_tags',
            'displayCond' => 'FIELD:type:IN:ttnews,tt_address,mmforum,comments,news',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'directories' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.directories',
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
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.fileext',
            'displayCond' => 'FIELD:type:IN:file,page,tt_content',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'default' => 'pdf,ppt,doc,xls'
            )
        ),
        'index_use_page_tags_for_files' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_use_page_tags_for_files',
            'displayCond' => 'FIELD:type:IN:page,tt_content',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'index_page_doctypes' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_page_doctypes',
            'displayCond' => 'FIELD:type:=:page',
            'config' => array(
                'type' => 'input',
                'size' => '30',
				'default' => '1,2,5'
            )
        ),
        'commenttypes' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.commenttypes',
            'displayCond' => 'FIELD:type:=:comments',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'default' => 'pages'
            )
        ),
        'filteroption' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.filteroption',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('', 0),
                ),
                'itemsProcFunc' => 'user_filterlist->getListOfAvailableFiltersForTCA',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            )
        ),
        'tvpath' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.tvpath',
            'displayCond' => 'FIELD:type:=:templavoila',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'default' => 'field_content'
            )
        ),
        'fal_storage' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.fal_storage',
            'displayCond' => 'FIELD:type:=:file',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.fal_storage.dont_use_fal', -1),
                ),
                'size' => 1,
                'maxitems' => 1,
                'default' => -1,
                'foreign_table' => 'sys_file_storage',
                'allowNonIdValues' => 1
            )
        ),
        'contenttypes' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.contenttypes',
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
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.cal_expired_events',
            'displayCond' => 'FIELD:type:IN:cal',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        )
    ),
    'types' => array(
        '0' => array('showitem' => 'hidden;;1;;1-1-1, title;;;;2-2-2, storagepid,targetpid;;;;3-3-3,type,startingpoints_recursive,single_pages,sysfolder,index_content_with_restrictions,index_passed_events,index_news_archived,index_news_category_mode,index_news_category_selection,index_extnews_category_selection,index_news_useHRDatesSingle,index_news_useHRDatesSingleWithoutDay,index_use_page_tags,fal_storage,directories,fileext,index_page_doctypes,contenttypes,commenttypes,filteroption,tvpath,index_use_page_tags_for_files,cal_expired_events')
    ),
    'palettes' => array(
        '1' => array('showitem' => '')
    )
);

// define dependencies to tt_news only if tt_news is installed
if (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tt_news')) {
    $GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['index_news_category_selection']['config'] = array(
        'type' => 'select',
        'form_type' => 'user',
        'userFunc' => 'tx_ttnews_TCAform_selectTree->renderCategoryFields',
        'treeView' => 1,
        'foreign_table' => 'tt_news_cat',
        'autoSizeMax' => 50,
        'minitems' => 0,
        'maxitems' => 500,
    );
}

// define dependencies to news only if news is installed
if (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
    $configurationArray['columns']['index_extnews_category_selection']['config'] = array(
        'type' => 'select',
        'renderType' => 'selectTree',
        'renderMode' => 'tree',
        'treeConfig' => array(
            'parentField' => 'parentcategory',
        ),
        'foreign_table' => 'tx_news_domain_model_category',
        'foreign_table_where' => ' AND (tx_news_domain_model_category.sys_language_uid = 0 OR tx_news_domain_model_category.l10n_parent = 0) ORDER BY tx_news_domain_model_category.sorting',
        'size' => 10,
        'autoSizeMax' => 20,
        'minitems' => 0,
        'maxitems' => 20,
    );

    // news version 3 features system categories instead of it's own
    // category system which was used in previous versions
    if (version_compare(TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('news'), '3.0.0') >= 0) {
        $configurationArray['columns']['index_extnews_category_selection']['config']['treeConfig']['parentField'] = 'parent';
        $configurationArray['columns']['index_extnews_category_selection']['config']['foreign_table'] = 'sys_category';
        $configurationArray['columns']['index_extnews_category_selection']['config']['foreign_table_where'] = '';
    }
}


return $configurationArray;
