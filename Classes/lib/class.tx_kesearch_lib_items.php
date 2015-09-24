<?php
/***************************************************************
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
***************************************************************/


/**
 * Plugin 'Faceted search' for the 'ke_search' extension.
 *
 * @author	Stefan Froemken
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_lib_items {
	public function fillIndexerConfig(&$params, $pObj) {
		// hook for custom registration of further indexerConfigurations
		if(is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerIndexerConfiguration'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerIndexerConfiguration'] as $_classRef) {
				$_procObj = & TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($_classRef);
				$_procObj->registerIndexerConfiguration($params, $pObj);
			}
		}
	}
}