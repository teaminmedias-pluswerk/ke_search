<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Andreas Kiefer <andreas.kiefer@inmedias.de>
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

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Plugin 'Faceted search - searchbox and filters' for the 'ke_search' extension.
 *
 * @author	Andreas Kiefer <andreas.kiefer@inmedias.de>
 * @author	Christian Bülter <christian.buelter@inmedias.de>
 * @package	TYPO3
 * @subpackage	tx_kesearch
 */
class tx_kesearch_pi1 extends tx_kesearch_lib {

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     */
    protected $searchFormView;

	// Path to this script relative to the extension dir.
	var $scriptRelPath      = 'pi1/class.tx_kesearch_pi1.php';

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	string The content that is displayed on the website
	 */
	function main($content, $conf) {
		$this->ms = GeneralUtility::milliseconds();
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		// Configuring so caching is not expected. This value means that no cHash params are ever set.
		// We do this, because it's a USER_INT object!
		$this->pi_USER_INT_obj = 1;

		// initializes plugin configuration
		$this->init();

		// init template for pi1
		$this->initFluidTemplate();

		// hook for initials
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['initials'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['initials'] as $_classRef) {
				$_procObj = & GeneralUtility::getUserObj($_classRef);
				$_procObj->addInitials($this);
			}
		}

		// get content for searchbox
		$this->getSearchboxContent();

		// assign variables and do the rendering
		$this->searchFormView->assignMultiple($this->fluidTemplateVariables);
		$htmlOutput = $this->searchFormView->render();

		return $htmlOutput;
	}

	/**
	 * inits the standalone fluid template
	 */
	public function initFluidTemplate() {
		$this->searchFormView = GeneralUtility::makeInstance('TYPO3\\CMS\\Fluid\\View\\StandaloneView');
		$this->searchFormView->setPartialRootPaths([$this->conf['partialRootPath']]);
		$this->searchFormView->setLayoutRootPaths([$this->conf['layoutRootPath']]);
		$this->searchFormView->setTemplatePathAndFilename($this->conf['templateRootPath'] . 'SearchForm.html');

		// make settings available in fluid template
		$this->searchFormView->assign('conf', $this->conf);
		$this->searchFormView->assign('extConf', $this->extConf);
		$this->searchFormView->assign('extConfPremium', $this->extConfPremium);
	}
}
