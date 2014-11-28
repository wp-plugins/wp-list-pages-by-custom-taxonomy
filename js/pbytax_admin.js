function displayTermsSelector( taxID, widgetID ){
	var selectorID = taxID + "-" + widgetID;
	jQuery( ".terms-selector-"+widgetID ).each(function() {
			if (selectorID == jQuery(this).attr("id") ) {
				jQuery( this ).show();
				this.disabled = false;
				/*jQuery( this ).prop('disabled', false);*/
			}
			else {
				jQuery( this ).hide();
				this.disabled = true;
				/*jQuery( this ).prop('disabled', true);*/
			}
		});
	
}