.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _pagesIndexer:

Pages
=====

The page indexer indexes standard TYPO3 pages.

All content elements on a page will be grouped and written to the index in one index entry. That means if your search
word appears in two different text elements on a page, you will get only one search result for the page
these two elements belong to.

The page indexer indexes content elements of the following types:

* text
* text with image
* bullet list
* table
* plain HTML
* header.

In the page property there's the field "Abstract for search result" in the tab "Search". Here you can enter a short
description of the page, this text will be used as an abstract in the search result list. If this field is empty, it
falls back to the field "Description" in the "Metadata" tab of the page properties.

Configuration
-------------
* Set the type of the indexer configuration to „Pages”.
* Set a title (only for internal use)
* set the record storage page of search data your search data folder
* Set „Startingpoints (recursive)” to the pages you want to index recursively.
* Set „Single Pages” to the pages you want to index non-recursively.

Advanced options:

* If you set „Index content elements with restrictions” to „yes”, content elements will be indexed even if they have frontend user group access restrictions. This function may be used to „tease” certain content elements in your search and then tell the user that he will have to log in to see the full content once he clicks on the search result.
* Set the allowed file extension of files to index. By default this is set to "pdf,ppt,doc,xls,docx,xlsx,pptx". For pdf, ppt doc and xls files you need to install external tools on the server.
* Set the page types you want to index.
* Set the content element types you want to index. You can add your own content element types for example those created with the extension "mask".
* You can choose to add a tag to all index entries created by this indexer.
* You can choose to add that tag also to files indexed by this indexer.