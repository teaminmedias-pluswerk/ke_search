<?php

namespace TeaminmediasPluswerk\KeSearch\Indexer\Filetypes;

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

use TeaminmediasPluswerk\KeSearch\Indexer\Types\File;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 * @author    Stefan Froemken
 * @author    Lukas Kamber
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class Doc extends File implements FileIndexerInterface
{

    public $extConf = array(); // saves the configuration of extension ke_search_hooks
    public $app = array(); // saves the path to the executables
    public $isAppArraySet = false;

    /**
     * class constructor
     *
     * @param \TeaminmediasPluswerk\KeSearch\Indexer\IndexerRunner $pObj
     */
    public function __construct($pObj)
    {
        // get extension configuration of ke_search
        $this->extConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ke_search');

        // check if path to catdoc is correct
        if ($this->extConf['pathCatdoc']) {
            $pathCatdoc = rtrim($this->extConf['pathCatdoc'], '/') . '/';

            $exe = Environment::isWindows() ? '.exe' : '';
            if (is_executable($pathCatdoc . 'catdoc' . $exe)) {
                $this->app['catdoc'] = $pathCatdoc . 'catdoc' . $exe;
                $this->isAppArraySet = true;
            } else {
                $this->isAppArraySet = false;
            }
        } else {
            $this->isAppArraySet = false;
        }

        if (!$this->isAppArraySet) {
            $errorMessage = 'The path to catdoctools is not correctly set in the '
                . 'extension manager configuration. You can get the path with "which catdoc".';
            $pObj->logger->error($errorMessage);
            $this->addError($errorMessage);
        }
    }

    /**
     * get Content of DOC file
     * @param string $file
     * @return string The extracted content of the file
     */
    public function getContent($file)
    {
        // create the tempfile which will contain the content
        $tempFileName = GeneralUtility::tempnam('doc_files-Indexer');

        // Delete if exists, just to be safe.
        @unlink($tempFileName);

        // generate and execute the pdftotext commandline tool
        $fileEscaped = CommandUtility::escapeShellArgument($file);
        $cmd = "{$this->app['catdoc']} -s8859-1 -dutf-8 $fileEscaped > $tempFileName";
        CommandUtility::exec($cmd);

        // check if the tempFile was successfully created
        if (@is_file($tempFileName)) {
            $content = GeneralUtility::getUrl($tempFileName);
            unlink($tempFileName);
        } else {
            return false;
        }

        // check if content was found
        if (strlen($content)) {
            return $content;
        } else {
            return false;
        }
    }
}
