<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Stefan Froemken 
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
 * ************************************************************* */

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 *
 * @author	Stefan Froemken 
 * @author	Lukas Kamber
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_indexer_filetypes_doc extends tx_kesearch_indexer_types_file implements tx_kesearch_indexer_filetypes {

	var $extConf = array(); // saves the configuration of extension ke_search_hooks
	var $app = array(); // saves the path to the executables
	var $isAppArraySet = false;

	/**
	 * class constructor
	 */
	public function __construct() {
		// get extension configuration of ke_search
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ke_search']);

		// check if path to catdoc is correct
		if ($this->extConf['pathCatdoc']) {
			$pathCatdoc = rtrim($this->extConf['pathCatdoc'], '/') . '/';

			$exe = (TYPO3_OS == 'WIN') ? '.exe' : '';
			if (is_executable($pathCatdoc . 'catdoc' . $exe)) {
				$this->app['catdoc'] = $pathCatdoc . 'catdoc' . $exe;
				$this->isAppArraySet = true;
			}
			else {
				$this->isAppArraySet = false;
			}
		}
		else {
			$this->isAppArraySet = false;
		}

		if (!$this->isAppArraySet) {
			$this->addError('The path to catdoctools is not correctly set in the extension manager configuration. You can get the path with "which catdoc".');
		}
	}

	/**
	 * get Content of DOC file
	 *
	 * @param string $file
	 * @return string The extracted content of the file
	 */
	public function getContent($file) {
		// create the tempfile which will contain the content
		if (TYPO3_VERSION_INTEGER >= 7000000) {
			$tempFileName = TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('doc_files-Indexer');
		} else {
			$tempFileName = t3lib_div::tempnam('doc_files-Indexer');
		}

		// Delete if exists, just to be safe.
		@unlink($tempFileName);

		// generate and execute the pdftotext commandline tool
		$cmd = $this->app['catdoc'] . ' -s8859-1 -dutf-8 ' . escapeshellarg($file) . ' > ' . escapeshellarg($tempFileName);
		if (TYPO3_VERSION_INTEGER >= 7000000) {
			TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd);
		} else {
			t3lib_utility_Command::exec($cmd);
		}

		// check if the tempFile was successfully created
		if (@is_file($tempFileName)) {
			if (TYPO3_VERSION_INTEGER >= 7000000) {
				$content = TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($tempFileName);
			} else {
				$content = t3lib_div::getUrl($tempFileName);
			}
			unlink($tempFileName);
		}
		else
			return false;

		// check if content was found
		if (strlen($content)) {
			return $content;
		}
		else
			return false;
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/indexer/filetypes/class.tx_kesearch_indexer_filetypes_doc.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ke_search/Classes/indexer/filetypes/class.tx_kesearch_indexer_filetypes_doc.php']);
}
?>