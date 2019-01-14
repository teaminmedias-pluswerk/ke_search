.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _customIndexer:

Custom Indexer
==============

You may write your own indexer and plug it into ke_search.

Feel free to use that extension as a kickstarter for your own custom indexer:

https://github.com/teaminmedias-pluswerk/ke_search_hooks

Hints
.....

* Make sure you fill $pid, $type, $language and (important) $additional_fields['orig_uid']. These fields are needed for the check if a content element already has been indexed. If you don't fill them, it may happen that only one content element of your specific type is indexed because all the elements are interpreted as the same record.
* You don't need to fill $tags if you don't use facetting.
* You don't need to fill $abstract, it will then generated automatically from $content.
* You will have to fill $params if you want to link to a extension which expects a certain parameter, eg. "&tx_myextension_pi1[showUid]=123"
