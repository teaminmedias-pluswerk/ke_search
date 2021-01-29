.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _linkviewhelper:

Link ViewHelper
===============

A link ViewHelper is provided to generate links to search result pages including a search word and filters.

Parameters
..........

* page (integer): Target page
* piVars (array): pivars (sword, filter[...], page, sortByField, sortByDir, resetFilters)
* resetFilters (array): Filters to reset
* keepPiVars (boolean): Should the piVars be kept?
* uriOnly (boolean): Returns only the uri.
* section (string): Anchor for links

Additionally the standard HTML universal attributes can be used (like class, dir, id, lanag, style, title, accesskey, tabindex, onclick).

Setting filters
...............

In order to use filters with the link ViewHelper you will need to know the filter UID, the tag of the filter option and (if you use a checkbox
filter) the UID of the filter option. Please see the example below.

Example
.......

.. code-block:: none

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
          xmlns:kesearch="http://typo3.org/ns/TeaminmediasPluswerk\KeSearch\ViewHelpers"
          data-namespace-typo3-fluid="true">

    <ul>
       <li><kesearch:link page="85" piVars="{sword: 'elephants'}" class="my-link-class">Search for elephants</kesearch:link></li>
       <li><kesearch:link page="85" piVars="{filter_13: 'page'}" class="my-link-class">Show all pages</kesearch:link></li>
       <li><kesearch:link page="85" piVars="{sword: 'elephants', filter_13: 'page'}" class="my-link-class">Search for elephants in pages</kesearch:link></li>
       <li><kesearch:link page="85" piVars="{sword: 'elephants', filter_13: 'page', filter_3_278: 'syscat92'}" class="my-link-class">Search for elephants in pages and use a checkbox filter</kesearch:link></li>
    </ul>
