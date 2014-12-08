(function(){

	rk.widgets.datetimepicker = rk.widgets.datepicker.extend({

		init: function(oParams) {
			this._super(oParams);
		},
				
		buildWidget: function () {
			this.oDatePicker = $(this.oContainer).datetimepicker(this.oParams.oJqParams);
		},
		
		resetDate: function() {
			this._super();
			$(this.oContainer).val('');
		},
	});
	
	var self = rk.widgets.datetimepicker;
	
})();
