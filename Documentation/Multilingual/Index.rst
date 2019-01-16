.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _multilangual:

Multilangual support
====================

ke_search has multilingual support:

* If one searches in a specific language, the results will only be shown for that language.
* Filters can be translated and be shown in the respective language.

In order to use the multilangual feature you'll have to

Create page translations
	Create alternative page languages for the storage folder where the index and filters are stored and
	for your search result page. You can do that with help of the list module you by creating a new record called
	"Alternative Page language" or with the page module by using the function "Make new translation of this page".

Translate filters and filter options
	Now you can activate “Localization view”-Checkbox in list module to translate the filters and filteroptions to the new language.

Localize the ke_search plugins
	On the search result page, insert the ke_search plugins in the translated page you just created. You can use the
	function "copy default content elements". You can leave the configuration as it has been copied from your default language.

After that step please restart the indexer process.
