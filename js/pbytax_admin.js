function displayTermsSelector( taxID, widgetID ){
	var selectorID = taxID + "-" + widgetID;
	jQuery( ".pbytax-settings .terms-selector-"+widgetID ).each(function() {
			if (selectorID == jQuery(this).attr("id") ) {
				jQuery( this ).show();
				this.disabled = false;
			}
			else {
				jQuery( this ).hide();
				this.disabled = true;
			}
	});
	
}

function displayMetaKeysSelector( postType, widgetID ){
	var selectorID = postType + "-keys-" + widgetID;
	jQuery( ".pbytax-settings .meta-keys-selector-"+widgetID ).each(function() {
			if (selectorID == jQuery(this).attr("id") ) {
				jQuery( this ).show();
				this.disabled = false;
			}
			else {
				jQuery( this ).hide();
				this.disabled = true;
			}
			if (postType == "any"){
				jQuery( this ).hide();
				this.disabled = true;
			}
	});
	if (postType=="any"){
		jQuery( ".pbytax-settings .meta_fields_options" ).hide();
	}
	else {
		jQuery( ".pbytax-settings .meta_fields_options" ).show();
	}
		
}