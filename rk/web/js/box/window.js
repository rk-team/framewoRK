(function(){

	rk.box.window = rk.base.extend({

		oParams: {},
		sId: null,
		oContainer: null,
		oHeadContainer: null,
		oContentContainer: null,
		oBackgroundContainer: null,
		
		sType: 'window',
		sDefaultId: 'defaultWindow',
		sModaleClass: rk.box.manager.sWindowClass,
		sBackgroundClass: 'box-bg',
		sHeadClass: 'head',
		sContentClass: 'content',
		sCloseButtonClass: 'close',
		sMoveButtonClass: 'move',
		
		init: function(oParams) {
			// check that we got here through the getInstance method
//			if(arguments.callee.caller.caller != rk.box.manage().createInstance 
//			&& arguments.callee.caller.caller.caller.caller != rk.box.manage().createInstance) {
//				throw Error('window constructor should not be called directly. use the rk.box.manage().createInstance method instead');
//			}
						
			this.sId = oParams.sId || this.sDefaultId;
			this.oParams = oParams;
			
			this.initContainer();
			this.initHandlers();
			this.initContent();
		},
		
		initContainer: function() {
			var sBackgroundId = this.sId + '-bg',
				oNode,
				sContent = '',
				oTopInstance,
				iNbIcons,
				iPadding,
				sClass;
			
			// add the main div
			this.oContainer = $('#' + this.sId);
			if(this.oContainer.length == 0) {
				sClass = this.sModaleClass;
				if(!rk.util.isEmpty(this.oParams.sClass)) {
					sClass += ' ' + this.oParams.sClass;
				}
				
				this.oContainer = document.createElement('div');
				$(this.oContainer).attr('id', this.sId);
				$(this.oContainer).addClass(sClass);
				$(this.oContainer).css('z-index', 10);
				
				$('body').append(this.oContainer);
			} else if(!this.oContainer.hasClass(this.sModaleClass)) {
				throw new Error('window container already exists in DOM but is not a window container');
			}
			
			// update the z-index according to other modales
			oTopInstance = rk.box.manage().getTopInstance();
			if(oTopInstance) {
				$(this.oContainer).css('z-index', parseInt($(oTopInstance.oContainer).css('z-index')) + 10);
			}
			
			// add the head div
			this.oHeadContainer = $('.' + this.sHeadClass, this.oContainer);
			if(this.oHeadContainer.length == 0) {
				this.oHeadContainer = document.createElement('div');
				$(this.oHeadContainer).addClass(this.sHeadClass);					
				$(this.oContainer).append(this.oHeadContainer);
			}
			sContent = '';
			if(!rk.util.isEmpty(this.oParams.sTitle)) {
				sContent = this.oParams.sTitle;
			}
			$(this.oHeadContainer).html('<div class="title">' + sContent + '</div>');
			
			
			// add the content div
			this.oContentContainer = $('.' + this.sContentClass, this.oContainer);
			if(this.oContentContainer.length == 0) {
				this.oContentContainer = document.createElement('div');
				$(this.oContentContainer).addClass(this.sContentClass);					
				$(this.oContainer).append(this.oContentContainer);
			}
			
			
			// add a background if needed
			if(!rk.util.isEmpty(this.oParams.bHasBackground)) {
				this.oBackgroundContainer = $('#' + sBackgroundId);
				
				if(this.oBackgroundContainer.length == 0) {
					this.oBackgroundContainer = document.createElement('div');
					$(this.oBackgroundContainer).attr('id', sBackgroundId);
					$(this.oBackgroundContainer).addClass(this.sBackgroundClass);					
					$(this.oBackgroundContainer).css('z-index', ($(this.oContainer).css('z-index') - 1));
					
					$('body').append(this.oBackgroundContainer);
				}
			}
			
			// add a close icon if needed
			if(!rk.util.isEmpty(this.oParams.bHasCloseButton)) {
				oNode = $('.' + this.sCloseButtonClass, this.oHeadContainer);
				
				if(oNode.length == 0) {
					oNode = document.createElement('div');
					$(oNode).addClass(this.sCloseButtonClass);					
					$(oNode).addClass('icon');					
					$(this.oHeadContainer).append(oNode);
				}
				
				$(oNode).bind('click', $.proxy(this.closeClickedHandler, this));
			}
			
			// add a move icon and handle the draggable if needed
			if(!rk.util.isEmpty(this.oParams.bHasMoveBUtton)) {
				oNode = $('.' + this.sMoveButtonClass, this.oHeadContainer);
				
				if(oNode.length == 0) {
					oNode = document.createElement('div');
					$(oNode).addClass(this.sMoveButtonClass);					
					$(oNode).addClass('icon');					
					$(this.oHeadContainer).append(oNode);
				}
				

				$(this.oContainer).draggable({
					handle: '.' + this.sHeadClass + ' .' + this.sMoveButtonClass,
					containment: 'document'
				});
			}
			
			// add padding-right to the title for each found icon
			iNbIcons = $('.icon', this.oHeadContainer).length;
			if(iNbIcons > 0) {
				iPadding = 8 + $('.icon', this.oHeadContainer).length * 20;
				$('.title', this.oHeadContainer)
					.css('padding-right', iPadding)
					.css('padding-left', iPadding);
			}
			
			this.refreshPosition();
		},
		
		initContent: function() {
			var oAjax,
				oCallback = new rk.event.callback(this.ajaxSuccessHandler, this);
			
			if(!rk.util.isEmpty(this.oParams.sContent)) {				
				this.updateContent(this.oParams.sContent);
			} else if(!rk.util.isEmpty(this.oParams.sURL)) {
				rk.widgets.loading.addOverlay(this.oContainer);
				this.refreshPosition();
				
				oAjax = new rk.ajax(this.oParams.sURL, oCallback);
				oAjax.query();
			}			
		},
		
		refresh: function() {
			if(!rk.util.isEmpty(this.oParams.sURL)) {
				this.initContent();
			}
		},
		
		updateContent: function(sContent) {
			rk.widgets.loading.removeOverlay(this.oContainer);
			
			$(this.oContentContainer).html(sContent);
			this.refreshPosition();

			rk.util.execJavaScript(this.oContentContainer);
			
			rk.box.manage().addLinksHandler(this.oContainer);
			
			if(this.oParams.oAfterUpdateCallback) {
				this.oParams.oAfterUpdateCallback.launch(this);
			}
		},
		
		refreshPosition: function() {
			if(!rk.util.isEmpty(this.oParams.oPosition)
					&& !rk.util.isEmpty(this.oParams.oPosition.iLeft) && !rk.util.isEmpty(this.oParams.oPosition.iTop)) {
				$(this.oContainer)
					.css('top', this.oParams.oPosition.iTop)
					.css('left', this.oParams.oPosition.iLeft);
			} else {
				rk.util.centerNode(this.oContainer);
			}
		},
		
		ajaxSuccessHandler: function(sMsg) {
			this.updateContent(sMsg);
		},
		
		closeClickedHandler: function(e) {
			this.close();
		},
		
		close: function() {
			$(this.oContainer).remove();
			$(this.oBackgroundContainer).remove();
			rk.box.manage().removeInstance(this.sId, this.sType);
		}
		
	});	
	
	rk.box.window.rkLinkClickedHandler = function(e) {
		e.preventDefault();
		
		var oParams = {
				bHasCloseButton: true, 
				bHasMoveBUtton: true, 
				sURL: $(e.target).attr('href')
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
