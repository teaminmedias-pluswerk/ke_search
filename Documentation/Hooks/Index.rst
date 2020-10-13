.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _hooks:

Hooks
=====

ke_search includes a lot of hooks you can use to include your own code and customize the behaviour of the extension.

modifyPagesIndexEntry
	Use this hook to modify the page data just before it will be saved into database.

modifyNewsIndexEntry
	Use this hook to modify the tt_news data just before it will be saved into database.

modifyAddressIndexEntry
	Use this hook to modify the tt_address data just before it will be saved into database.

modifyFilterOptions
	Use this hook to modify your filter options for type “select”, e.g. for adding special options, labels, css classes or to preselect an option.

modifyFilterOptionsArray
	Use this hook to modify your filter options, independent from filter type, e.g. for adding special options, css classes or to preselect an option.

initials
	Change any variable while initializing the plugin.

modifyFlexFormData
	Access and modify all returned values of ke_search-Flexform.

customFilterRenderer
	You can write your own filter rendering function using this hook. You will have to add your custom filter type to TCA options array. See chapter “Custom filter rendering” for further information.

registerIndexerConfiguration
	Use this hook for registering your custom indexer configuration in TCA. See chapter “:ref:`Write your own custom indexer! <customIndexer>`” for further information.

registerAdditionalFields
	This hook is important if you have extended the indexer table with your own columns.

renderPagebrowserInit
	Hook for third party pagebrowsers or for modification of build in browser, if the hook return content then return that content.

pagebrowseAdditionalMarker
	Hook for additional markers in pagebrowse.

getLimit
	Hook for third party pagebrowsers or for modification $this->pObj->piVars['page'] parameter.

modifyResultList
	Hook for adding new markers to the result list

fileReferenceTypes
	Hook for adding third party file previews

