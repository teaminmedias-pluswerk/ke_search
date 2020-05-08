.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _options:

Search options
==============

The following parameters can be used in the fulltext search:


Search for phrases
------------------

A search for phrases can be executed by using the quote signs (""). The result will then contain exactly the
searched phrase.


.. code-block:: none

	"french cooking"

Conjunction: The result must contain the word
---------------------------------------------

.. code-block:: none

	"french cooking" +eggs

Exclusion: The result must not contain the word
-----------------------------------------------

.. code-block:: none

	"french cooking" -eggs

Partial word search
-------------------

.. code-block:: none

	"french cooking" pepp*

results in

.. code-block:: none

	"french cooking" pepper
	"french cooking" peppermint

Search for umlauts
------------------

If you search for words including umlauts, it doesn't matter if you use the umlaut character or the character in your
search word (ä --> a, ö --> o etc.). Example: Searching for "Küche" or "Kuche" gives the same results.

The reason for that lies in MySQL itself and it's treatment of collations:

http://dev.mysql.com/doc/refman/5.1/en/charset-collation-effect.html

http://stackoverflow.com/questions/2607130/mysql-treats-as-aao

More than one search word
-------------------------

If you type in more than one searchword, all the words will be linked with "OR".

The results containing all searched words get the highest ranking and will be placed on top of the result list.

* If you place a „+“ in front of a word, the result must contain the word.(Conjunction).
* If you place a „-“ in front of a word, the result must not contain the word.(Disjunction).

Example:

“+Auto +cheap -expensive“

If you activate the „enableExplicitAnd“-option in the extension manager,
all words will be conjuncted and the „+“-parameter becomes needles.

Note: If you are using the premium version of ke_search and you want to activate the
searchengine Sphinx, all search words will automatically be conjuncted for it is the default behaviour of Sphinx.

Minimum length
--------------
If a word is shorter than 4 characters it will not be searched (Example: "come to" is the searched phrase and
only "come" will be searched). This behaviour only shows if the short word stands at the beginning or the end of the
searched phrase. If the short word stands between to longer words like "come to our company",
this phrase will be searched exactly.

The minimum length can be changed, see "Configuration".

Partial word search
-------------------

The partial word search is enabled by default.

ke_search will find partial words if they are in the beginning of the words in the index.

Search for "Apple" will find:

* Apple
* Appletree

But will not find:

* Bigapple

The partial word search can be deactivated in the extension setting (Extension-Manager), option „enablePartSearch“.
Only full words which match the input will then be found.

You can activate the partial word search for single words by adding a „*“ to the searched words. (Example see above).

In-Word-Partial-Search
----------------------

Using the standard version of ke_search it is not possible to find partial words within other words, they have to
be placed at the beginning of a word.

Example:

* Searching for "back" will not find "paperback".
* Searching for "paper" will find "paperback".

If you use the premium version of ke_search together with Sphinx you can enable partial in-word search so
that searching for "back" will also find "paperback".

