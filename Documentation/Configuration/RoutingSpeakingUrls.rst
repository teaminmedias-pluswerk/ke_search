.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _configuration-routing-speaking-urls:

Routing (Speaking URLs)
=======================

Speaking URLs (for TYPO3 9 and greater) can be achieved by adding a routeEnhancer configuration to the site configuration
as shown in the example below.

Speaking URLs can be configured for the search word, for filters, for the sorting and for the pagination.

Example URL
...........

*https://example.org/search/score/desc/0/1/page/syscat80///contentexample/search+word*

("search" in this example is the page slug, not part of the route enhancer for ke_search)

Notes
.....

Adjust the values for the parameter "sortByField" as it fits your needs (you may have different fields to sort by in
your website).

For filters of type "select" and "textlink" you need one rule *per filter*.

* The rule is defined by using "filter" + "_" + the UID of the filter.

For filters of type "checkbox" (multi-select filters) you need one rule *per filter option*.

* The rules are defined by using filter + "_" + the UID of the filter + "_" + the UID of the filter option.

Unfortunately this will render your URL very long if you have many filter options. Maybe there will be
a better solution in the future.

Search words will be url encoded (eg. "schön" will become "sch%25C3%25B6n").


Example Site configuration (config.yaml)
........................................

This is an example for a site configuration. Please adjust the filter UIDs (like in "filter_13" where "13" is the UID
of the filter) and the filter option UIDs (like in "filter_3_267" where "267" is the UID of the filter option).

.. code-block:: none

    routeEnhancers:
      KeSearch:
        type: Plugin
        routePath: '{sortByField}/{sortByDir}/{resetFilters}/{page}/{filter_13}/{filter_14}/{filter_3_267}/{filter_3_273}/{filter_3_278}/{sword}'
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
          sword: ''
        requirements:
          sortByField: '(score|title|customranking)?'
          sortByDir: '(asc|desc)?'
          resetFilters: '[0-9]?'
          page: '\d+'
          filter_13: '[0-9a-zA-Z]*'
          filter_14: '[0-9a-zA-Z]*'
          filter_3_267: '[0-9a-zA-Z]*'
          filter_3_273: '[0-9a-zA-Z]*'
          filter_3_278: '[0-9a-zA-Z]*'
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
          filter_13:
            type: 'UrlEncodeMapper'
          filter_14:
            type: 'UrlEncodeMapper'
          filter_3_267:
            type: 'UrlEncodeMapper'
          filter_3_273:
            type: 'UrlEncodeMapper'
          filter_3_278:
            type: 'UrlEncodeMapper'
          sword:
            type: 'UrlEncodeMapper'

Templates changes
.................

If you are upgrading from ke_search 3.3.1 or below and you are using your own templates, you will have to do a few
adjustments to the templates as shown below.

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


Resources/Private/Partials/Filters/Checkbox.html
------------------------------------------------

* Change the "name" attribute of the options

.. code-block:: none

    <input type="checkbox" name="{option.key}" id="{option.id}" value="{option.tag}" {f:if(condition: '{option.selected}', then: ' checked="checked"')} {f:if(condition: '{option.disabled}', then: 'disabled = "disabled"')} />
