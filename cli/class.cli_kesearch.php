<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2010 Andreas Kiefer <kiefer@kennziffer.com>
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

if (!defined('TYPO3_cliMode'))  die('You cannot run this script directly!');

class tx_kesearch_cli extends \TYPO3\CMS\Core\Controller\CommandLineController  {

	/**
	 * Constructor
	 */
    function tx_kesearch_cli () {

		// Running parent class constructor
			parent::__construct();

        // Setting help texts:
        $this->cli_help['name'] = 'ke_search Command Line Interface';
        $this->cli_help['synopsis'] = '###OPTIONS###';
        $this->cli_help['description'] = 'Start indexer for ke_search as CLI script';
        $this->cli_help['examples'] = '.../cli_dispatch.phpsh ke_search startIndexing';
        $this->cli_help['author'] = 'Andreas Kiefer, (c) 2010-2011';
    }

    /**
     * CLI engine
     *
     * @param    array        Command line arguments
     * @return    string
     */
    function cli_main($argv) {

		// make instance of indexer

        // get task (function)
        $task = (string)$this->cli_args['_DEFAULT'][1];

		// get extension configuration
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ke_search']);

		// switch between tasks
		switch ($task) {

			default:
				$this->cli_validateArgs();
				$this->cli_help();
				break;

			case 'startIndexing':

				$indexer  = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_indexer');
				$this->cli_echo(chr(10));
				$verboseMode = true;
				$cleanup = $this->extConf['cleanupInterval'];
				$response = $indexer->startIndexing($verboseMode, $this->extConf, 'CLI');
				$response = str_replace('<br /><br />', chr(10),$response);
				$response = str_replace('<br />', chr(10),$response);
				$response = strip_tags($response);
				$this->cli_echo($response.chr(10).chr(10));
				break;
		}

    }

}

// Call the functionality
$cleanerObj  = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_cli');
$cleanerObj->cli_main($_SERVER['argv']);