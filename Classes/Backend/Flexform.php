<?php
namespace TeaminmediasPluswerk\KeSearch\Backend;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TeaminmediasPluswerk\KeSearch\Lib\Db;

class Flexform
{
    public $lang;
    public $notAllowedFields;

    public function init()
    {
        $this->lang = GeneralUtility::makeInstance(LanguageService::class);
        $this->notAllowedFields = 'uid,pid,tstamp,crdate,cruser_id,starttime,endtime'
            . ',fe_group,targetpid,content,params,type,tags,abstract,language'
            . ',orig_uid,orig_pid,hash,lat,lon,externalurl,lastremotetransfer';
    }

    public function listAvailableOrderingsForFrontend(&$config)
    {
        $this->init();
        $this->lang->init($GLOBALS['BE_USER']->uc['lang']);

        // get orderings
        $fieldLabel = $this->lang->sL('LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_index.relevance');
        $config['items'][] = array($fieldLabel, 'score');
        $res = Db::getDatabaseConnection('tx_kesearch_index')->fetchAll('SHOW COLUMNS FROM tx_kesearch_index');

        foreach($res as $col) {
            $isInList = GeneralUtility::inList($this->notAllowedFields, $col['Field']);
            if (!$isInList) {
                $file = $GLOBALS['TCA']['tx_kesearch_index']['columns'][$col['Field']]['label'];
                $fieldLabel = $this->lang->sL($file);
                $config['items'][] = array($fieldLabel, $col['Field']);
            }
        }
    }

    public function listAvailableOrderingsForAdmin(&$config)
    {
        $this->init();
        $this->lang->init($GLOBALS['BE_USER']->uc['lang']);

        // get orderings
        $fieldLabel = $this->lang->sL('LLL:EXT:ke_search/Resources/Private/Language/locallang_db.xml:tx_kesearch_index.relevance');
        if (!$config['config']['relevanceNotAllowed']) {
            $config['items'][] = array($fieldLabel . ' UP', 'score asc');
            $config['items'][] = array($fieldLabel . ' DOWN', 'score desc');
        }
        $res = Db::getDatabaseConnection('tx_kesearch_index')->fetchAll('SHOW COLUMNS FROM tx_kesearch_index');

        foreach($res as $col) {
            $isInList = GeneralUtility::inList($this->notAllowedFields, $col['Field']);
            if (!$isInList) {
                $file = $GLOBALS['TCA']['tx_kesearch_index']['columns'][$col['Field']]['label'];
                $fieldLabel = $this->lang->sL($file);
                $config['items'][] = array($fieldLabel . ' UP', $col['Field'] . ' asc');
                $config['items'][] = array($fieldLabel . ' DOWN', $col['Field'] . ' desc');
            }
        }
    }
}
