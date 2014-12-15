(function(){

	rk.event.callback = rk.base.extend({

		oScope: null,
		oParams: null,
		fCallback: null,
		
		init: function(fCallback, oScope, oParams) {
			this.checkMissingValue(fCallback, 'fCallback');
			
			this.fCallback = fCallback;
			this.oParams = oParams || {};
			this.oScope = oScope || false;
		},
		
		launch: function (oData) {
			oData = oData || {};

			if (this.oScope) {
				return this.fCallback.call (this.oScope, oData, this.oParams);
			} else {
				return this.fCallback (oData, this.oParams);
			}
		},
		
		// add another callback to call before launch
		prepend: function(oToAppendCallback) {
			this.addToLaunch(oToAppendCallback, 'before');
		},
		
		// add another callback to call after launch
		append: function(oToAppendCallback) {
			this.addToLaunch(oToAppendCallback, 'after');
		},
		
		addToLaunch: function(oToAddCallback, sPosition) {
			this.oParams.fOriginalLaunch = this.launch;
			this.oParams.oToAddCallback = oToAddCallback;
			if(sPosition == 'after') {
				this.launch = function(sData) {
					this.oParams.fOriginalLaunch.call(this, sData);
					if(oToAddCallback.fCallback) {
						oToAddCallback.launch();
					} else {
						oToAddCallback.call(this. sData);
					}
				};
			} else if(sPosition == 'before'){
				this.launch = function(sData) {
					if(oToAddCallback.fCallback) {
						oToAddCallback.launch();
					} else {
						oToAddCallback.call(this. sData);
					}
					this.oParams.fOriginalLaunch.call(this, sData);
				};
			}
		}
	});	
})();
