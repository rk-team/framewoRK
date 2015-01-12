(function(){

	rk.widgets.datepicker = rk.base.extend({

		oParams: {},
		oContainer: null,
		oResetButton: null,
		sLanguage: null,
		oDatePicker: null,
		oJqueryParams: null,
		
		//constructeur
		init: function(oParams) {
			oParams = oParams || {};
			
			this.checkMissingParam(oParams, 'mTarget');
			var oTarget = this.checkValidContainer(oParams.mTarget);
			
			if (!rk.util.isEmpty(oParams.sLanguage)) {
				this.sLanguage = oParams.sLanguage;
			} else {
				this.sLanguage = rk.util.i18n.language;
			}
			
			this.oParams = oParams;
			this.oParams.oJqueryParams = oParams.oJqueryParams || {};	
			
			this.oContainer = oTarget;

			this.initWidget();
		},
		
		initWidget: function() {
			var oDate;
			// re-init of datepicker container to avoid jquery bug around refresh
			if($('#ui-datepicker-div').length == 1) {
				$.datepicker.dpDiv = $('#ui-datepicker-div');					
			}
			
			if(!rk.util.isEmpty(this.oParams.bAddResetButton)) {
				this.addResetButton();
			}
			
			this.oParams.oJqueryParams.alwaysSetTime = false;
			this.buildWidget();
			
			if(!rk.util.isEmpty($(this.oContainer).val())) {
				$(this.oDatePicker).datepicker({ dateFormat: 'yyyy-mm-dd' });
				$(this.oDatePicker).datepicker('setDate', $(this.oContainer).val());				
			}
			
			//We change the language
			if(!rk.util.isEmpty(this.sLanguage) && (this.sLanguage != 'en')) {
				//"en" is default we don't need to switch
				$(this.oDatePicker).datepicker('option', $.datepicker.regional[this.sLanguage]);
			}
						
			if(!rk.util.isEmpty(this.oParams.bTextDisable)) {
				this.disableTextField();
			}
		},
		
		buildWidget: function () {
			this.oDatePicker = $(this.oContainer).datepicker(this.oParams.oJqueryParams);
		},
		
		addResetButton: function() {
			var oButton = document.createElement('div'),
				oCallback;
			
			$(oButton).addClass('dateReset cancel');
			$(oButton).insertAfter(this.oContainer);			
			
			this.oResetButton = $(oButton);

			$(this.oResetButton).bind('click', $.proxy(this.resetClickedHandler, this));
		},
		
		resetClickedHandler: function() {
			this.resetDate();
		},
				
		resetDate: function() {
			$.datepicker._clearDate(this.oContainer);
			$(this.oContainer).trigger('reset');
		},
		
		disableTextField: function() {
			$(this.oContainer).attr('readonly', 'readonly');
		}
	});	
	
	var self = rk.widgets.datepicker;
	
})();
