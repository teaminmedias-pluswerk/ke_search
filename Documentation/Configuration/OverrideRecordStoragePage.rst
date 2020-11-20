.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _configuration-override-record-storage-page:

Override record storage page
============================

It is possible to override the record storage page defined in the plugin using typoscript. This is useful
if you want to servere differend search results depending on typoscript conditions.

For example you could server different search results to logged in users.

Setup typoscript:

.. code-block:: none

    plugin.tx_kesearch_pi1.overrideStartingPoint = 123
    plugin.tx_kesearch_pi1.overrideStartingPointRecursive = 1
    plugin.tx_kesearch_pi2.overrideStartingPoint = 123
    plugin.tx_kesearch_pi2.overrideStartingPointRecursive = 1
