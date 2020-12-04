.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _configuration-routing-speaking-urls:

Routing (Speaking URLs)
=======================

Speaking URLs (for TYPO3 9 and greater) can be achieved as follows.

Adjust the values for the parameter "sortByField" as it fits your needs.

Note: Routing for filters is currently not available because filters use multi-dimensional piVars which is not supported
by the "Plugin" route enhancer.

Site configuration (config.yaml)
................................

Add this to your site configuration.

.. code-block:: none

    routeEnhancers:
      KeSearch:
        type: Plugin
        routePath: '{sortByField}/{sortByDir}/{resetFilters}/{page}/{sword}'
        namespace: 'tx_kesearch_pi1'
        defaults:
          sortByField: 'score'
          sortByDir: 'desc'
          resetFilters: '0'
          page: '1'
          sword: ''
        requirements:
          sortByField: '(score|title|customranking)?'
          sortByDir: '(asc|desc)?'
          resetFilters: '[0-9]?'
          page: '\d+'
        aspects:
          sortByField:
            type: 'StaticValueMapper'
            map:
              score: 'score'
              customranking: 'customranking'
              title: 'title'
          sortByDir:
            type: 'StaticValueMapper'
            map:
              asc: 'asc'
              desc: 'desc'
          resetFilters:
            type: 'StaticRangeMapper'
            start: '0'
            end: '1'
          page:
            type: 'StaticRangeMapper'
            start: '1'
            end: '99'
          sword:
            type: 'UrlEncodeMapper'

Templates
.........

If you are upgrading from ke_search 3.3.1 or below and you added your own templates, you will have to to a few
adjustments to the templates.

Resources/Private/Templates/SearchForm.html
-------------------------------------------

* Add the snippet to rewrite the url to the beginning of the form
* Add conditions to the hidden fields

.. code-block:: none

    <f:format.raw><script type="text/javascript">history.replaceState(null,'','</f:format.raw>{f:uri.page(addQueryString:'1', addQueryStringMethod:'GET')}<f:format.raw>');</script></f:format.raw>

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
--------------------------------------------------

* Switch to "GET" method

.. code-block:: none

    <f:if condition="{pagination.previous}">
        <li>
            <f:link.page additionalParams="{tx_kesearch_pi1: {page: pagination.previous}}" addQueryString="1" addQueryStringMethod="GET" class="prev">{f:translate(key: 'LLL:EXT:ke_search/Resources/Private/Language/locallang_searchbox.xml:pagebrowser_prev')}</f:link.page>
        </li>
    </f:if>
    <f:for each="{pagination.pages}" as="page">
        <li>
            <f:link.page additionalParams="{tx_kesearch_pi1: {page: page}}" addQueryString="1" addQueryStringMethod="GET" class="{f:if(condition: '{page} == {pagination.currentPage}', then: 'current')}">{page}</f:link.page></li>
    </f:for>
    <f:if condition="{pagination.next}">
        <li>
            <f:link.page additionalParams="{tx_kesearch_pi1: {page: pagination.next}}" addQueryString="1" addQueryStringMethod="GET" class="next">{f:translate(key: 'LLL:EXT:ke_search/Resources/Private/Language/locallang_searchbox.xml:pagebrowser_next')}</f:link.page>
        </li>
    </f:if>
