.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _filtertypes:

Filter Types
============

There are three different types for filters:

Selectbox
~~~~~~~~~

This options renders a dropdown selectbox.

List
~~~~

This option renders link for each filter option. The list is put into a box which can be expanded. You can
define if this list should be automatically expanded or not. You can define an additional CSS class in order to
have a rendering that fits your website (smaller box, larger box).

Checkbox (multiselect, OR)
~~~~~~~~~~~~~~~~~~~~~~~~~~

This mode has the same settings as the list mode. But there is an additional setting to mark all
checkboxes as default. In this mode all filter options will be combined with logical "OR" in the search query.

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

No check is done. All filter options will be displayed, whether they have results or not.

