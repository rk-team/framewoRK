(function(){

	rk.box.confirm = rk.box.modale.extend({
		
		sType: 'confirm',
		sModaleClass: 'rk-bw rk-bc',
		sDefaultId: 'defaultConfirm',
		
		init: function(oParams) {
			
			/**
			 * Possible oParams items :
			 * 		sId				=> id of the confirm
			 * 		sText			=> text of the confirm
			 * 		bHasMoveBUtton
			 * 		bHasCloseButton
			 * 		bHasBackground
			 * 		oValidateCallback	=> callback to be launched when validate is clicked
			 * 		sValidateURL		=> URL to call when validate is clicked
			 * 
			 */
			
			
			// check that we got here through the getInstance method
//			if(arguments.callee.caller.caller != rk.box.manage().createInstance) {
//				throw Error('confirm constructor should not be called directly. use the rk.window.confirm.createInstance method instead');
//			}
			
			if(oParams.sId === undefined) {
				oParams.sId = self.sDefaultId;
			}
			if(rk.util.isEmpty(oParams.sText)) {
				oParams.sText = rk.util.i18n.get('confirm.are_you_sure');
			}
			if(oParams.bHasMoveBUtton === undefined) {
				oParams.bHasMoveBUtton = true;
			}
			if(oParams.bHasCloseButton === undefined) {
				oParams.bHasCloseButton = true;
			}
			if(oParams.bHasBackground === undefined) {
				oParams.bHasBackground = true;
			}

			if(oParams.sClass === undefined) {
				oParams.sClass = self.sModaleClass;				
			}
			
			this.sId = oParams.sId || this.sDefaultId;
			this.oParams = oParams;
			
			this.initContainer();
			this.initHandlers();
			this.initContent();
		},

		initContainer: function() {
			this._super();
			
			var oButtonContainers,
				oNode;
			
			oNode = document.createElement('div');
			$(oNode).addClass('message');
			$(oNode).html(this.oParams.sText);
			$(this.oContentContainer).append(oNode);
			
			oButtonContainers = document.createElement('div');
			$(oButtonContainers).addClass('buttons');
			$(this.oContentContainer).append(oButtonContainers);
			
			oNode = document.createElement('button');
			$(oNode).addClass('button validate');
			$(oNode).html(rk.util.i18n.get('confirm.validate'));
			$(oButtonContainers).append(oNode);
			
			oNode = document.createElement('button');
			$(oNode).addClass('button cancel');
			$(oNode).html(rk.util.i18n.get('confirm.cancel'));
			$(oButtonContainers).append(oNode);
		},

		initHandlers: function() {
			this._super();
			
			$('.buttons .cancel', this.oContentContainer).bind('click', $.proxy(this.cancelClickedHandler, this));
			$('.buttons .validate', this.oContentContainer).bind('click', $.proxy(this.validateClickedHandler, this));
		},
		
		cancelClickedHandler: function() {
			this.close();
		},
		
		validateClickedHandler: function() {
			// call the given URL (if any)
			if(!rk.util.isEmpty(this.oParams.sValidateURL)) {
				rk.ajax.updater(this.oParams.sValidateURL, this.oContentContainer);
			}
			
			// call the given callback (if any)
			if(!rk.util.isEmpty(this.oParams.oValidateCallback)) {
				this.oParams.oValidateCallback.launch(this);
			}
		}
		
	});	
	
	rk.box.confirm.rkLinkClickedHandler = function(e) {
		e.preventDefault();
		
		var oParams = {
				bHasCloseButton: true, 
				bHasMoveBUtton: true, 
				bHasBackground: true,
				sValidateURL: $(e.target).attr('href'),
				sType: 'confirm'
			},
			sTitle = $(e.target).attr('data-rkWindowTitle'),
			sId = $(e.target).attr('data-rkModaleId'),
			sText = $(e.target).attr('data-rkConfirmText');
					
		if(rk.util.isEmpty(sText)) {
			sText = rk.util.i18n.get('confirm.are_you_sure');
		}
		oParams.sText = sText;
		
		if(!rk.util.isEmpty(sTitle)) {
			oParams.sTitle = sTitle;
		}
		if(!rk.util.isEmpty(sId)) {
			oParams.sId = sId;
		}
		
		rk.box.manage().createInstance(oParams);
	};
})();
