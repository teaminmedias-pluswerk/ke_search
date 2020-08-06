.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _ranking:

Ranking / Sorting
=================

The ranking of search results can be defined in the plugin settings of the "searchbox" plugin
in the tab "Sorting". By default, search results will be sorted by "relevance" if a searchword is given
and by "date" if no searchword is given.

Ranking by relevance ("score")
------------------------------

The ranking of search results by relevance (in case "relevance" is selected in the plugin
settings or selected by the user) is based on the MySQL fulltext search.
You can find more information about the ranking algorithm of MySQL here:

https://dev.mysql.com/doc/internals/en/full-text-search.html

Searchwords in titles
---------------------

If a user is searching for a word and that word appears in the title of index records, those records will be
ranked higher. Most likely this will push those index records to the very top of the result list.

Please note:

* This will only work, if the search word appears exactly in the title.
  If a user searches for "Australia", the results with "Australian shepherd" in the title won't get a
  higher weight. (see also "Relevance calculation for partial words" below)

Adjusting weight of the searchwords in titles
---------------------------------------------

You can adjust how strong the search word in the title should be weighted. Go to the extension settings and
increase the number for "multiplyValueToTitle".

Please note:

* Even with this value set to "1" (the lowest possible value), results having the search word in the title will be on top of the result list. Thus in most cases this setting will not have any effect.

Relevance calculation for partial words
---------------------------------------

Please note that MySQL will not calculate a relevance for partial words. The relevance can only be calculated if at
least one search word is found as exact string in the index content

* search for „testcontent“ will deliver a relevance value if this word exists in index content
* search for „testco“ will NOT deliver a relevance value if „testcontent“ exists in index content

Custom Ranking
--------------
If you want to have more power about the ranking of search results or want to push certain results to the
top of the list, consider the premium version of ke_search which adds the features "custom ranking"
and "boost keywords".

More information

* http://dev.kesearch.de/documentation/ke_search_premium/Index.html
* https://www.typo3-macher.de/facettierte-suche-ke-search/premium/
