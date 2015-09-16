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