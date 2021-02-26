.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _configuration-storage-engine:

Storage Engine
==============

Why not use InnoDB for the index table ?
----------------------------------------

The storage engine for the index table (tx_kesearch_index) is MyISAM while all other tables use the default InnoDB storage engine.
Unfortunately this prevents using ke_search in Galera Clusters.

The reasons are explained below.

If the behaviour explained below does not affect how you use ke_search, you may switch to InnoDB for tx_kesearch_index.

Search for @-character ist not supported
........................................

You cannot search for the "at"-Character in InnoDB tables:

"InnoDB full-text search does not support the use of the @ symbol in boolean full-text searches. The @ symbol is reserved for use by the @distance proximity search operator."

* https://dev.mysql.com/doc/refman/5.6/en/fulltext-boolean.html
* https://github.com/teaminmedias-pluswerk/ke_search/issues/226

Phrase search is not supported
..............................

Phrase search using double quotes ("my search word") is not supported in InnoDB.

* https://bugs.mysql.com/bug.php?id=78485
* https://github.com/teaminmedias-pluswerk/ke_search/issues/214