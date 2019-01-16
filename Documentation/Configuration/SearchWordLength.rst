.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _configuration-search-word-length:

Search word length
==================

By default ke_search only finds words with a minimum length of four characters. This corresponds to the MySQL setting
"ft_min_word_len" which is set to 4 by default.

The value can be reduced by following theses steps:

* change "ft_min_word_len" to the desired value in your MySQL configuration (eg. my.cnf) (default: 4)
* set "searchWordLength" in the extension manager setting to the same value
* re-index your content
