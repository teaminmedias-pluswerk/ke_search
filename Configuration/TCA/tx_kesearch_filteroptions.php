<?php

$langGeneralPath = 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:';
if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) <
    \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('8.0')
) {
    $langGeneralPath = 'LLL:EXT:lang/locallang_general.xml:';
}

return array(
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
        'iconfile' => 'EXT:ke_search/res/img/table_icons/icon_tx_kesearch_filteroptions.gif',
        'searchFields' => 'title,tag'
    ),
    'interface' => array(
        'showRecordFieldList' => 'sys_language_uid,l10n_parent,l10n_diffsource,hidden,title,tag'
    ),
    'columns' => array(
        'sys_language_uid' => array(
            'exclude' => 1,
            'label' => $langGeneralPath . 'LGL.language',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => array(
                    array($langGeneralPath . 'LGL.allLanguages', -1),
                    array($langGeneralPath . 'LGL.default_value', 0)
                )
            )
        ),
        'l10n_parent' => array(
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => $langGeneralPath . 'LGL.l18n_parent',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('', 0),
                ),
                'foreign_table' => 'tx_kesearch_filteroptions',
                'foreign_table_where' => 'AND tx_kesearch_filteroptions.pid=###CURRENT_PID###'
                    . ' AND tx_kesearch_filteroptions.sys_language_uid IN (-1,0)',
            )
        ),
        'l10n_diffsource' => array(
            'config' => array(
                'type' => 'passthrough'
            )
        ),
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
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filteroptions.title',
            'config' => array(
                'type' => 'input',
                'size' => '30',
            )
        ),
        'tag' => array(
            'exclude' => 0,
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filteroptions.tag',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim,required,TeaminmediasPluswerk\KeSearch\UserFunction\CustomFieldValidation\FilterOptionTagValidator'
            )
        ),
        'automated_tagging' => array(
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filteroptions.automated_tagging',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 99,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest',
                    ),
                ),
            )
        ),
        'automated_tagging_exclude' => array(
            'exclude' => 1,
            'l10n_mode' => 'exclude',
            'label' => 'LLL:EXT:ke_search/locallang_db.xml:tx_kesearch_filteroptions.automated_tagging_exclude',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 99,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest',
                    ),
                ),
            )
        ),
    ),
    'types' => array(
        '0' => array('showitem' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden,'
            . ' title, tag, automated_tagging,automated_tagging_exclude')
    )
);
