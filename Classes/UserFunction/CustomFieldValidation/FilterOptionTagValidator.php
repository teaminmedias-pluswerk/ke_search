<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2017 Armin Vieweg <armin.vieweg@pluswerk.ag>
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

namespace TeaminmediasPluswerk\KeSearch\UserFunction\CustomFieldValidation;


use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Validates given filter option tag. Checks length, which may not smaller than
 * basic.searchWordLength extension (and MySQL) setting.
 *
 * @package TeaminmediasPluswerk\KeSearch\UserFunction\CustomFieldValidation
 */
class FilterOptionTagValidator
{
    /**
     * PHP Validation to disallow leading numbers
     *
     * @param string $value
     * @return mixed|string Updated string, which fits the requirements
     */
    public function evaluateFieldValue($value)
    {
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ke_search']);
        $minLength = isset($extConf['searchWordLength']) ? (int) $extConf['searchWordLength'] : 4;

        if (strlen($value) < $minLength) {
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $message */
            $message = GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Messaging\FlashMessage::class,
                $this->translate('tag_too_short_message', [$value, $minLength]),
                $this->translate('tag_too_short'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR,
                true
            );

            /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
            $flashMessageService->getMessageQueueByIdentifier()->addMessage($message);
            return false;
        }
        return $value;
    }

    /**
     * JavaScript validation
     *
     * @return string javascript function code for js validation
     */
    public function returnFieldJs()
    {
        return 'return value;';
    }

    /**
     * Returns the translation of current language, stored in locallang_db.xml.
     *
     * @param string $key key in locallang_db.xml to translate
     * @param array $arguments optional arguments
     * @return string Translated text
     */
    protected function translate($key, array $arguments = [])
    {
        return \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
            'LLL:EXT:ke_search/Resources/Private/Language/locallang_mod.xml:' . $key,
            'KeSearch',
            $arguments
        );
    }
}
