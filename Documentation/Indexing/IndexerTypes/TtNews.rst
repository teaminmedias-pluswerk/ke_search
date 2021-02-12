.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _newsIndexer:

News (EXT:tt_news)
==================

With this indexer you can index news from the extension "news" (extension key "tt_news", not "news").

The following fields will be indexed: title, short, bodytext, author, author_email, keywords.

Access limitations will be taken into account.

Configuration
-------------

In order to index news, create a new indexer configuration an configure it as follows:

* Title: just for internal use
* Storage: the sysfolder all your search data is saved on.
* Target page: The page your news detail view plugin is placed on.
* Type: has to be "News (tt_news)".
* Pages/folders recursive/single: Sysfolders with news data to index.
* Add tags of parent pages/folders: If you added a tag to the sysfolder containing news, these tags will be added to the news index entry.
* Add tag to all indexed elements: You can select an already existing filter option / tag to add it to all indexed elements.
