jQuery(function(){
	var dates = jQuery("#date_start, #date_end").datepicker({
		changeMonth: true,
		changeYear: true,
		defaultDate: "",
		dateFormat: "yy-mm-dd",
		numberOfMonths: 1,
		showButtonPanel: true,
		showOn: "both",
		buttonImage: "../wp-content/plugins/woocommerce/assets/images/calendar.png",
		buttonImageOnly: true,
		onSelect: function(selectedDate){
			var option = this.id == "date_start" ? "minDate" : "maxDate",
				instance = jQuery(this).data("datepicker"),
				date = jQuery.datepicker.parseDate(
					instance.settings.dateFormat ||
					jQuery.datepicker._defaults.dateFormat,
					selectedDate, instance.settings);
			dates.not(this).datepicker("option", option, date);
		}
	});
});