<?php
class tx_kesearch_classes_flexform {
	/**
	 * @var language
	 */
	var $lang;

	public function init() {
		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$this->lang = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Lang\\LanguageService');
		} else if (TYPO3_VERSION_INTEGER >= 6002000) {
			$this->lang = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('language');
		} else {
			$this->lang = t3lib_div::makeInstance('language');
		}

		if (TYPO3_VERSION_INTEGER < 6001000) {
			t3lib_div::loadTCA('tx_kesearch_index');
		}
	}

	function listAvailableOrderingsForFrontend(&$config) {
		$this->init();
		$this->lang->init($GLOBALS['BE_USER']->uc['lang']);

		// get orderings
		$fieldLabel = $this->lang->sL('LLL:EXT:ke_search/locallang_db.php:tx_kesearch_index.relevance');
		$notAllowedFields = 'uid,pid,tstamp,crdate,cruser_id,starttime,endtime,fe_group,targetpid,content,params,type,tags,abstract,language,orig_uid,orig_pid,hash';
		$config['items'][] = array($fieldLabel, 'score');
		$res = $GLOBALS['TYPO3_DB']->sql_query('SHOW COLUMNS FROM tx_kesearch_index');
		while($col = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

			if (TYPO3_VERSION_INTEGER >= 7000000) {
				$isInList = TYPO3\CMS\Core\Utility\GeneralUtility::inList($notAllowedFields, $col['Field']);
			} else {
				$isInList = t3lib_div::inList($notAllowedFields, $col['Field']);
			}

			if(!$isInList) {
				$file = $GLOBALS['TCA']['tx_kesearch_index']['columns'][$col['Field']]['label'];
				$fieldLabel = $this->lang->sL($file);
				$config['items'][] = array($fieldLabel, $col['Field']);
			}
		}
	}

	function listAvailableOrderingsForAdmin(&$config) {
		$this->init();
		$this->lang->init($GLOBALS['BE_USER']->uc['lang']);

		// get orderings
		$fieldLabel = $this->lang->sL('LLL:EXT:ke_search/locallang_db.php:tx_kesearch_index.relevance');
		$notAllowedFields = 'uid,pid,tstamp,crdate,cruser_id,starttime,endtime,fe_group,targetpid,content,params,type,tags,abstract,language,orig_uid,orig_pid,hash';
		if(!$config['config']['relevanceNotAllowed']) {
			$config['items'][] = array($fieldLabel . ' UP', 'score asc');
			$config['items'][] = array($fieldLabel . ' DOWN', 'score desc');
		}
		$res = $GLOBALS['TYPO3_DB']->sql_query('SHOW COLUMNS FROM tx_kesearch_index');
		while($col = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {

			if (TYPO3_VERSION_INTEGER >= 7000000) {
				$isInList = TYPO3\CMS\Core\Utility\GeneralUtility::inList($notAllowedFields, $col['Field']);
			} else {
				$isInList = t3lib_div::inList($notAllowedFields, $col['Field']);
			}

			if(!$isInList) {
				$file = $GLOBALS['TCA']['tx_kesearch_index']['columns'][$col['Field']]['label'];
				$fieldLabel = $this->lang->sL($file);
				$config['items'][] = array($fieldLabel . ' UP', $col['Field'] . ' asc');
				$config['items'][] = array($fieldLabel . ' DOWN', $col['Field'] . ' desc');
			}
		}
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/Backend/class.tx_kesearch_classes_flexform.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/Backend/Class.tx_kesearch_classes_flexform.php']);
}
?>