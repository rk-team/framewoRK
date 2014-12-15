(function(){

	rk.ajax = rk.base.extend({

		sType: 'POST',
		sUrl: '',
		sData: '',
		sDataType: '',
		oCallBack: null,
		oErrorCallback: null,
		oAbortCallback: null,
		oJqAjax: null,
		
		init: function(sUrl, oCallBack, oParams) {
			this.checkMissingValue(sUrl, 'sUrl');
			
			oParams = oParams || {};
			
			this.sUrl = sUrl;
			this.oCallBack = oCallBack;
			
			this.oErrorCallback = oParams.oErrorCallback || this.oErrorCallback;		
			this.oAbortCallback = oParams.oAbortCallback || this.oAbortCallback;		
			this.sData = oParams.sData || this.sData;
			this.sType = oParams.sType || this.sType;
			this.sDataType = oParams.sDataType || this.sDataType;
		},
			
		query: function () {
			var oSuccessCallback = this.oCallBack,
				oQueryParams;
			
			oQueryParams = {
				type:     this.sType,
				url:      this.sUrl,
				data:     this.sData,
				success:  function(sMsg){
					if(oSuccessCallback.launch) {
						oSuccessCallback.launch(sMsg);						
					} else {
						oSuccessCallback.call(this, sMsg);
					}
				},
				error:	$.proxy(this.errorHandler, this)
			};
			if (this.sDataType !== '') {
				oQueryParams.dataType = this.sDataType;
			}

			this.oJqAjax = $.ajax(oQueryParams);
		},
		
		errorHandler: function(oXHR, sStatus, sError) {
			var oData = {};
			oData.sResponse =  oXHR.responseText;
			oData.sStatus = sStatus;
			oData.sError = sError;
			
			if(sStatus == 'abort' && this.oAbortCallback !== null) {
				this.oAbortCallback.launch.call(this.oAbortCallback, oData);
			} else if(this.oErrorCallback !== null) {
				this.oErrorCallback.launch.call(this.oErrorCallback, oData);
			} else {
				this.oCallBack.launch.call(this.oCallBack, oData.sResponse);
			}
		},
		
		abort: function() {
			if(this.oJqAjax && this.oJqAjax.readyState != 4) {
				this.oJqAjax.abort();
			}			
		}
	});

	
	rk.ajax.updater = function (sUrl, mContainer, oParams) {
		oParams = oParams || {};
		
		var oCallback,
			oAjax,
			oContainer = rk.util.getContainer(mContainer);
		
		oCallback = new rk.event.callback(rk.ajax._ajaxUpdaterHandler, false, {oContainer: oContainer});
		
		if(oParams.oAfterUpdateCallback) {
			oCallback.append(oParams.oAfterUpdateCallback);
		}

		rk.widgets.loading.addOverlay(oContainer);
		
		oAjax = new rk.ajax(sUrl, oCallback, oParams);
		oAjax.query();
	};
	
	rk.ajax.postUpdater = function(sAjaxHTML, oTargetContainer) {
		rk.widgets.loading.removeOverlay(oTargetContainer);

		var oTmpDiv = document.createElement('div'),
			oChildren,
			aClasses,
			aOriginalClasses,
			bReplace = false,
			sOriginalId = '',
			sNewId = '',
			i,
			aToAddChildren = [],
			oFirstChild = false,
			sHTML = '';
		
		$(oTmpDiv).html(sAjaxHTML);
		
		//We care about script later
		oChildren = $(oTmpDiv).children(':not(script)');
		
		if(oChildren.length === 1) {
			// If the direct first son of e.oContainer is the same as the original one, we keep the original
			// and drop the e.oContainer one
			oFirstChild = oChildren[0];
			if($(oFirstChild).attr('id') !== undefined) {
				sOriginalId = $(oTargetContainer).attr('id');
				sNewId = $(oFirstChild).attr('id');
				if(sOriginalId == sNewId) {
					// same id => replacement
					bReplace = true;
				}
			} else {
				if(oFirstChild.tagName == oTargetContainer.tagName) {
					aClasses = $(oFirstChild).attr('class');
					aClasses = aClasses.split(' ');
					
					aOriginalClasses = $(oTargetContainer).attr('class');
					aOriginalClasses = $.trim(aOriginalClasses).split(' ');
										
					if(aClasses.length === aOriginalClasses.length) {
						bReplace = true;
						for(i = 0; i < aClasses.length; i++) {
							if(aOriginalClasses[i] != aClasses[i]) {
								// same tagName and Classes => replacement
								bReplace = false;
							}
						}						
					}
				}
			}
		}
	
		
		if(bReplace) {
			
			sHTML += $(oFirstChild).html();
			
			// and add the scripts
			aToAddChildren = $(oTmpDiv).children('script');
			for(i = 0; i < aToAddChildren.length; i++) {
				sHTML += $(aToAddChildren[i]).outerHTML();
			}
			
			$(oTargetContainer).html(sHTML);
		} else {
			$(oTargetContainer).html(sAjaxHTML);
		}

		rk.util.execJavaScript(oTargetContainer);
		rk.box.manage().addLinksHandler(oTargetContainer);
	};
	
	rk.ajax.refreshContent = function(sData, oNode) {
		rk.ajax.postUpdater(sData, oNode);
	};
	
	rk.ajax._ajaxUpdaterHandler = function(sResponse, oParams) {
		rk.ajax.postUpdater(sResponse, oParams.oContainer);
	};
})();
