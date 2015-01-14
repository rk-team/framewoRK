(function(){

	rk.box.manager = rk.base.extend({
		
		oInstances: {},
		sModaleClass: 'rk-wm',
		
		init: function() {
			this.initHandlers();
		},
		
		initHandlers: function() {
			// handle keyboard events
			$(document).unbind('keyup.box');
			$(document).bind('keyup.box', $.proxy(this.keyupHandler, this));
		},
		
		keyupHandler: function(e) {
			var oInstance;
			if (e.originalEvent.which == 27) {
				oInstance = this.getTopInstance();
				if(oInstance) {
					oInstance.close();
				}
		    }
		},
		
		createInstance: function(oParams) {
			oParams = oParams || {};
			
			var oBox;
			
			if(rk.util.isEmpty(oParams.sType)) {
				oParams.sType = 'window';
			}
			
			switch(oParams.sType) {
				case 'window':
					oBox = new rk.box.window(oParams);
				break;
				
				case 'modale':
					oBox = new rk.box.modale(oParams);
				break;
				
				case 'confirm':
					oBox = new rk.box.confirm(oParams);
				break;
				
				default:
					rk.util.die('unknown type ' + oParams.sType);
				break;
			}
			
			this.oInstances[oBox.sId] = oBox;			
		},
		
		getInstance: function(sId) {
			if (rk.util.isEmpty(this.oInstances[sId])) {
				return false;
			}
			return this.oInstances[sId];
		},
		
		getTopInstance: function() {
			var oModale,
				i,
				iMaxZIndex = 0,
				sTopModaleId = '';
			
			for(i in this.oInstances) {
				oModale = $('#' + this.oInstances[i].sId);
				if(iMaxZIndex < $(oModale).css('z-index')) {
					iMaxZIndex = $(oModale).css('z-index');
					sTopModaleId = $(oModale).attr('id');
				}
			}
			if(sTopModaleId === '') {
				return false;
			}
			return this.oInstances[sTopModaleId];
			
		},
		
		removeInstance: function(sId) {
			if (rk.util.isEmpty(this.oInstances[sId])) {
				rk.util.die('unknown instance id ' + sId);
			}
			delete this.oInstances[sId];
		},
		
		addLinksHandler: function(mContainer) {
			mContainer = mContainer || 'body';
			
			var oContainer = rk.util.getContainer(mContainer);

			$('a.rkWindow', oContainer).unbind('click.rkWindow');
			$('a.rkWindow', oContainer).bind('click.rkWindow', rk.box.window.rkLinkClickedHandler);
					
			$('a.rkModale', oContainer).unbind('click.rkModale');
			$('a.rkModale', oContainer).bind('click.rkModale', rk.box.modale.rkLinkClickedHandler);
			
			$('a.rkConfirm', oContainer).unbind('click.rkConfirm');
			$('a.rkConfirm', oContainer).bind('click.rkConfirm', rk.box.confirm.rkLinkClickedHandler);
			
			$('a.rkPopup', oContainer).unbind('click.rkPopup');
			$('a.rkPopup', oContainer).bind('click.rkPopup', rk.box.manager.rkPopupClickedHandler);
		},
		
		getWindowForNode: function(mNode) {
			var oNode = rk.util.getContainer(mNode),
				oParent;
			
			oParent = $(oNode).closest('.' + rk.box.manager.sWindowClass);
			
			if(oParent.length > 0) {
				return this.getInstance($(oParent).attr('id'));
			}
			return false;
		}
	});
	
	rk.box.manager.rkPopupClickedHandler = function(e) {
		e.preventDefault();
		
		var sHeight = 600,
			sWidth = 600;
		
		if($(e.target).attr('data-popupheight')) {
			sHeight = $(e.target).attr('data-popupheight');
		}
		if($(e.target).attr('data-popupwidth')) {
			sWidth = $(e.target).attr('data-popupwidth');
		}
		
		window.open($(e.target).attr('href'), '', "height=" + sHeight + ",width=" + sWidth);
	};
	
	rk.box.manager.sWindowClass = 'rk-bw';
	rk.box.manager.oInstance = {};
	rk.box.manage = function() {
		if(rk.util.isEmpty(rk.box.manager.oInstance)) {
			rk.box.manager.oInstance = new rk.box.manager();
		}
		return rk.box.manager.oInstance;
	};
	
})();
