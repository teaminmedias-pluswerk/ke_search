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
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_indexer_filetypes_pdf extends tx_kesearch_indexer_types_file implements tx_kesearch_indexer_filetypes {

	var $extConf = array(); // saves the configuration of extension ke_search_hooks
	var $app = array(); // saves the path to the executables
	var $isAppArraySet = false;

	/**
	 * class constructor
	 */
	public function __construct() {
		// get extension configuration of ke_search_hooks
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ke_search']);

		// check if needed system tools pdftotext and pdfinfo exist
		if ($this->extConf['pathPdftotext']) {
			$pathPdftotext = rtrim($this->extConf['pathPdftotext'], '/') . '/';
			$pathPdfinfo = rtrim($this->extConf['pathPdfinfo'], '/') . '/';

			$exe = (TYPO3_OS == 'WIN') ? '.exe' : '';
			if ((is_executable($pathPdftotext . 'pdftotext' . $exe) && is_executable($pathPdfinfo . 'pdfinfo' . $exe))) {
				$this->app['pdfinfo'] = $pathPdfinfo . 'pdfinfo' . $exe;
				$this->app['pdftotext'] = $pathPdftotext . 'pdftotext' . $exe;
				$this->isAppArraySet = true;
			} else {
				$this->isAppArraySet = false;
			}
		} else {
			$this->isAppArraySet = false;
		}

		if (!$this->isAppArraySet) {
			$this->addError('The path to pdftools is not correctly set in the extension manager configuration. You can get the path with "which pdfinfo" or "which pdftotext".');
		}
	}

	/**
	 * get Content of PDF file
	 *
	 * @param string $file
	 * @return string The extracted content of the file
	 */
	public function getContent($file) {
		$this->fileInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_kesearch_lib_fileinfo');
		$this->fileInfo->setFile($file);

		// get PDF informations
		if (!$pdfInfo = $this->getPdfInfo($file))
			return false;

		// proceed only of there are any pages found
		if (intval($pdfInfo['pages']) && $this->isAppArraySet) {

			// create the tempfile which will contain the content
			$tempFileName = TYPO3\CMS\Core\Utility\GeneralUtility::tempnam('pdf_files-Indexer');

			// Delete if exists, just to be safe.
			@unlink($tempFileName);

			// generate and execute the pdftotext commandline tool
			$cmd = $this->app['pdftotext'] . ' -enc UTF-8 -q ' . escapeshellarg($file) . ' ' . escapeshellarg($tempFileName);

			TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd);

			// check if the tempFile was successfully created
			if (@is_file($tempFileName)) {
				$content = TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($tempFileName);
				unlink($tempFileName);
			}
			else {
				$this->addError('Content for file ' . $file . ' could not be extracted. Maybe it is encrypted?');

				// return empty string if no content was found
				$content = '';
			}

			return $this->removeEndJunk($content);
		}
		else
			return false;
	}

	/**
	 * execute commandline tool pdfinfo to extract pdf informations from file
	 *
	 * @param string $file
	 * @return array The pdf informations as array
	 */
	public function getPdfInfo($file) {
		if ($this->fileInfo->getIsFile()) {
			if ($this->fileInfo->getExtension() == 'pdf' && $this->isAppArraySet) {
				$cmd = $this->app['pdfinfo'] . ' ' . escapeshellarg($file);
				\TYPO3\CMS\Core\Utility\CommandUtility::exec($cmd, $pdfInfoArray);
				$pdfInfo = $this->splitPdfInfo($pdfInfoArray);
				unset($pdfInfoArray);
				return $pdfInfo;
			}
			else
				return false;
		}
		else
			return false;
	}

	/**
	 * Analysing PDF info into a useable format.
	 *
	 * @param array Array of PDF content, coming from the pdfinfo tool
	 * @return array The pdf informations as array in a useable format
	 */
	function splitPdfInfo($pdfInfoArray) {
		$res = array();
		if (is_array($pdfInfoArray)) {
			foreach ($pdfInfoArray as $line) {
				$parts = explode(':', $line, 2);
				if (count($parts) > 1 && trim($parts[0])) {
					$res[strtolower(trim($parts[0]))] = trim($parts[1]);
				}
			}
		}
		return $res;
	}

	/**
	 * Removes some strange char(12) characters and line breaks that then to occur in the end of the string from external files.
	 *
	 * @param string String to clean up
	 * @return string Cleaned up string
	 */
	function removeEndJunk($string) {
		return trim(preg_replace('/[' . LF . chr(12) . ']*$/', '', $string));
	}

}