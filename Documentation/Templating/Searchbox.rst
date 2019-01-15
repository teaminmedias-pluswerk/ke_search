.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. _searchbox:

Searchbox on every page
=======================

Include searchbox with HTML
---------------------------


.. code-block:: none

	########################################
	# searchbox pure HTML
	########################################
	lib.searchbox_html = TEXT
	lib.searchbox_html.value (
	<form method="get" id="form_kesearch_searchfield" name="form_kesearch_searchfield" action="/search/">
	  <input type="text" id="ke_search_searchfield_sword" name="tx_kesearch_pi1[sword]" placeholder="Your search phrase" />
	  <input type="submit" id="ke_search_searchfield_submit" alt="Find" />
	</form>
	)

	# Default PAGE object:
	page = PAGE
	page.5 < lib.searchbox_html
	page.10 < styles.content.get

The action "/search/" ist the slug of the page you created with your result list plugin.

Include searchbox with Typoscript
---------------------------------

Via Typoscript you can include the search box plugin on every page.

Right now this is only possible without filters. If you need filters, it's recommended to include the searchbox as content element and then inherit that element to subpages.

You can either include the plugin directly or use HTML to create a search box.

Attention: If you use COA_INT no static cache is possible. You should use the HTML version in this case.

You can include the searchbox as follows.

.. code-block:: none

	########################################
	# Searchbox Plugin
	########################################
	lib.searchbox_plugin = COA_INT
	lib.searchbox_plugin {
	  10 < plugin.tx_kesearch_pi1

	  # result page
	  10.resultPage = 123

	  # CSS file
	  10.cssFile = EXT:ke_search/res/ke_search_pi1.css

	  # Content element (search box plugin) from which additional
	  # configuration should be loaded (UID of content element).
	  # Important: If you have two search boxes on your result page
	  # (eg. in the top and in the left area), you should set this value!
	  # 10.loadFlexformsFromOtherCE = 123456
	}

The number 123 in this case is a placeholder for the page ID you created with your result list plugin.

