<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Pluswerk
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 * @author    Armin Vieweg
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class tx_kesearch_indexer_filetypes_xlsx extends tx_kesearch_indexer_types_file implements tx_kesearch_indexer_filetypes
{
    /**
     * class constructor
     */
    public function __construct()
    {
        // without overwriting __construct, the parent class would expect one param ($pObj)
        // which occures exception in Classes/indexer/types/class.tx_kesearch_indexer_types_file.php:224 (makeInstance)
        // may break with more strict php settings
    }

    /**
     * get Content of XLSX file
     * @param string $file
     * @return string The extracted content of the file
     */
    public function getContent($file)
    {
        /** @var \TeaminmediasPluswerk\KeSearch\Utility\OoxmlConversion $reader */
        $reader = GeneralUtility::makeInstance(\TeaminmediasPluswerk\KeSearch\Utility\OoxmlConversion::class, $file);
        return trim($reader->convertToText());
    }
}
