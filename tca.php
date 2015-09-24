<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$extPath = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ke_search');
$extRelPath = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('ke_search');

$TCA['tx_kesearch_filters'] = array (
	'ctrl' => $TCA['tx_kesearch_filters']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'sys_language_uid,l10n_parent,l10n_diffsource,hidden,title,options,rendertype'
	),
	'columns' => array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_kesearch_filters',
				'foreign_table_where' => 'AND tx_kesearch_filters.pid=###CURRENT_PID### AND tx_kesearch_filters.sys_language_uid IN (-1,0)',
			)
		),
		'l10n_diffsource' => array (
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters.title',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'rendertype' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters.rendertype',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters.rendertype.I.0', 'select'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters.rendertype.I.1', 'list'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters.rendertype.I.2', 'checkbox'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters.rendertype.I.3', 'textlinks'),
					),
				'size' => 1,
				'maxitems' => 1,
				'default' => 'select',
			)
		),

		'markAllCheckboxes' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters.markAllCheckboxes',
			'config' => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'options' => array (
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters.options',
			'config' => Array(
				'type' => 'inline',
				'foreign_table' => 'tx_kesearch_filteroptions',
				'maxitems' => 500,
				'appearance' => Array(
					'collapseAll' => 1,
					'expandSingle' => 1,
					'useSortable' => 1,
				),
			),
		),
		'target_pid' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters.target_pid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'amount' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters.amount',
			'config' => array (
				'type' => 'input',
				'default' => '10',
				'size' => '30',
				'eval' => 'trim,int',
			)
		),
		'shownumberofresults' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters.shownumberofresults',
			'config' => array (
				'type'    => 'check',
				'default' => '1'
			)
		),
		'alphabeticalsorting' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filters.alphabeticalsorting',
			'config' => array (
				'type'    => 'check',
				'default' => '1',
			)
		),
	),
	'types' => array (
		'select' => array('showitem' => 'sys_language_uid;;;;1-1-1,l10n_parent, l10n_diffsource, hidden;;1;;1-1-1, title;;;;2-2-2,rendertype;;;;3-3-3, options, shownumberofresults, alphabeticalsorting;;;;4-4-4'),
		'list' => array('showitem' => 'sys_language_uid;;;;1-1-1,l10n_parent, l10n_diffsource, hidden;;1;;1-1-1, title;;;;2-2-2,rendertype;;;;3-3-3, options, shownumberofresults, alphabeticalsorting;;;;4-4-4'),
		'checkbox' => array('showitem' => 'sys_language_uid;;;;1-1-1,l10n_parent, l10n_diffsource, hidden;;1;;1-1-1, title;;;;2-2-2,rendertype;;;;3-3-3, markAllCheckboxes, options, shownumberofresults, alphabeticalsorting;;;;4-4-4'),
		'textlinks' => array('showitem' => 'sys_language_uid;;;;1-1-1,l10n_parent, l10n_diffsource, hidden;;1;;1-1-1, title;;;;2-2-2,rendertype;;;;3-3-3, target_pid, amount, shownumberofresults, alphabeticalsorting;;;;4-4-4, options')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$TCA['tx_kesearch_filteroptions'] = array (
	'ctrl' => $TCA['tx_kesearch_filteroptions']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'sys_language_uid,l10n_parent,l10n_diffsource,hidden,title,tag'
	),
	'columns' => array (
		'sys_language_uid' => array (
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_kesearch_filteroptions',
				'foreign_table_where' => 'AND tx_kesearch_filteroptions.pid=###CURRENT_PID### AND tx_kesearch_filteroptions.sys_language_uid IN (-1,0)',
			)
		),
		'l10n_diffsource' => array (
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filteroptions.title',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'tag' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filteroptions.tag',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'automated_tagging' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filteroptions.automated_tagging',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'automated_tagging_exclude' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filteroptions.automated_tagging_exclude',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 5,
				'minitems' => 0,
				'maxitems' => 99,
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1;;1-1-1, title;;;;2-2-2, tag;;;;3-3-3, automated_tagging;;;;4-4-4,automated_tagging_exclude;;;;5-5-5')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);



$TCA['tx_kesearch_index'] = array (
	'ctrl' => $TCA['tx_kesearch_index']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'targetpid,content,params,type,tags,abstract,title,language'
	),
	'columns' => array (
		'starttime' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'default'  => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'checkbox' => '0',
				'default'  => '0',
				'range'    => array (
					'upper' => mktime(3, 14, 7, 1, 19, 2038),
					'lower' => mktime(0, 0, 0, date('m')-1, date('d'), date('Y'))
				)
			)
		),
		'fe_group' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.fe_group',
			'config'  => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
					array('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
					array('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'ORDER BY fe_groups.title',
				'size' => 6,
				'minitems' => 0,
				'maxitems' => 99999,
			)
		),
		'targetpid' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_index.targetpid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'content' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_index.content',
			'config' => array (
				'type' => 'text',
				'wrap' => 'OFF',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'params' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_index.params',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'type' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_index.type',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'tags' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_index.tags',
			'config' => array (
				'type' => 'text',
				'wrap' => 'OFF',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'abstract' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_index.abstract',
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_index.title',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'language' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_index.language',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0)
				),
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'sortdate' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_index.sortdate',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'datetime',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'orig_uid' => array (
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'orig_pid' => array (
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'directory' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_index.directory',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'hash' => array (
			'config' => array (
				'type' => 'passthrough'
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'starttime;;;;1-1-1, endtime, fe_group, targetpid, content, params, type, tags, abstract, title;;;;2-2-2, language;;;;3-3-3')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);


$TCA['tx_kesearch_indexerconfig']['ctrl']['requestUpdate'] = 'type';
$TCA['tx_kesearch_indexerconfig'] = array (
	'ctrl' => $TCA['tx_kesearch_indexerconfig']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,title,storagepid,startingpoints_recursive,single_pages,sysfolder,type,index_content_with_restrictions,index_passed_events,,index_news_category_mode,index_news_category_selection,directories,fileext,filteroption'
	),
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'title' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.title',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'storagepid' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.storagepid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			)
		),
		'targetpid' => array (
			'displayCond' => 'FIELD:type:!IN:page,tt_content,file,templavoila,comments,remote',
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.targetpid',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			)
		),
		'type' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.0', 'page',         $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_0.gif'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.1', 'ke_yac',       $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_1.gif'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.2', 'ttnews',       $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_2.gif'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.3', 'dam',          $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_3.gif'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.5', 'tt_address',   $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_5.gif'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.6', 'tt_content',   $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_6.gif'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.7', 'file',         $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_7.gif'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.8', 't3s_content',  $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_8.gif'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.9', 'templavoila',  $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_9.gif'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.10', 'mmforum',     $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_10.gif'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.11', 'comments',    $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_11.gif'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.12', 'news',        $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_12.gif'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.type.I.13', 'a21glossary', $extRelPath . 'res/img/types_backend/selicon_tx_kesearch_indexerconfig_type_13.gif'),
				),
				'itemsProcFunc' => 'tx_kesearch_lib_items->fillIndexerConfig',
				'size' => 1,
				'maxitems' => 1,
				'default' => 'page',
			)
		),
		'startingpoints_recursive' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.startingpoints_recursive',
			'displayCond' => 'FIELD:type:=:page,tt_content,ttnews,tt_address,templavoila,comments,news,a21glossary',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 99,
			)
		),
		'single_pages' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.single_pages',
			'displayCond' => 'FIELD:type:=:page,tt_content,templavoila',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 99,
			)
		),
		'sysfolder' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.sysfolder',
			'displayCond' => 'FIELD:type:IN:ke_yac,ttnews,dam,tt_address,mmforum,comments,news,a21glossary',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 99,
			)
		),
		'index_content_with_restrictions' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_content_with_restrictions',
			'displayCond' => 'FIELD:type:=:page',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_content_with_restrictions.I.0', 'yes'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_content_with_restrictions.I.1', 'no'),
				),
				'size' => 1,
				'maxitems' => 1,
				'default' => 'no'
			)
		),
		'index_passed_events' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_passed_events',
			'displayCond' => 'FIELD:type:=:ke_yac',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_passed_events.I.0', 'yes'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_passed_events.I.1', 'no'),
				),
				'size' => 1,
				'maxitems' => 1,
			)
		),
		'index_news_category_mode' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_category_mode',
			'displayCond' => 'FIELD:type:=:ttnews,news',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_category_mode.I.1', '1'),
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_category_mode.I.2', '2'),
				),
				'size' => 1,
				'maxitems' => 1,
			)
		),
		'index_news_category_selection' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_category_selection',
			'displayCond' => 'FIELD:type:=:ttnews',
			'config' => Array (
				'type' => 'select',
				'form_type' => 'user',
				'userFunc' => 'tx_ttnews_TCAform_selectTree->renderCategoryFields',
				'treeView' => 1,
				'foreign_table' => 'tt_news_cat',
				'autoSizeMax' => 50,
				'minitems' => 0,
				'maxitems' => 500,
			)
		),
		'index_extnews_category_selection' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:news/Resources/Private/Language/locallang_db.xml:tx_news_domain_model_news.categories',
			'displayCond' => 'FIELD:type:=:news',
			'config' => array(
				'type' => 'select',
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
			)
		),
		'index_news_useHRDatesSingle' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_useHRDatesSingle',
			'displayCond' => 'FIELD:type:=:ttnews',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'index_news_useHRDatesSingleWithoutDay' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_news_useHRDatesSingleWithoutDay',
			'displayCond' => 'FIELD:type:=:ttnews',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'index_dam_categories' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_dam_categories',
			'displayCond' => 'FIELD:type:=:dam',
			'config' => array (
				'type' => 'select',
				'form_type' => 'user',
				'userFunc' => 'EXT:dam/lib/class.tx_dam_tcefunc.php:&tx_dam_tceFunc->getSingleField_selectTree',
				'treeViewBrowseable' =>  0,
				'treeViewClass' => 'EXT:dam/components/class.tx_dam_selectionCategory.php:&tx_dam_selectionCategory',
				'foreign_table' => 'tx_dam_cat',
				'size' => 10,
				'autoSizeMax' => 10,
				'minitems' => 0,
				'maxitems' => 100,
			)
		),
		'index_dam_without_categories' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_dam_without_categories',
			'displayCond' => 'FIELD:type:=:dam',
			'config' => array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'index_dam_categories_recursive' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_dam_categories_recursive',
			'displayCond' => 'FIELD:type:=:dam',
			'config' => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'index_use_page_tags' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_use_page_tags',
			'displayCond' => 'FIELD:type:=:ttnews,tt_address,mmforum,comments,news',
			'config' => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'directories' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.directories',
			'displayCond' => 'FIELD:type:IN:file',
			'config' => array (
				'type' => 'text',
				'cols' => 48,
				'rows' => 10,
				'eval' => 'trim',
			)
		),
		'fileext' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.fileext',
			'displayCond' => 'FIELD:type:IN:file,page,tt_content',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'default' => 'pdf,ppt,doc,xls'
			)
		),
		'index_use_page_tags_for_files' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.index_use_page_tags_for_files',
			'displayCond' => 'FIELD:type:=:page,tt_content',
			'config' => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'commenttypes' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.commenttypes',
			'displayCond' => 'FIELD:type:=:comments',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'default' => 'pages'
			)
		),
		'filteroption' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.filteroption',
			'config' => array (
				'type'   => 'select',
				'items' => array (
					array('', 0),
				),
				'itemsProcFunc' => 'user_filterlist->getListOfAvailableFiltersForTCA',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'tvpath' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.tvpath',
			'displayCond' => 'FIELD:type:=:templavoila',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'default' => 'field_content'
			)
		),
		'fal_storage' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.fal_storage',
			'displayCond' => 'FIELD:type:IN:file',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.fal_storage.dont_use_fal', -1),
				),
				'size' => 1,
				'maxitems' => 1,
				'default' => -1,
				'foreign_table' => 'sys_file_storage',
				'allowNonIdValues' => 1
			)
		),
		'contenttypes' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_indexerconfig.contenttypes',
			'displayCond' => 'FIELD:type:IN:page,tt_content',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'default' => 'text,textpic,bullets,table,html,header,uploads'
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'hidden;;1;;1-1-1, title;;;;2-2-2, storagepid,targetpid;;;;3-3-3,type,startingpoints_recursive,single_pages,sysfolder,index_content_with_restrictions,index_passed_events,index_news_category_mode,index_news_category_selection,index_extnews_category_selection,index_news_useHRDatesSingle,index_news_useHRDatesSingleWithoutDay,index_dam_categories,index_dam_without_categories,index_dam_categories_recursive,index_use_page_tags,fal_storage,directories,fileext,contenttypes,commenttypes,filteroption,tvpath,index_use_page_tags_for_files')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

// news version 3 features system categories instead of it's own
// category system which was used in previous versions
$ttnewsVersion = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getExtensionVersion('news');
if (version_compare($ttnewsVersion, '3.0.0') >= 0) {
	$TCA['tx_kesearch_indexerconfig']['columns']['index_extnews_category_selection']['config']['treeConfig']['parentField'] = 'parent';
	$TCA['tx_kesearch_indexerconfig']['columns']['index_extnews_category_selection']['config']['foreign_table'] = 'sys_category';
	$TCA['tx_kesearch_indexerconfig']['columns']['index_extnews_category_selection']['config']['foreign_table_where'] = '';
}