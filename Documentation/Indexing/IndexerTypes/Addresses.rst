.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _addressesIndexer:

Addresses
=========

The Address indexer allows you to index tt_address entries.

The following fields are indexed (if they are filled):

* name
* first_name
* middle_name
* last_name
* company
* address
* zip
* city
* country
* region
* email
* phone
* fax
* mobile
* www

If set, the description is used as an abstract (search result list teaser).

Please notice that there is no singleview in tt_address, the parameter "tt_address[showUid]"  ist nevertheless set.
If you need another parameter – e.g. for use with another extension that handles tt_address records –
you will have to modify the indexer content by using your own hook.