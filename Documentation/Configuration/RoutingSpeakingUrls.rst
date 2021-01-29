.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _configuration-routing-speaking-urls:

Routing (Speaking URLs)
=======================

Speaking URLs (for TYPO3 9 and greater) can be achieved by adding a routeEnhancer configuration to the site
configuration (see example below).

Speaking URLs can be configured for the search word, for filters, for the sorting and for the pagination.

There are two mappers provided to map tags to slugs (KeSearchTagToSlugMapper) and to use the search word as part of
the route (KeSearchUrlEncodeMapper).

Example URL
...........

*https://example.org/search-page/score/desc/0/1/news-cat-1/search+word*

Notes
.....

* Adjust the values for the parameter "sortByField" as it fits your needs (you may have different fields to sort by in
  your website).

* For filters of type "select" and "textlink" you need one rule *per filter*. The rule is defined by
  using "filter" + "_" + the UID of the filter.

* For filters of type "checkbox" (multi-select filters) you need one rule *per filter option*. The rules are
  defined by using filter + "_" + the UID of the filter + "_" + the UID of the filter option. Unfortunately this may
  render your URL very long if you have many filter options. Maybe there will be a better solution in the future.

* Search words will be url encoded (eg. "schön" will become "sch%25C3%25B6n").

* The StaticMappableAspectInterface can also be used for filters. In this case not the slug is used for
  URL generation but the tag. This may be useful in cases where you use the same tag for different filter options
  or if you have huge amounts of tags and want to improve performance (the KeSearchTagToSlugMapper accesses the database
  once for each routing parameter on every request).

Examples
........

These are examples for the site configuration file (config.yaml).

You need to adjust the filter UIDs (like in "filter_13" where "13" is the UID
of the filter) and the filter option UIDs (like in "filter_3_267" where "267" is the UID of the filter option).


Simple example
~~~~~~~~~~~~~~

This is a simple example with one filter and the search word mapped to a speaking URL.

If only a filter is given, this will give URLs like

*https://www.example.org/search-page/filter-option*

If additionally a searchword is given, this will result in

*https://www.example.org/search-page/filter-option/score/desc/0/1/search-word*

.. code-block:: none

    routeEnhancers:
      KeSearch:
        type: Plugin
        routePath: '{filter_13}/{sortByField}/{sortByDir}/{resetFilters}/{page}/{sword}'
        namespace: 'tx_kesearch_pi1'
        defaults:
          sortByField: 'score'
          sortByDir: 'desc'
          resetFilters: '0'
          page: '1'
          sword: ''
          filter_13: ''
        requirements:
          sortByField: '(score|title|customranking)?'
          sortByDir: '(asc|desc)?'
          resetFilters: '[0-9]?'
          page: '\d+'
          filter_13: '[0-9a-zA-Z-]*'
        aspects:
          sortByField:
            type: StaticValueMapper
            map:
              score: 'score'
              customranking: 'customranking'
              title: 'title'
          sortByDir:
            type: StaticValueMapper
            map:
              asc: 'asc'
              desc: 'desc'
          resetFilters:
            type: StaticRangeMapper
            start: '0'
            end: '1'
          page:
            type: StaticRangeMapper
            start: '1'
            end: '99'
          filter_13:
            type: KeSearchTagToSlugMapper
          sword:
            type: KeSearchUrlEncodeMapper

Full example
~~~~~~~~~~~~

This is an example for a site configuration which adds multiple filters to the routing configuration. Filter no. 3 is
a "checkbox" filter, therefore each filter option has to be a configured individually.

.. code-block:: none

    routeEnhancers:
      KeSearch:
        type: Plugin
        routePath: '{sortByField}/{sortByDir}/{resetFilters}/{page}/{filter_14}/{filter_13}/{filter_3_267}/{filter_3_273}/{filter_3_278}/{filter_3_283}/{sword}'
        namespace: 'tx_kesearch_pi1'
        defaults:
          sortByField: 'score'
          sortByDir: 'desc'
          resetFilters: '0'
          page: '1'
          filter_13: ''
          filter_14: ''
          filter_3_267: ''
          filter_3_273: ''
          filter_3_278: ''
          filter_3_283: ''
          sword: ''
        requirements:
          sortByField: '(score|title|customranking)?'
          sortByDir: '(asc|desc)?'
          resetFilters: '[0-9]?'
          page: '\d+'
          filter_13: '[0-9a-zA-Z-]*'
          filter_14: '[0-9a-zA-Z-]*'
          filter_3_267: '[0-9a-zA-Z-]*'
          filter_3_273: '[0-9a-zA-Z-]*'
          filter_3_278: '[0-9a-zA-Z-]*'
          filter_3_283: '[0-9a-zA-Z-]*'
        aspects:
          sortByField:
            type: StaticValueMapper
            map:
              score: 'score'
              customranking: 'customranking'
              title: 'title'
          sortByDir:
            type: StaticValueMapper
            map:
              asc: 'asc'
              desc: 'desc'
          resetFilters:
            type: StaticRangeMapper
            start: '0'
            end: '1'
          page:
            type: StaticRangeMapper
            start: '1'
            end: '99'
          filter_13:
            type: KeSearchTagToSlugMapper
          filter_14:
            type: KeSearchTagToSlugMapper
          filter_3_267:
            type: KeSearchTagToSlugMapper
          filter_3_273:
            type: KeSearchTagToSlugMapper
          filter_3_278:
            type: KeSearchTagToSlugMapper
          filter_3_283:
            type: KeSearchTagToSlugMapper
          sword:
            type: KeSearchUrlEncodeMapper

Upgrading
.........

If you are upgrading from ke_search 3.3.1 or below and you are using your own templates, you will have to do a few
adjustments to the templates as shown below.

Resources/Private/Templates/SearchForm.html
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

* Add the kesearch namespace to the beginning of the file
* Add the snippet to rewrite the url to the beginning of the form
* Add conditions to the hidden fields

.. code-block:: none

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
          xmlns:kesearch="http://typo3.org/ns/TeaminmediasPluswerk\KeSearch\ViewHelpers"
          data-namespace-typo3-fluid="true">

.. code-block:: none

		<f:comment> // Replace the URL with the speaking URL </f:comment>
		<f:format.raw><script type="text/javascript">history.replaceState(null,'','</f:format.raw><kesearch:link keepPiVars="1" uriOnly="1" /><f:format.raw>');</script></f:format.raw>

.. code-block:: none

    <f:if condition="{page}">
        <input id="kesearchpagenumber" type="hidden" name="tx_kesearch_pi1[page]" value="{page}" />
    </f:if>
    <input id="resetFilters" type="hidden" name="tx_kesearch_pi1[resetFilters]" value="0" />
    <f:if condition="{sortByField}">
        <input id="sortByField" type="hidden" name="tx_kesearch_pi1[sortByField]" value="{sortByField}" />
    </f:if>
    <f:if condition="{sortByDir}">
        <input id="sortByDir" type="hidden" name="tx_kesearch_pi1[sortByDir]" value="{sortByDir}" />
    </f:if>

Resources/Private/Templates/Widget/Pagination.html
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

* Add the kesearch namespace to the beginning of the file
* Change the links using the kesearch:link viewhelper

.. code-block:: none

    <html xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
          xmlns:kesearch="http://typo3.org/ns/TeaminmediasPluswerk\KeSearch\ViewHelpers"
          data-namespace-typo3-fluid="true">

    <f:spaceless>
    <ul>
    <f:if condition="{pagination.previous}">
        <li>
            <kesearch:link piVars="{page: pagination.previous}" keepPiVars="1" class="prev">{f:translate(key: 'LLL:EXT:ke_search/Resources/Private/Language/locallang_searchbox.xml:pagebrowser_prev')}</kesearch:link>
        </li>
    </f:if>
    <f:for each="{pagination.pages}" as="page">
        <li>
            <kesearch:link piVars="{page: page}" keepPiVars="1" class="{f:if(condition: '{page} == {pagination.currentPage}', then: 'current')}">{page}</kesearch:link>
    </f:for>
    <f:if condition="{pagination.next}">
        <li>
            <kesearch:link piVars="{page: pagination.next}" keepPiVars="1" class="next">{f:translate(key: 'LLL:EXT:ke_search/Resources/Private/Language/locallang_searchbox.xml:pagebrowser_next')}</kesearch:link>
        </li>
    </f:if>
    </ul>
    </f:spaceless>


Resources/Private/Partials/Filters/Checkbox.html
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

* Change the "name" attribute of the options

.. code-block:: none

    <input type="checkbox" name="{option.key}" id="{option.id}" value="{option.tag}" {f:if(condition: '{option.selected}', then: ' checked="checked"')} {f:if(condition: '{option.disabled}', then: 'disabled = "disabled"')} />
