(function(){

	rk.box.modale = rk.box.window.extend({
		
		sType: 'modale',
		sModaleClass: 'rk-bw rk-bm',
		sDefaultId: 'defaultModale',

		init: function(oParams) {
			oParams = oParams || {};
			oParams.bHasBackground = true;
			
			this._super(oParams);
		}
		
	});	
	
	rk.box.modale.rkLinkClickedHandler = function(e) {
		e.preventDefault();
		
		var oParams = {
				bHasCloseButton: true, 
				bHasMoveBUtton: true, 
				bHasBackground: true,
				sURL: $(e.target).attr('href'),
				sType: 'modale'
			},
			sTitle = $(e.target).attr('data-rkWindowTitle'),
			sId = $(e.target).attr('data-rkModaleId');
		
		if(!rk.util.isEmpty(sTitle)) {
			oParams.sTitle = sTitle;
		}
		if(!rk.util.isEmpty(sId)) {
			oParams.sId = sId;
		}
		
		rk.box.manage().createInstance(oParams);
	};
	
})();
