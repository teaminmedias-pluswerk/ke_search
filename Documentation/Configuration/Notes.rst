.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _configurationNotes:

Notes
=====

Notes on typoscript and flexform settings
-----------------------------------------

Each property in Flexform overwrites the property defined by TypoScript.

Each property has stdWrap-properties.

With the following TypoScript, you can define the result page:

.. code-block:: none

	plugin.tx_kesearch_pi1.resultPage = 9

or you can define the resultpage with help of an URL param if you want:

.. code-block:: none

	plugin.tx_kesearch_pi1.resultPage.data = GP:tx_kesearch_pi1|resultPage

Notes on typoscript and extension manager settings
--------------------------------------------------

In the extension manager you can define basic options like the minimal length of searchwords.

You can overwrite this configuration in your page typoscript setup:

.. code-block:: none

	ke_search_premium.extconf.override.searchWordLength = 3

or

.. code-block:: none

	ke_search_premium.extconf.override.enableSphinxSearch = 0
