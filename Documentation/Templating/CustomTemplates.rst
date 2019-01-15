.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _templatingCustomTemplates:

Use your own Templates
======================

In order to use your own fluid templates, please set the path to your templates in the typoscript *constants*.

It's good practice to put the templates in a dedicated "site" package (an extension which holds all your
templates, configuration and css files).

For example:

.. code-block:: none

	plugin.tx_kesearch.templateRootPath = EXT:mysite/Resources/Private/Templates/ke_search/
	plugin.tx_kesearch.partialRootPath = EXT:mysite/Resources/Private/Partials/ke_search/
	plugin.tx_kesearch.layoutRootPath = EXT:mysite/Resources/Private/Layouts/ke_search/

You can use the *Constants Editor* to set the paths to your templates, partials and layouts.

.. image:: ../Images/Templating/templating-constants.png

