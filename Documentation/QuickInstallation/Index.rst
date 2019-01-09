.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _quickinstallation:

Quick installation
==================

Follow the steps below to set up a simple fulltext search for your pages.
In order to use the faceting feature see "Faceting".

Download and installation
-------------------------

Install the extension ke_search via extension manager or via composer (recommended):

.. code-block:: none

	composer require teaminmedias-pluswerk/ke_search

You can find the current version (and older ones) at

https://github.com/teaminmedias-pluswerk/ke_search/releases

Create pages
------------

Create a new page called "Search" (or similar) and a sysfolder called "Search data" (or similar).

.. image:: ../Images/QuickInstallation/page-structure.png

Configure Plugins
-----------------

* Create a plugin "Faceted Search: Show searchbox and filters" on the page "Search".
* Fill in the field "Record Storage Page" in the Tab "Plugin" -> "General" with the Systemfolder that you created in Step 2 (our example: 'Search data'). NOTE: It is useful to give the Plug-In "Searchbox and Filters" a Header (our example: 'Searchbox'). That makes it easier to identify the correct content element in the next step.
* Create a plugin "Faceted Search: Show resultlist" on the page "Search".
* In the field "load flexform config from this search box" fill in the Search-Plug-In that you created in Step 3 (our example: "Searchbox").

3. Implementation & Configuration of the Plug-In: "Searchbox and Filters"




4. Implementation & Configuration of the Plug-In: “Resultlist"

Inserting the Plug-In "Show result list"

Insert the Plug-In "Faceted Search - Show resultlist" as a new content element on the search page (our example page: "search results").



NOTE: It is useful to give the Plug-In "Searchbox and Filters" a Header (our example: 'Searchbox'). That way we can Identify it here, otherwise the Plug-In would have no Title.

NOTE: Both Plug-Ins have to be implemented into the result page. It is possible to only use the Plug-In "Searchbox and Filters" on other pages and redirect to the result page from there.

Create the indexer configuration
--------------------------------

Creating an Index-Configuration
Create Index-Configuration
Configure Index-Configuration
Configuration of the Index-Configuration
Configure Index-Configuration
Create an "Indexer-Confuguration"-Entry in the system folder "Search storage page":

Enter a Title of your choosing.
Select the folder "Search storage page".
Set the type of the Indexer-Configuration to "Page".
Choose the Pages you wish to index. You can decide whether the indexing process runs on all Pages recursively or if only one page is to be considered. Another option is to combine both fields.
Set the field "Index content elements with restrictions?" to "No", if you don't want protected content elements to show up in the search resultlist.
6. Start Indexing

Start Indexer
-------------
Start Indexer
Open the backend module “Web → Faceted Search” and start the indexing process.

Searching frontend
Suche im Frontend ist eingerichtet
You're done!

Open the frontend page where you inserted the plugins and start finding...