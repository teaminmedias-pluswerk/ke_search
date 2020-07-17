<?php
// re-use news category TCA, needs to stay in TCA/Overrides to make sure news TCA is loaded
if (TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('news')) {
    $GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['index_extnews_category_selection']['config']
        = $GLOBALS['TCA']['tx_news_domain_model_news']['columns']['categories']['config'];

    $GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['index_extnews_category_selection']['config']['MM_match_fields']['tablenames']
        = 'tx_kesearch_indexerconfig';
}
