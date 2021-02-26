.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _ttnewsIndexer:

News (EXT:tt_news)
==================

With this indexer you can index news from the extension "news" (extension key "tt_news", not "news").

The following fields will be indexed: title, short, bodytext, author, author_email, keywords.

Access limitations will be taken into account.

An image will be shown in the result list if you activate that setting in the ke_search searchbox plugin. The first assigned image will be shown.

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

Alternative single view page from category
------------------------------------------

If a news record has a news category assigned which has a "Single-view page for news from this category [single_pid]"
set, this will be used as single view and will overwrite the setting "Target page" of the indexer configuration. This
is the same behaviour as if the corresponding tt_news constant set.

.. code-block:: none

	plugin.tt_news.useSPidFromCategory = 1

Note: This constant has no effect in ke_search. In ke_search this category is always used.

File indexing
-------------

Files attached to news records will be indexed. You can specify in the indexer configuration wether to include the
content of the files into the news record search result, that means they will appear as one result, or to index files
separately, making them show up as a individual result.

You can also specify which files should be indexed by defining a comma-separated list of file extensions. If you
leave this field empty, no files will be indexed.

Automated tagging
-----------------
Tags will be automatically generated from keywords and from assigned categories by applying the rules for tags
(no spaces and special characters). For example, if you have category
"Blue cars!", a tag named "Bluecars" will be assigned to the index record. You can use these tags to create
filters. Just use the same tag ("Bluecars") as tag in the filter option you are creating.