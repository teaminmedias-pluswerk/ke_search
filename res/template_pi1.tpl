<!-- ###JS_SEARCH_ALL### begin -->
<script type="text/javascript">
//<![CDATA[
// js for all render methods
function searchboxFocus(searchbox) {
	if(searchbox.value == "###SEARCHBOX_DEFAULT_VALUE###") {
		searchbox.value = "";
	}
}

function searchboxBlur(searchbox) {
	if(searchbox.value == "") {
		searchbox.value = "###SEARCHBOX_DEFAULT_VALUE###";
	}
}

function resetCheckboxes(filter) {
	allLi = document.getElementsByName("optionCheckBox" + filter);
	allCb = new Array();
	for(i = 0; i < allLi.length; i++) {
		allCb[i] = allLi[i].getElementsByTagName("input");
	}
	for(i = 0; i < allCb.length; i++) {
		allCb[i][0].checked = false;
	}
}

function enableCheckboxes(filter) {
	var lis = document.getElementsByTagName("LI");
	//alert(lis.count());
	var allCb = new Array();
	var allCbChecked = true;
	var count = 0;
	var optionClass = new Array();
	for(var i = 0; i < lis.length; i++) {
		if (optionClasses = lis[i].getAttribute("class", 1)) {
			optionClassesArray = optionClasses.split(" ");
			//alert(optionClasses);
			if(optionClassesArray[1] == "optionCheckBox" + filter) {
				allCb[count] = lis[i].getElementsByTagName("input")[0];
				count++;
			}
		}
	}
	for(i = 0; i < allCb.length; i++) {
		if(!allCb[i].checked) {
			allCbChecked = false;
		}
	}
	if(allCbChecked) {
		for(i = 0; i < allCb.length; i++) {
			allCb[i].checked = false;
		}
	} else {
		for(i = 0; i < allCb.length; i++) {
			allCb[i].checked = true;
		}
	}
}
//]]>
</script>
<!-- ###JS_SEARCH_ALL### end -->

<!-- ###JS_SEARCH_NON_STATIC### begin -->
<script type="text/javascript">
//<![CDATA[
function switchArea(objid) {
	if (document.getElementById("options_" + objid).className == "expanded") {
		document.getElementById("options_" + objid).className = "closed";
		document.getElementById("bullet_" + objid).src="###SITE_REL_PATH###res/img/list-head-closed.gif";
	} else {
		document.getElementById("options_" + objid).className = "expanded";
		document.getElementById("bullet_" + objid).src="###SITE_REL_PATH###res/img/list-head-expanded.gif";
	}
}

function hideSpinnerFiltersOnly() {
	document.getElementById("kesearch_filters").style.display="block";
	document.getElementById("kesearch_updating_filters").style.display="none";
	document.getElementById("resetFilters").value=0;
}

function pagebrowserAction() {
	document.getElementById("kesearch_results").style.display="none";
	document.getElementById("kesearch_updating_results").style.display="block";
	document.getElementById("kesearch_pagebrowser_top").style.display="none";
	document.getElementById("kesearch_pagebrowser_bottom").style.display="none";
	document.getElementById("kesearch_query_time").style.display="none";
}

// refresh result list onload
function onloadFilters() {
	document.getElementById("kesearch_filters").style.display="none";
	document.getElementById("kesearch_updating_filters").style.display="block";
	tx_kesearch_pi1refreshFiltersOnLoad(xajax.getFormValues("xajax_form_kesearch_pi1"));
}
//]]>
</script>
<!-- ###JS_SEARCH_NON_STATIC### end -->

<!-- ###JS_SEARCH_AJAX_RELOAD### begin -->
<script type="text/javascript">
//<![CDATA[
// refresh result list onload
function onloadResults() {
	document.getElementById("kesearch_pagebrowser_top").style.display="none";
	document.getElementById("kesearch_pagebrowser_bottom").style.display="none";
	document.getElementById("kesearch_results").style.display="none";
	document.getElementById("kesearch_updating_results").style.display="block";
	document.getElementById("kesearch_query_time").style.display="none";
	tx_kesearch_pi1refreshResultsOnLoad(xajax.getFormValues("xajax_form_kesearch_pi1"));
}

function onloadFiltersAndResults() {
	document.getElementById("kesearch_filters").style.display="none";
	document.getElementById("kesearch_updating_filters").style.display="block";
	document.getElementById("kesearch_results").style.display="none";
	document.getElementById("kesearch_updating_results").style.display="block";
	document.getElementById("kesearch_pagebrowser_top").style.display="none";
	document.getElementById("kesearch_pagebrowser_bottom").style.display="none";
	document.getElementById("kesearch_query_time").style.display="none";
	tx_kesearch_pi1refresh(xajax.getFormValues("xajax_form_kesearch_pi1"));
}

function hideSpinner() {
	document.getElementById("kesearch_filters").style.display="block";
	document.getElementById("kesearch_updating_filters").style.display="none";
	document.getElementById("kesearch_results").style.display="block";
	document.getElementById("kesearch_updating_results").style.display="none";
	document.getElementById("kesearch_pagebrowser_top").style.display="block";
	document.getElementById("kesearch_pagebrowser_bottom").style.display="block";
	document.getElementById("kesearch_query_time").style.display="block";
}


// domReady function
!function (context, doc) {
  var fns = [], ol, f = false,
      testEl = doc.documentElement,
      hack = testEl.doScroll,
      domContentLoaded = 'DOMContentLoaded',
      addEventListener = 'addEventListener',
      onreadystatechange = 'onreadystatechange',
      loaded = /^loade|c/.test(doc.readyState);

  function flush(i) {
    loaded = 1;
    while (i = fns.shift()) { i() }
  }
  doc[addEventListener] && doc[addEventListener](domContentLoaded, function fn() {
    doc.removeEventListener(domContentLoaded, fn, f);
    flush();
  }, f);


  hack && doc.attachEvent(onreadystatechange, (ol = function ol() {
    if (/^c/.test(doc.readyState)) {
      doc.detachEvent(onreadystatechange, ol);
      flush();
    }
  }));

  context['domReady'] = hack ?
    function (fn) {
      self != top ?
        loaded ? fn() : fns.push(fn) :
        function () {
          try {
            testEl.doScroll('left');
          } catch (e) {
            return setTimeout(function() { domReady(fn) }, 50);
          }
          fn();
        }()
    } :
    function (fn) {
      loaded ? fn() : fns.push(fn);
    };

}(this, document);

// domReadyAction
###DOMREADYACTION###
//]]>
</script>
<!-- ###JS_SEARCH_AJAX_RELOAD### end -->

<!-- ###SEARCHBOX_STATIC### start -->
<form method="get" id="xajax_form_kesearch_pi1" name="xajax_form_kesearch_pi1"  action="###FORM_ACTION###" class="static" ###ONSUBMIT###>
	<fieldset class="kesearch_searchbox">
	<input type="hidden" name="id" value="###FORM_TARGET_PID###" />
	###HIDDENFIELDS###

	<div class="kesearchbox">
		<input type="text" id="ke_search_sword" name="tx_kesearch_pi1[sword]" value="###SWORD_VALUE###" placeholder="###SEARCHBOX_DEFAULT_VALUE###" onfocus="###SWORD_ONFOCUS###" onblur="###SWORD_ONBLUR###"/>
		<input type="image" id="kesearch_submit" src="typo3conf/ext/ke_search/res/img/kesearch_submit.png" alt="###SUBMIT_VALUE###" class="submit" onclick="document.getElementById('pagenumber').value=1; document.getElementById('xajax_form_kesearch_pi1').submit();" />
		<div class="clearer">&nbsp;</div>
	</div>

	<input id="pagenumber" type="hidden" name="tx_kesearch_pi1[page]" value="###HIDDEN_PAGE_VALUE###" />
	<input id="resetFilters" type="hidden" name="tx_kesearch_pi1[resetFilters]" value="0" />
	<input id="sortByField" type="hidden" name="tx_kesearch_pi1[sortByField]" value="###SORTBYFIELD###" />
	<input id="sortByDir" type="hidden" name="tx_kesearch_pi1[sortByDir]" value="###SORTBYDIR###" />

	<div id="kesearch_filters">###FILTER###</div>

	<!-- ###SHOW_SPINNER### begin -->
	<div id="kesearch_updating_filters">###SPINNER###<br /></div>
	<!-- ###SHOW_SPINNER### end -->
	<span class="resetbutt">###RESET###</span>
	<span class="submitbutt">###SUBMIT###</span>
	</fieldset>

</form>
<!-- ###SEARCHBOX_STATIC### end -->


<!-- ###RESULT_LIST### start -->
	<div id="kesearch_num_results">###NUMBER_OF_RESULTS###</div>
	<div id="kesearch_ordering">###ORDERING###</div>
	<div id="kesearch_pagebrowser_top">###PAGEBROWSER_TOP###</div>
	<div class="clearer"> </div>
	<div id="kesearch_results">###MESSAGE###</div>
	<div id="kesearch_updating_results">###SPINNER###<br /></div>
	<div id="kesearch_pagebrowser_bottom">###PAGEBROWSER_BOTTOM###</div>
	<!-- ###SUB_QUERY_TIME### start -->
	<div id="kesearch_query_time">###QUERY_TIME###</div>
	<!-- ###SUB_QUERY_TIME### end -->
<!-- ###RESULT_LIST### end -->


<!-- ###PAGEBROWSER### start -->

<div class="pages_total">
	<div class="result_txt">###RESULTS### ###START### ###UNTIL### ###END### ###OF### ###TOTAL###</div>
	<div class="kesearch_pagebrowser">###PREVIOUS### ###PAGES_LIST### ###NEXT###</div>
</div>

<!-- ###PAGEBROWSER### end -->

<!-- ###ORDERNAVIGATION### start -->
<div class="ordering">
	<ul>
		<li><strong>###LABEL_SORT###</strong></li>
		<!-- ###SORT_LINK### begin -->
			<li class="sortlink sortlink-###FIELDNAME###">###URL###<span class="###CLASS###"></span></li>
		<!-- ###SORT_LINK### end -->
	</ul>
	<div class="clearer"></div>
</div>
<!-- ###ORDERNAVIGATION### end -->

<!-- ###RESULT_ROW### start -->
<div class="result-list-item result-list-item-type-###TYPE###">
	<!-- ###SUB_NUMERATION### start --><span class="result-number">###NUMBER###.</span><!-- ###SUB_NUMERATION### end -->
	<span class="result-title">###TITLE###</span>
	<!-- ###SUB_SCORE_SCALE### start -->
		<span class="scoreBar">
			<span class="score" style="width: ###SCORE###%;"></span>
		</span>
	<!-- ###SUB_SCORE_SCALE### end -->
	<span class="clearer">&nbsp;</span>
	<div class="add-info">
	    <!-- ###SUB_RESULTURL### start -->
		<i>###LABEL_RESULTURL###:</i> ###RESULTURL###<br />
	    <!-- ###SUB_RESULTURL### end -->
	    <!-- ###SUB_SCORE### start -->
		<i>###LABEL_SCORE###:</i> ###SCORE###<br />
	    <!-- ###SUB_SCORE### end -->
	    <!-- ###SUB_DATE### start -->
		<i>###LABEL_DATE###:</i> ###DATE###<br />
	    <!-- ###SUB_DATE### end -->
	    <!-- ###SUB_SCORE_PERCENT### start -->
		<i>###LABEL_SCORE_PERCENT###:</i> ###SCORE_PERCENT### %<br />
	    <!-- ###SUB_SCORE_PERCENT### end -->
	    <!-- ###SUB_TAGS### start -->
		<i>###LABEL_TAGS###:</i> ###TAGS###<br />
	    <!-- ###SUB_TAGS### end -->
	</div>
	<!-- ###SUB_TYPE_ICON### start --><span class="teaser_icon">###TYPE_ICON###</span><!-- ###SUB_TYPE_ICON### end -->
	<span class="result-teaser">###TEASER###</span>
	<span class="clearer">&nbsp;</span>
</div>
<!-- ###RESULT_ROW### end -->


<!-- ###GENERAL_MESSAGE### start -->
    <div class="general-message">
	<div class="image">###IMAGE###</div>
	<div class="message">###MESSAGE###</div>
	<div class="clearer">&nbsp;</div>
    </div>
<!-- ###GENERAL_MESSAGE### end -->


<!-- ###SUB_FILTER_SELECT### start -->
    <div>
	<select id="###FILTERID###" name="###FILTERNAME###" ###ONCHANGE### ###DISABLED###>
	    <!-- ###SUB_FILTER_SELECT_OPTION### start -->
		<option value="###VALUE###" ###SELECTED###>###TITLE###</option>
	    <!-- ###SUB_FILTER_SELECT_OPTION### end -->
	</select>
    </div>
<!-- ###SUB_FILTER_SELECT### end -->


<!-- ###SUB_FILTER_LIST### start -->
    <div class="list" id="list_###FILTERID###">
		<span class="head">
			###BULLET###
			###SWITCH_AREA_START### ###FILTERTITLE### ###SWITCH_AREA_END###
		</span>
		<input type="hidden" name="###FILTERNAME###" id="###FILTERID###" value="###VALUE###" />
		<ul id="options_###FILTERID###" class="###LISTCSSCLASS###">
			<!-- ###SUB_FILTER_LIST_OPTION### start -->
			<li class="###OPTIONCSSCLASS### ###SPECIAL_CSS_CLASS###" ###ONCLICK###>###TITLE###</li>
			<!-- ###SUB_FILTER_LIST_OPTION### end -->
			<li><span class="kesGreyButt" ###ONCLICK_RESET###>###RESET_FILTER###</span></li>
		</ul>

	</div>
<!-- ###SUB_FILTER_LIST### end -->



<!-- ###SUB_FILTER_CHECKBOX### start -->
    <div class="list" id="list_###FILTERID###">
	<span class="head">
	    ###BULLET###
		###SWITCH_AREA_START### ###FILTERTITLE### ###SWITCH_AREA_END###
	</span>
	<ul id="options_###FILTERID###" class="###LISTCSSCLASS### checkboxList">
		<!-- ###SUB_CHECKBOX_SWITCH### start -->
		<li class="checkboxButton">
			<span class="kesGreyButt" onclick="enableCheckboxes(###FILTER_UID###)">###LABEL_ALL###</span>
		</li>
		<!-- ###SUB_CHECKBOX_SWITCH### end -->

		<!-- ###SUB_FILTER_CHECKBOX_OPTION### start -->
		<li class="###OPTIONCSSCLASS### ###SPECIAL_CSS_CLASS###">
			<input type="checkbox" name="###FILTERNAME###[###OPTIONKEY###]" id="###OPTIONID###" value="###VALUE###" ###OPTIONSELECT### ###OPTIONDISABLED### />
			<label for="###OPTIONID###">###TITLE###</label>
			<div class="clearer"></div>
		</li>
		<!-- ###SUB_FILTER_CHECKBOX_OPTION### end -->

		<li class="clearer"></li>

		<!-- ###SUB_CHECKBOX_RESET### start -->
		<li class="checkboxButton">
			<span class="kesGreyButt" onclick="resetCheckboxes(###FILTER_UID###); ###ONCLICK_RESET###">###RESET_FILTER###</span>
		</li>
		<!-- ###SUB_CHECKBOX_RESET### end -->

		<!-- ###SUB_CHECKBOX_SUBMIT### start -->
		<li class="checkboxButtonSubmit">
			<span class="kesGreyButt" onclick="###ONCLICK_RESET###">###CHECKBOX_SUBMIT###</span>
		</li>
		<!-- ###SUB_CHECKBOX_SUBMIT### end -->
	</ul>
    </div>
<!-- ###SUB_FILTER_CHECKBOX### end -->


<!-- ###SUB_FILTER_TEXTLINKS### begin ### -->
<div class="textlinks">
	###HIDDEN_FIELDS###
	<h3>###FILTERTITLE###</h3>
	<ul>
	<!-- ###SUB_FILTER_TEXTLINK_OPTION### begin -->
		<li class="###CLASS###">###TEXTLINK###</li>
	<!-- ###SUB_FILTER_TEXTLINK_OPTION### end -->
	</ul>
	<div>###LINK_MULTISELECT###</div>
	<div class="resetlink">###LINK_RESET_FILTER###</div>
</div>
<!-- ###SUB_FILTER_TEXTLINKS### end ### -->


<!-- ###SUB_FILTER_MULTISELECT### begin -->
<div class="multiselect">
	<form method="get" action="###FORM_ACTION###">
		<input type="hidden" name="id" value="###PAGEID###" />
		<input type="hidden" name="tx_kesearch_pi1[multi]" value="1" />
		<input type="hidden" name="tx_kesearch_pi1[sword]" value="###SWORD###" />
		<input type="hidden" name="tx_kesearch_pi1[page]" value="1" />

		<!-- ###SUB_FILTER_MULTISELECT_HIDDEN### begin -->
			<input type="hidden" name="###NAME###" value="###VALUE###" />
		<!-- ###SUB_FILTER_MULTISELECT_HIDDEN### end -->

		<!-- ###SUB_FILTER_MULTISELECT_FILTER### begin -->
			<h3>###TITLE###</h3>
			<!-- ###SUB_FILTER_MULTISELECT_OPTION### begin -->
				<div class="multi-option###ADDCLASS###">
					<input type="checkbox" name="###FILTERNAME###[###OPTIONKEY###]" id="###FILTERNAME###[###OPTIONKEY###]" value="###OPTIONTAG###" ###SELECTED### />
					<label for="###FILTERNAME###[###OPTIONKEY###]">###OPTIONTITLE###</label>
					<div class="clearer"></div>
				</div>
			<!-- ###SUB_FILTER_MULTISELECT_OPTION### end -->
			<div class="clearer"></div>
		<!-- ###SUB_FILTER_MULTISELECT_FILTER### end -->
		<div class="multiselectButtons">
			<span class="kesGreyButt">###LINK_BACK###</span>
			<input class="kesGreyButt" type="submit" value="###SHOW_RESULTS###" />
		</div>
	</form>
</div>
<!-- ###SUB_FILTER_MULTISELECT### end -->
