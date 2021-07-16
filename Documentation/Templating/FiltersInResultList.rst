.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _filtersInResultList:

Render filters in result list
=============================

Since version 3.9.0 it is possible to show filters also in the result list plugin which gives you more flexibility
in placing them in relation to the result list.

This is useful if you want to show e.g. the filters in the right-hand side and only if they are present.

HowTo
~~~~~
Create a `Resources/Private/Partials/FiltersForm.html` which is a modification of the `Resources/Private/Partials/Filters.html` which looks like:

.. code-block:: none

    <f:for each="{filters}" as="filter">
        <f:switch expression="{filter.rendertype}">
            <f:case value="select"><f:render partial="Filters/Select" arguments="{conf: conf, filter: filter}" /></f:case>
            <f:case value="checkbox"><f:render partial="Filters/Checkbox" arguments="{conf: conf, filter: filter}" /></f:case>
        </f:switch>
    </f:for>

Add a `Resources/Private/Partials/FiltersResults.html` which contains:

.. code-block:: none

    <f:for each="{filters}" as="filter">
        <f:switch expression="{filter.rendertype}">
            <f:case value="list"><f:render partial="Filters/List" arguments="{conf: conf, filter: filter}" /></f:case>
            <f:case value="custom"><f:format.raw>{filter.rawHtmlContent}</f:format.raw></f:case>
        </f:switch>
    </f:for>

In `Resources/Private/Templates/ResultList.html` include:

.. code-block:: none

    <f:if condition="{filters}">
        <div class="filters filtersResults">
            <f:render partial="FiltersResults" arguments="{conf: conf, numberofresults: numberofresults, resultrows: resultrows, filters: filters}" />
        </div>
    </f:if>

And in `Resources/Private/Templates/SearchForm.html` include:

.. code-block:: none

    <f:if condition="{filters}">
        <div class="filters filtersForm">
            <f:render partial="FiltersForm" arguments="{conf: conf, numberofresults: numberofresults, resultrows: resultrows, filters: filters}" />
        </div>
    </f:if>