.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _filters:

Filters
=======

ke_search comes with a faceted search feature which allows the website visitors to narrow down the search result list
by using filters.

You'll have to create „filters” and „filter options”. Filter options appear in the searchbox in the frontend
and at the same time you can set them as „tags” for pages (or other content) in the backend.

Example
-------

You have a website that's about cars. On a few pages, your content is about tires. You can now create a filter
named „Accessories“ and filter option named „Tires“. Now when your customer uses the search function,
she or he can narrow down the search to all pages marked with „Tires“. That does not mean, that „Tires“ must
be on that page as a word, but it's marked as „relevant for tires“ in the backend.

You have two possibilities to mark the relevant pages: Open the page properties and in the tab "Search" you
find your „Tags for faceted search“ use the function „Set tag for all children of this page” in filter record.

Faceted search setup
--------------------

Follow these steps to set up faceted search:

Create a filter
...............

Go to your search storage folder and use the list module to create a „filters” record. For each
category you want to use in your faceted search in the frontend you will have to create one filter.

.. image:: ../Images/Filters/filters-1.png

Create filter options
.....................

Add new Filter options. This is done using the IRRE technology: You can create new filter options inside the
filter. For each option you want to display in the frontend you will have to add one filter option.

.. image:: ../Images/Filters/filters-2.png

Tags
....

Tags are used internally to mark content as relevant for a certain filter option. You will have to choose a
tagname for each filter.

NOTE: You may freely choose a tag name, they're only used for internal purposes. But make sure the
tag you choose for each filter option is unique!

Important: The tag has to be at least four characters long and must not contain a dot.

If you use the option „Set tag for all children of this page” the tag will be set automatically to
the subpages of the page you set while indexing that pages (you can select multiple pages).

With the excludeoption you can prevent childpages to be tagged automatically.

If you do not want to set the tag for pages automatically, you can choos to set the tag on each page manually in
the tab "Search" in the page properties.

.. image:: ../Images/Filters/filters-3.png

The tags will be added to the index entry of that page at the time the indexer reads that page
and writes its content to the index.

NOTE: You will first have to create at least one “filter” and one “filter option” to see any items in this list!

If you have a multilingual website, the tagging can be done only in the main language. But you can translate the
filter options so that they will be visible in the frontend in the correct translation.

The filter options are coming from the whole system, no matter on what page you created them.

If you have more than one search plugin, you may want to restrict the filter options displayed here to a certain folder.
You can do this by adding this to your PAGE-TSConfig, where 1234 is the uid of your folder:

.. code-block:: none

	tx_kesearch.filterStorage = 1234

Add filter to search plugin
...........................

Open your search plugin and select the filters you want to display in the tab „filter“.

.. image:: ../Images/Filters/filters-4.png

The filter will then be displayed in the frontend.

.. image:: ../Images/Filters/filters-5.png

Note on indexing
................

The tags will be applied after the next indexing. So whenever you change the filters, re-index after that.

Filter types
............

There are three different types for filters:

Selectbox
~~~~~~~~~

This options renders a dropdown selectbox.

List
~~~~

This option renders textlink for each filter option. The list is put into a box which can be expanded. You can
define if this list should be automatically expanded or not. You can define an additional CSS class in order to
have a rendering that fits your website (smaller box, larger box).

Checkbox (multiselect OR)
~~~~~~~~~~~~~~~~~~~~~~~~~

This mode has the same settings as the list mode. But there is an additional setting to mark all
checkboxes as default. In this mode all filteroptions will be connected as OR in the search query!

Hidden filters
..............

With this option you can add filter which are not changeable in the frontend (using the field "preselected
filter options"). You can use this in order to reduce the result list to results matching a certain filter option.
For example on a website with pages and news you can present a list of all indexed news.

Availability check for filter options
.....................................

In the "Filter" settings you have the possibility to select an "Availability check for filter options". You may
select one of the following options:


Check in condition to other filters
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Only those filter options in each filter will be displayed which would give results in combination with
the already selected filters. These are the filter options which occur in the result list. That means, it cannot happen
that selecting a filter option leads to a "no results found" message.

no check
~~~~~~~~

No check is done. All filter options will be displayed, wether they lead to a hit or not.

Setting tags for pages

In the page properties you can set the tags which correspond with the filter options displayed in the frontend. The tags will be added to the index entry of that page at the time the indexer reads that page and writes its content to the index.

NOTE: You will first have to create at least one “filter” and one “filter option” in order to see any items in this list!

Einstellen der Schlagwörter
If you have a multilingual website, the tagging can be done only in the main language. But you can translate the filter options so that they will be visible in the frontend in the correct translation. See „multilingual support” for more information.

The filter options are coming from the whole system, no matter on what page you created them.

If you have more than one ke_search instance, you may want to restrict the filter options displayed here to a certain folder.
You can do this by adding this to your PAGE-TSConfig, where 1234 is the uid of your folder:

