<?php

use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Abstract class for custom indexer.
 *
 * @package    TYPO3
 * @subpackage tx_kesearch
 * @author Wolfram Eberius <edrush@posteo.de>
 */
abstract class tx_kesearch_hooks_abstract_custom_indexer
{
    /**
     * objectManager.
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance('TYPO3\CMS\\Extbase\Object\ObjectManager');
    }

    abstract protected function getType();

    abstract protected function getName();

    public function registerIndexerConfiguration(&$params, $pObj)
    {
        $extensionKey = tx_kesearch_helper::getExtensionKeyByObject($this);
        $extensionRelPath = ExtensionManagementUtility::extRelPath($extensionKey);

        $iconPath = is_readable($extensionRelPath . 'ext_icon.png') ? $extensionRelPath . 'ext_icon.png' : $extensionRelPath . 'ext_icon.gif';
        $newIndexer = array(
            $this->getName(),
            $this->getType(),
            $iconPath,
        );
        $params ['items'] [] = $newIndexer;

        // enable "sysfolder" field
        $GLOBALS ['TCA'] ['tx_kesearch_indexerconfig'] ['columns'] ['sysfolder'] ['displayCond'] .= ',' . $this->getType();
    }

    protected function storeInIndex(tx_kesearch_lib_vo_index_entry $indexEntry, \tx_kesearch_indexer $indexerObject, $indexerConfig)
    {
        $indexerObject->amountOfRecordsToSaveInMem = 999999;

        return $indexerObject->storeInIndex($indexerConfig ['storagepid'],         // storage PID
            $indexEntry->getTitle(),         // record title
            $indexerConfig ['type'],         // content type
            $indexerConfig ['targetpid'],         // target PID: where is the single view?
            $indexEntry->getContent(),         // indexed content, includes the title (linebreak after title)
            $indexEntry->getTags(),         // tags for faceted search
            $indexEntry->getParams(),         // typolink params for singleview
            $indexEntry->getAbstract(),         // abstract; shown in result list if not empty
            $indexEntry->getLanguageUid(),         // language uid
            '',         // starttime
            '',         // endtime
            '',         // fe_group
            false,         // debug only?
            $indexEntry->getAdditionalFields()); // additionalFields
    }

    protected function getReport($indexerConfig, $objects)
    {
        $count = count($objects);

        return '<p><b>Indexer "' . $indexerConfig ['title'] . '":</b></br> ' . $count . ' elements have been indexed.</b></p>';
    }

}
