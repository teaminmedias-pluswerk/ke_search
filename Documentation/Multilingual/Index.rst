.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _multilangual:

Multilangual support
====================

ke_search has multilingual support in a way that

* if one searches in a specific language, the results will only be shown for that language.
* filters can be translated and be shown in the respective language.

Indexing content in different languages
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

All available langauges will be detected automatically and will be indexed.

Translating search result pages
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

On the search result page, insert the ke_search plugins in the translated page you just created. You can use the
function "copy default content elements". You can leave the configuration as it has been copied from your default language.

Translating filters
~~~~~~~~~~~~~~~~~~~

In order to use the multilangual feature for filters you'll have to

Create page translations
	Create alternative page languages for the storage folder where the index and filters are stored and
	for your search result page. You can do that with help of the list module you by creating a new record called
	"Alternative Page language" or with the page module by using the function "Make new translation of this page".

Translate filters and filter options
    Now you can translate the filters and filteroptions to the new language. Note: In TYPO3 version 9 and below you will
    have to activate “Localization view”-Checkbox in list module.