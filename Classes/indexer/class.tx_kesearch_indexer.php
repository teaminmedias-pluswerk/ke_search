<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 *
 * @author	Andreas Kiefer (kennziffer.com) <kiefer@kennziffer.com>
 * @author	Stefan Froemken
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_indexer {
	var $counter;
	var $extConf; // extension configuration
	var $extConfPremium = array(); // extension configuration of ke_search_premium, if installed
	var $indexerConfig = array(); // saves the indexer configuration of current loop
	var $lockFile = '';
	var $additionalFields = '';
	var $indexingErrors = array();
	var $startTime;
	var $currentRow = array(); // current row which have to be inserted/updated to database

	// We collect some records before saving
	// this is faster than waiting till the next record was builded
	var $tempArrayForUpdatingExistingRecords = array();
	var $tempArrayForInsertNewRecords = array();
	var $amountOfRecordsToSaveInMem = 100;

	/**
	 * @var t3lib_Registry
	 */
	var $registry;

	/**
	 * @var tx_kesearch_lib_div
	 */
	var $div;

	/**
	 * @var array
	 */
	var $defaultIndexerTypes = array();

	/**
	 * Constructor of this class
	 */
	public function __construct() {
		// get extension configuration array
		$this->extConf = tx_kesearch_helper::getExtConf();
		$this->extConfPremium = tx_kesearch_helper::getExtConfPremium();
		$this->registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');

		// fetch the list of the default indexers which come with ke_search
		foreach ($GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['type']['config']['items'] as $indexerType) {
			$this->defaultIndexerTypes[] = $indexerType[1];
		}

	}

	/*
	 * function startIndexing
	 * @param $verbose boolean 	if set, information about the indexing process is returned, otherwise processing is quiet
	 * @param $extConf array			extension config array from EXT Manager
	 * @param $mode string				"CLI" if called from command line, otherwise empty
	 * @return string							only if param $verbose is true
	 */
	function startIndexing($verbose=true, $extConf, $mode='')  {
		// write starting timestamp into registry
		// this is a helper to delete all records which are older than starting timestamp in registry
		// this also prevents starting the indexer twice
		if($this->registry->get('tx_kesearch', 'startTimeOfIndexer') === null) {
			$this->registry->set('tx_kesearch', 'startTimeOfIndexer', time());
		} else {
			// check lock time
			$lockTime = $this->registry->get('tx_kesearch', 'startTimeOfIndexer');
			$compareTime = time() - (60*60*12);
			if ($lockTime < $compareTime) {
				// lock is older than 12 hours - remove
				$this->registry->removeAllByNamespace('tx_kesearch');
				$this->registry->set('tx_kesearch', 'startTimeOfIndexer', time());
			} else {
				return 'You can\'t start the indexer twice. Please wait while first indexer process is currently running';
			}
		}

		// set indexing start time
		$this->startTime = time();

		// get configurations
		$configurations = $this->getConfigurations();

		// number of records that should be written to the database in one action
		$this->amountOfRecordsToSaveInMem = 500;

		// register additional fields which should be written to DB
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerAdditionalFields'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerAdditionalFields'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$_procObj->registerAdditionalFields($this->additionalFields);
			}
		}

		// set some prepare statements
		$this->prepareStatements();

		foreach($configurations as $indexerConfig) {
			$this->indexerConfig = $indexerConfig;

			// run default indexers shipped with ke_search
			if (in_array($this->indexerConfig['type'], $this->defaultIndexerTypes)) {
				$path = TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ke_search') . 'Classes/indexer/types/class.tx_kesearch_indexer_types_' . $this->indexerConfig['type'] . '.php';
				if(is_file($path)) {
					require_once($path);
					$searchObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_indexer_types_' . $this->indexerConfig['type'], $this);
					$content .= $searchObj->startIndexing();
				} else {
					$content = '<div class="error"> Could not find file ' . $path . '</div>' . "\n";
				}
			}

			// hook for custom indexer
			if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['customIndexer'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['customIndexer'] as $_classRef) {
					$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
					$content .= $_procObj->customIndexer($indexerConfig, $this);
				}
			}

			// In most cases there are some records waiting in ram to be written to db
			$this->storeTempRecordsToIndex('both');
		}

		// process index cleanup
		$content .= $this->cleanUpIndex();

		// count index records
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'tx_kesearch_index');
		$content .= '<p><b>Index contains ' . $count . ' entries.</b></p>';

		// clean up process after indezing to free memory
		$this->cleanUpProcessAfterIndexing();

		// print indexing errors
		if (sizeof($this->indexingErrors)) {
			$content .= "\n\n".'<br /><br /><br /><b>INDEXING ERRORS (' . sizeof($this->indexingErrors) . ')<br /><br />'.CHR(10);
			foreach ($this->indexingErrors as $error) {
				$content .= $error . '<br />' . CHR(10);
			}
		}

		// create plaintext report
		$plaintextReport = $this->createPlaintextReport($content);

		// send notification in CLI mode
		if ($mode == 'CLI') {
			// send finishNotification
			$isValidEmail = TYPO3\CMS\Core\Utility\GeneralUtility::validEmail($extConf['notificationRecipient']);
			if ($extConf['finishNotification'] && $isValidEmail) {

				// send the notification message
				$mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
				$mail->setFrom(array($extConf['notificationSender']));
				$mail->setTo(array($extConf['notificationRecipient']));
				$mail->setSubject($extConf['notificationSubject']);
				$mail->setBody($plaintextReport);
				$mail->send();
			}
		}

		// log report to sys_log
		$GLOBALS['BE_USER']->simplelog($plaintextReport, 'ke_search');

		// verbose or quiet output? as set in function call!
		if ($verbose) return $content;
	}

	/**
	 * create plaintext report from html content
	 *
	 * @param string $content
	 * @return string
	 */
	public function createPlaintextReport($content) {
		$report = 'ke_search indexing report' . "\n\n";
		$report .= 'Finishing time: ' . date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ', H:i') . "\n\n";
		$report .= strip_tags($content);

		// calculate and format indexing time
		$indexingTime = time() - $this->startTime;
		if ($indexingTime > 3600) {
			// format hours
			$indexingTime = $indexingTime / 3600;
			$indexingTime = number_format($indexingTime, 2, ',', '.');
			$indexingTime .= ' hours';
		} else if ($indexingTime > 60) {
			// format minutes
			$indexingTime = $indexingTime / 60;
			$indexingTime = number_format($indexingTime, 2, ',', '.');
			$indexingTime .= ' minutes';
		} else {
			$indexingTime .= ' seconds';
		}
		$report .= "\n\n".'Indexing process ran '.$indexingTime;

		return $report;
	}

	/**
	 * prepare sql-statements for indexer
	 *
	 * @return void
	 */
	public function prepareStatements() {
		// create vars to keep statements dynamic
		foreach($this->additionalFields as $value) {
			$addUpdateQuery .= ', ' . $value . ' = ?';
			$addInsertQueryFields .= ', ' . $value;
			$addInsertQueryValues .= ', ?';
		}

		// Statement to check if record already exists in db
		$GLOBALS['TYPO3_DB']->sql_query('PREPARE searchStmt FROM "
			SELECT *
			FROM tx_kesearch_index
			WHERE orig_uid = ?
			AND pid = ?
			AND type = ?
			AND language = ?
			LIMIT 1
		"');

		// Statement to update an existing record in indexer table
		$GLOBALS['TYPO3_DB']->sql_query('PREPARE updateStmt FROM "
			UPDATE tx_kesearch_index
			SET pid=?,
			title=?,
			type=?,
			targetpid=?,
			content=?,
			tags=?,
			params=?,
			abstract=?,
			language=?,
			starttime=?,
			endtime=?,
			fe_group=?,
			tstamp=?' . $addUpdateQuery . '
			WHERE uid=?
		"');

		// Statement to insert a new records to index table
		$GLOBALS['TYPO3_DB']->sql_query('PREPARE insertStmt FROM "
			INSERT INTO tx_kesearch_index
			(pid, title, type, targetpid, content, tags, params, abstract, language, starttime, endtime, fe_group, tstamp, crdate' . $addInsertQueryFields . ')
			VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?' . $addInsertQueryValues . ', ?)
		"');

		// disable keys only if indexer table was truncated (has 0 records)
		// this speeds up the first indexing process
		// don't use this for updating index table
		// if you activate this for updating 40.000 existing records, indexing process needs 1 hour longer
		$countIndex = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'tx_kesearch_index', '');
		if($countIndex == 0) $GLOBALS['TYPO3_DB']->sql_query('ALTER TABLE tx_kesearch_index DISABLE KEYS');
	}


	/**
	 * clean up statements
	 *
	 * @return void
	 */
	public function cleanUpProcessAfterIndexing() {
		// enable keys again if this was the first indexing process
		if($countIndex == 0) $GLOBALS['TYPO3_DB']->sql_query('ALTER TABLE tx_kesearch_index ENABLE KEYS');

		$GLOBALS['TYPO3_DB']->sql_query('DEALLOCATE PREPARE searchStmt');
		$GLOBALS['TYPO3_DB']->sql_query('DEALLOCATE PREPARE updateStmt');
		$GLOBALS['TYPO3_DB']->sql_query('DEALLOCATE PREPARE insertStmt');

		// remove all entries from ke_search registry
		$this->registry->removeAllByNamespace('tx_kesearch');
	}


	/**
	 * Delete all index elements that are older than starting timestamp in registry
	 *
	 * @return string content for BE
	*/
	function cleanUpIndex() {
		$content = '';
		$startMicrotime = microtime(true);
		$table = 'tx_kesearch_index';

		// select all index records older than the beginning of the indexing process
		$where = 'tstamp < ' . $this->registry->get('tx_kesearch', 'startTimeOfIndexer');

		// hook for cleanup
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['cleanup'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['cleanup'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$content .= $_procObj->cleanup($where, $this);
			}
		}

		// count and delete old index records
		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', $table, $where);
		$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, $where);

		// check if Sphinx is enabled
		// in this case we have to update sphinx index, too.
		if($this->extConfPremium['enableSphinxSearch']) {
			if(!$this->extConfPremium['sphinxIndexerName']) $this->extConfPremium['sphinxIndexerConf'] = '--all';
			if(is_file($this->extConfPremium['sphinxIndexerPath']) && is_executable($this->extConfPremium['sphinxIndexerPath']) && file_exists($this->extConfPremium['sphinxSearchdPath']) && is_executable($this->extConfPremium['sphinxIndexerPath'])) {
				$found = preg_match_all('/exec|system/', ini_get('disable_functions'), $match);
				if($found === 0) { // executables are allowed
					$ret = system($this->extConfPremium['sphinxIndexerPath'] . ' --rotate ' . $this->extConfPremium['sphinxIndexerName']);
					if (strpos($ret, 'WARNING') !== FALSE) {
						$warning = strstr($ret, 'WARNING');
						$content .= '<div class="error">SPHINX ' . $warning . '</div>';
					}
					$content .= $ret;
				} elseif($found === 1) { // one executable is allowed
					if($match[0] == 'system') {
						$ret = system($this->extConfPremium['sphinxIndexerPath'] . ' --rotate ' . $this->extConfPremium['sphinxIndexerName']);
					} else { // use exec
						exec($this->extConfPremium['sphinxIndexerPath'] . ' --rotate ' . $this->extConfPremium['sphinxIndexerName'], $retArr);
						foreach ($retArr as $retRow) {
							if (strpos($retRow, 'WARNING') !== FALSE) {
								$content .= '<div class="error">SPHINX ' . $retRow . '</div>';
							}
						}
						$ret = implode(';', $retArr);
					}
					$content .= $ret;
				} else {
					$content .= '<div class="error">Check your php.ini configuration for disable_functions. For now it is not allowed to execute a shell script.</div>';
				}
			} else {
				$content .= '<div class="error">We can\'t find the sphinx executables or execution permission is missing.</div>';
			}
		}

		$content .= '<p><b>Index cleanup:</b><br />' . "\n";
		$content .= $count . ' entries deleted.<br />' . "\n";

		// calculate duration of indexing process
		$duration = ceil((microtime(true) - $startMicrotime) * 1000);
		$content .= '<i>Cleanup process took ' . $duration . ' ms.</i></p>'."\n";

		return $content;
	}


	/**
	 * store collected data of defined indexers to db
	 *
	 * @param integer $storagepid
	 * @param string $title
	 * @param string $type
	 * @param string $targetpid
	 * @param string $content
	 * @param string $tags
	 * @param string $params
	 * @param string $abstract
	 * @param string $language
	 * @param integer $starttime
	 * @param integer $endtime
	 * @param string $fe_group
	 * @param boolean $debugOnly
	 * @param array $additionalFields
	 */
	function storeInIndex($storagePid, $title, $type, $targetPid, $content, $tags='', $params='', $abstract='', $language=0, $starttime=0, $endtime=0, $fe_group='', $debugOnly=false, $additionalFields=array()) {
		// if there are errors found in current record return false and break processing
		if(!$this->checkIfRecordHasErrorsBeforeIndexing($storagePid, $title, $type, $targetPid)) return false;

		// optionally add tag set in the indexer configuration
		if (!empty($this->indexerConfig['filteroption'])
			&& (
				(substr($type, 0, 4) != 'file' || (substr($type, 0, 4) == 'file' && $this->indexerConfig['index_use_page_tags_for_files']))
				|| $this->indexerConfig['type'] == 'file'
				)
			) {
			$indexerTag = $this->getTag( $this->indexerConfig['filteroption'] );
			$tagChar = $this->extConf['prePostTagChar'];
			if ($tags) {
				$tags .= ',' . $tagChar . $indexerTag . $tagChar;
			} else {
				$tags = $tagChar . $indexerTag . $tagChar;
			}
			$tags = TYPO3\CMS\Core\Utility\GeneralUtility::uniqueList($tags);
		}

		$table = 'tx_kesearch_index';
		$fieldValues = $this->createFieldValuesForIndexing($storagePid, $title, $type, $targetPid, $content, $tags, $params, $abstract, $language, $starttime, $endtime, $fe_group, $additionalFields);

		// check if record already exists
		if (substr($type, 0, 4) == 'file') {
			$recordExists = $this->checkIfFileWasIndexed($fieldValues['type'], $fieldValues['hash']);
		} else {
			$recordExists = $this->checkIfRecordWasIndexed($fieldValues['orig_uid'], $fieldValues['pid'], $fieldValues['type'], $fieldValues['language']);
		}

		if ($recordExists) { // update existing record
			$where = 'uid=' . intval($this->currentRow['uid']);
			unset($fieldValues['crdate']);
			if ($debugOnly) { // do not process - just debug query
				t3lib_utility_Debug::debug($GLOBALS['TYPO3_DB']->UPDATEquery($table,$where,$fieldValues),1);
			} else { // process storing of index record and return uid
				$this->prepareRecordForUpdate($fieldValues);
				return true;
			}
		} else { // insert new record
			if($debugOnly) { // do not process - just debug query
				t3lib_utility_Debug::debug($GLOBALS['TYPO3_DB']->INSERTquery($table, $fieldValues, FALSE));
			} else { // process storing of index record and return uid
				$this->prepareRecordForInsert($fieldValues);
				return $GLOBALS['TYPO3_DB']->sql_insert_id();
			}
		}
	}


	/**
	 * Return the query part for additional fields to get prepare statements dynamic
	 *
	 * @param array $fieldValues
	 * @return array containing two query parts
	 */
	public function getQueryPartForAdditionalFields(array $fieldValues) {
		$queryForSet = '';
		$queryForExecute = '';

		foreach($this->additionalFields as $value) {
			$queryForSet .= ', @' . $value . ' = ' . $fieldValues[$value];
			$queryForExecute .= ', @' . $value;
		}
		return array('set' => $queryForSet, 'execute' => $queryForExecute);
	}


	/**
	 * concatnate each query one after the other before executing
	 *
	 * @param array $fieldValues
	 */
	public function prepareRecordForInsert($fieldValues) {
		$addQueryPartFor = $this->getQueryPartForAdditionalFields($fieldValues);

		$queryArray = array();
		$queryArray['set'] = 'SET
			@pid = ' . $fieldValues['pid'] . ',
			@title = ' . $fieldValues['title'] . ',
			@type = ' . $fieldValues['type'] . ',
			@targetpid = ' . $fieldValues['targetpid'] . ',
			@content = ' . $fieldValues['content'] . ',
			@tags = ' . $fieldValues['tags'] . ',
			@params = ' . $fieldValues['params'] . ',
			@abstract = ' . $fieldValues['abstract'] . ',
			@language = ' . $fieldValues['language'] . ',
			@starttime = ' . $fieldValues['starttime'] . ',
			@endtime = ' . $fieldValues['endtime'] . ',
			@fe_group = ' . $fieldValues['fe_group'] . ',
			@tstamp = ' . $fieldValues['tstamp'] . ',
			@crdate = ' . $fieldValues['crdate'] . $addQueryPartFor['set'] . '
		;';
		$queryArray['execute'] = '
			EXECUTE insertStmt USING @pid, @title, @type, @targetpid, @content, @tags, @params, @abstract, @language, @starttime, @endtime, @fe_group, @tstamp, @crdate' . $addQueryPartFor['execute'] . ';
		';

		$this->tempArrayForInsertNewRecords[] = $queryArray;

		// if a defined maximum is reached...store temp records into database
		if(count($this->tempArrayForInsertNewRecords) >= $this->amountOfRecordsToSaveInMem) {
			$this->storeTempRecordsToIndex('insert');
		}
	}


	/**
	 * concatnate each query one after the other before executing
	 *
	 * @param array $fieldValues
	 */
	public function prepareRecordForUpdate($fieldValues) {
		$addQueryPartFor = $this->getQueryPartForAdditionalFields($fieldValues);

		$queryArray = array();
		$queryArray['set'] = 'SET
			@pid = ' . $fieldValues['pid'] . ',
			@title = ' . $fieldValues['title'] . ',
			@type = ' . $fieldValues['type'] . ',
			@targetpid = ' . $fieldValues['targetpid'] . ',
			@content = ' . $fieldValues['content'] . ',
			@tags = ' . $fieldValues['tags'] . ',
			@params = ' . $fieldValues['params'] . ',
			@abstract = ' . $fieldValues['abstract'] . ',
			@language = ' . $fieldValues['language'] . ',
			@starttime = ' . $fieldValues['starttime'] . ',
			@endtime = ' . $fieldValues['endtime'] . ',
			@fe_group = ' . $fieldValues['fe_group'] . ',
			@tstamp = ' . $fieldValues['tstamp'] . $addQueryPartFor['set'] . ',
			@uid = ' . $this->currentRow['uid'] . '
		';

		$queryArray['execute'] = '
			EXECUTE updateStmt USING @pid, @title, @type, @targetpid, @content, @tags, @params, @abstract, @language, @starttime, @endtime, @fe_group, @tstamp' . $addQueryPartFor['execute'] . ', @uid;
		';

		$this->tempArrayForUpdatingExistingRecords[] = $queryArray;

		// if a defined maximum is reached...store temp records into database
		if(count($this->tempArrayForUpdatingExistingRecords) >= $this->amountOfRecordsToSaveInMem) {
			$this->storeTempRecordsToIndex('update');
		}
	}


	/**
	 * This method decides which temp array will be saved
	 *
	 * @param string $which
	 */
	public function storeTempRecordsToIndex($which = 'both') {
		switch($which) {
			case 'insert':
				$this->insertRecordsToIndexer();
				break;
			case 'update':
				$this->updateRecordsInIndexer();
				break;
			case 'both':
			default:
				$this->insertRecordsToIndexer();
				$this->updateRecordsInIndexer();
		}
	}


	/**
	 * Update temporary saved records to db
	 */
	public function updateRecordsInIndexer() {
		foreach($this->tempArrayForUpdatingExistingRecords as $query) {
			$GLOBALS['TYPO3_DB']->sql_query($query['set']);
			$GLOBALS['TYPO3_DB']->sql_query($query['execute']);
		}
		$this->tempArrayForUpdatingExistingRecords = array();
	}


	/**
	 * Insert temporary saved records to db
	 */
	public function insertRecordsToIndexer() {
		foreach($this->tempArrayForInsertNewRecords as $query) {
			$GLOBALS['TYPO3_DB']->sql_query($query['set']);
			$GLOBALS['TYPO3_DB']->sql_query($query['execute']);
		}
		$this->tempArrayForInsertNewRecords = array();
	}


	/**
	 * try to find an allready indexed record
	 * This function also sets $this->currentRow
	 * parameters should be already fullQuoted. see storeInIndex
	 *
	 * TODO: We should create an index to column type
	 *
	 * @param integer $uid
	 * @param integer $pid
	 * @param string $type
	 * @param integer $language
	 * @return boolean true if record was found, false if not
	 */
	function checkIfRecordWasIndexed($uid, $pid, $type, $language) {
		$GLOBALS['TYPO3_DB']->sql_query('SET @orig_uid = ' . $uid . ', @pid = ' . $pid . ', @type = ' . $type . ', @language = ' . $language);
		$res = $GLOBALS['TYPO3_DB']->sql_query('EXECUTE searchStmt USING @orig_uid, @pid, @type, @language;');
		if($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
			if($this->currentRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				return true;
			} else {
				$this->currentRow = array();
				return false;
			}
		} else {
			$this->currentRow = array();
			return false;
		}
	}


	/**
	 * try to find an allready indexed record
	 * This function also sets $this->currentRow
	 * parameters should be already fullQuoted. see storeInIndex
	 *
	 * TODO: We should create an index to column type
	 *
	 * @param integer $type
	 * @param integer $hash
	 * @return boolean true if record was found, false if not
	 */
	function checkIfFileWasIndexed($type, $hash) {
		// Query DB if record already exists
		$res = $GLOBALS['TYPO3_DB']->sql_query( 'SELECT * FROM tx_kesearch_index WHERE ' . 'type = ' . $type . ' AND hash = ' . $hash . ' LIMIT 1');
		if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
			if ($this->currentRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				return true;
			} else {
				$this->currentRow = array();
				return false;
			}
		} else {
			$this->currentRow = array();
			return false;
		}
	}


	/**
	 * Create fieldValues to save them in db later on
	 * sets some default values, too
	 *
	 * @param integer $storagepid
	 * @param string $title
	 * @param string $type
	 * @param string $targetpid
	 * @param string $content
	 * @param string $tags
	 * @param string $params
	 * @param string $abstract
	 * @param string $language
	 * @param integer $starttime
	 * @param integer $endtime
	 * @param string $fe_group
	 * @param array $additionalFields
	 */
	public function createFieldValuesForIndexing($storagepid, $title, $type, $targetpid, $content, $tags='', $params='', $abstract='', $language=0, $starttime=0, $endtime=0, $fe_group='', $additionalFields=array()) {
		$now = time();
		$fieldsValues = array(
			'pid' => intval($storagepid),
			'title' => $this->stripControlCharacters($title),
			'type' => $type,
			'targetpid' => $targetpid,
			'content' => $this->stripControlCharacters($content),
			'tags' => $tags,
			'params' => $params,
			'abstract' => $this->stripControlCharacters($abstract),
			'language' => intval($language),
			'starttime' => intval($starttime),
			'endtime' => intval($endtime),
			'fe_group' => $fe_group,
			'tstamp' => $now,
			'crdate' => $now,
		);

		// add all registered additional fields to field value
		// TODO: for no default is string. but in future it should select type automatically (string/int)
		foreach($this->additionalFields as $fieldName) {
			$fieldsValues[$fieldName] = '';
		}

		// merge filled additionalFields with ke_search fields
		if(count($additionalFields)) {
			$fieldsValues = array_merge($fieldsValues, $additionalFields);
		}

		// full quoting record. Average speed: 0-1ms
		$fieldsValues = $GLOBALS['TYPO3_DB']->fullQuoteArray($fieldsValues, 'tx_kesearch_index');

		return $fieldsValues;
	}


	/**
	 * check if there are errors found in record before storing to db
	 *
	 * @param integer $storagePid
	 * @param string $title
	 * @param string $type
	 * @param string $targetPid
	 */
	public function checkIfRecordHasErrorsBeforeIndexing($storagePid, $title, $type, $targetPid) {
		$errors = array();

		// check for empty values
		if (empty($storagePid)) $errors[] = 'No storage PID set';
		if (empty($type)) $errors[] = 'No type set';
		if (empty($targetPid)) $errors[] = 'No target PID set';

		// collect error messages if an error was found
		if (count($errors)) {
			$errormessage = '';
			$errormessage = implode(',', $errors);
			if (!empty($type)) $errormessage .= 'TYPE: ' . $type . '; ';
			if (!empty($targetPid)) $errormessage .= 'TARGET PID: ' . $targetPid . '; ';
			if (!empty($storagePid)) $errormessage .= 'STORAGE PID: ' . $storagePid . '; ';
			$this->indexingErrors[] = ' (' . $errormessage . ')';

			// break indexing and wait for next record to store
			return false;
		} else return true;
	}


	/**
	 * function getTag
	 *
	 * @param int $tagUid
	 * @param bool $clearText. If true returns the title of the tag. false return the tag itself
	 */
	public function getTag($tagUid, $clearText=false) {
		$fields = 'title, tag';
		$table = 'tx_kesearch_filteroptions';
		$where = 'uid = "' . intval($tagUid) . '" ';
		$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table, 0);
		$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table, 0);

		$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			$fields,
			$table,
			$where
		);

		if ($clearText) {
			return $row['title'];
		} else {
			return $row['tag'];
		}
	}


	/**
	 * Strips control characters
	 *
	 * @param string the content to sanitize
	 * @return string the sanitized content
	 * @see http://forge.typo3.org/issues/34808
	 */
	public function stripControlCharacters($content) {
			// Printable utf-8 does not include any of these chars below x7F
		return preg_replace('@[\x00-\x08\x0B\x0C\x0E-\x1F]@', ' ', $content);
	}


	/**
	 * this function returns all indexer configurations found in DB
	 * independant of PID
	 */
	public function getConfigurations() {
		$fields = '*';
		$table = 'tx_kesearch_indexerconfig';
		$where = '1=1 ';
		$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields($table);
		$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table);
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, $where);
	}
}