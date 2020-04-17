.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _introduction:

Introduction
============

ke_search is a TYPO3 search engine. It allows to search content stored in the TYPO3 database (pages, content
elements, news and other extension content) and files.

It offers faceting possibilities to enhance the search experience. Faceting means you can narrow down your search
results by selecting certain categories, called facets or filter options. By using faceting you can also
create applications which are not related directly to fulltext search but make more use of filtering the content.
Good examples would be a product finder for companies or a study finder for universities.

By writing your own indexer you can put any content you want into the index.

ke_search does not use frontend crawling but fetches content elements and data records directly from the database.
This approach has the advantage that content will only be stored once in the index, even if it shows up on multiple
pages of the website.
For each type of content (pages, news, files ...) there has to be a dedicated indexer available. That means there may
not be an indexer already available for the content type you want to index. On the other hand, it's quite easy for a
programmer to write custom indexers for custom data records. A set of indexers for common content types comes
bundled together with ke_search (including pages, news and pdf files).

ke_search uses the MySQL fulltext search algorithm, so it does not need any tools installed on
the server. But you will need to install tools if you want to use file indexing (PDF, XLS, DOC files).

System requirements
-------------------

* ke_search 3 requires TYPO3 9. (Please use ke_search version 2.8.X for TYPO3 8).
* ke_search requires MySQL / MariaDB, since it uses the “MATCH … AGAINST” function.
* For file indexing additional tools are required: pdftotext, pdfinfo, catdoc, catppt, xls2csv.
