.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _filters:

Filters / Faceted Search
========================

ke_search comes with a faceted search feature which allows the website visitors to narrow down the search result list
by using filters.

You'll have to create "filters" and "filter options". Filter options appear in the searchbox in the frontend
and at the same time you can set them as "tags" for pages (or other content) in the backend.

Examples
--------

Example "Car Website"
.....................

You have a website that's about cars. On some pages, your content is about tires, others are about "Accessories".
You can now create a filter named "Accessories" and filter option named "Tires". Now when your customer
uses the search function, she or he can narrow down the search to all pages marked with "Tires". That does not
mean, that "Tires" must be on that page as a word, but it's marked as "relevant for tires" in the backend.

Example "University Courses"
............................

As university you offer courses with different degrees. You could create a page for each course and add filter options
for "bachelor" and "master" and let the users select which courses they want to see.

Using Faceting
--------------

The next pages will show you how to setup and configure the faceting feature:

.. toctree::
	:maxdepth: 3
	:titlesonly:
	:glob:

	Setup
	FilterTypes
	SystemCategories

