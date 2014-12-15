(function(){

	rk.widgets.tabview = rk.base.extend({
		
		aHeaderContainers: [],
		aContentContainers: [],
		
		init: function(oParams) {
			rk.util.checkMissingParam(oParams, 'aHeaders');
			rk.util.checkMissingParam(oParams, 'aContents');
			
			this.oParams = oParams;
			
			this.retrieveContainers();
		},
		
		retrieveContainers: function() {
			var i;

			if(this.aContentContainers.length != this.aHeaderContainers.length) {
				rk.util.die('headers length doest not match contents length');				
			}
			
			for(i = 0; i < this.oParams.aHeaders.length; i++) {
				$(this.oParams.aHeaders[i]).unbind('click.tabview');
				$(this.oParams.aHeaders[i]).bind('click.tabview', $.proxy(this.headerClickedHandler, this, i));
			}
		},
		
		headerClickedHandler: function(iIndex, e) {
			var i;
			for(i = 0; i < this.oParams.aContents.length; i++) {
				$(this.oParams.aContents[i]).css('display', 'none');				
			}
			$(this.oParams.aContents[iIndex]).css('display', '');
		}
	});	
	
	var self = rk.widgets.tabview;
	
})();
