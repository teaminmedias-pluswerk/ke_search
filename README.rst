.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.


.. _start:

=========
ke_search
=========


ke_search is a search engine for the TYPO3 content management system.

It offers fulltext search and faceting possibilities. Faceting means you
can narrow down your search results by selecting certain categories,
called facets or filter options.

It is very flexible: By writing your own indexer you can put any content you want into the index.

It uses fluid templates (since version 2.0).

ke_search comes with strong defaults, with very little configuration you can have a powerful
search engine in your TYPO3 website, eg. images in the search result list and faceting without
templting or coding.

ke_search does not use frontend crawling but fetches content elements and data records directly from the database.
For each content type an indexer is needed (eg. pages, news).
For the most used content types indexers are provided within the extension itself, including pages, news and tt_news.

That means there may not be an indexer already available for the content type you want to index, but it's quite
easy for a programmer to write it's own indexer for custom data records.

See https://www.typo3-macher.de/en/facetted-search-ke-search/documentation/introduction/ for further documentation and
a quickstart tutorial.

If you find bugs or want to ask for a feature, please use https://github.com/teaminmedias-pluswerk/ke_search/issues

