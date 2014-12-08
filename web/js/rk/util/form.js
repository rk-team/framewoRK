(function(){
	
	rk.util.form = rk.base.extend({

		oParams: {},
		oContainer: null,
		sId: '',
		oUpdateContainer: null,
		
		init: function(oParams) {
			
			oParams = oParams || {};			
			this.checkMissingParam(oParams, 'sId');
			this.oContainer = this.checkValidContainer('#' + oParams.sId);
			
			if(!rk.util.isEmpty(oParams.sUpdateContainer)) {
				this.oUpdateContainer = rk.util.getContainer(oParams.sUpdateContainer);
			}			

			this.oParams = oParams;
			this.sId = oParams.sId;
			
			this.initForm();
		},
		
		initForm: function() {		
			
			if (($(this.oContainer).prop('tagName').toLowerCase() == 'form') && $(this.oContainer).hasClass('noAjaxify') == false) {
				
				$(this.oContainer).unbind('submit.form');
				$(this.oContainer).bind('submit.form', $.proxy(this.submitHandler, this));
				
				$(this.oContainer).unbind('reset.form');
				$(this.oContainer).bind('reset.form', $.proxy(this.resetHandler, this));
			} 
			else {
				rk.util.die('container isn\'t a valid form');
			}
		},
		
		addedButtonClickHandler: function (e) {
			var oTarget = $(e.sData.currentTarget);
			if(!$(oTarget).hasClass('noSubmit')) {
				this.submitWithAddedButton(oTarget);				
			}
		},
		
		submitWithAddedButton: function (oTarget) {
			var oHiddenAssociatedInput = $('input[name="' + $(oTarget).attr('data-name') + '"]', this.oContainer);
			
			if (oHiddenAssociatedInput.length == 0) {
				oHiddenAssociatedInput = document.createElement('input');
				$(oHiddenAssociatedInput).attr('name', $(oTarget).attr('data-name'));
				$(oHiddenAssociatedInput).attr('type', 'hidden');
				$(this.oContainer).append(oHiddenAssociatedInput);
			}
			
			$(oHiddenAssociatedInput).attr('value', $(oTarget).attr('data-value'));
			this.submit();
		},

		handleIFrameServerResponse: function() {
			var sData = $(self.oFileIFrame).contents().find('body').html();
			this.refreshContent(sData);
		},
		
		postFiles: function() {
			var sURL = $(this.oContainer).attr('action'),
				oClone = {};
			
			sURL = rk.util.url.addParams(sURL, {rkForceAjax: 1, _rkFormId: $(this.oContainer).attr('id')});
			
			$(self.oFileIFrame).contents().find('body').html('');
			oClone = $(this.oContainer).clone();			
			$(oClone).attr('action', sURL);
			
			$(self.oFileIFrame).contents().find('body').append(oClone);
			$(self.oFileIFrame).contents().find('form')[0].submit();
		},
		
		loadIFrameHandler: function() {
			this.handleIFrameServerResponse();
		},
		
		submit: function() {
			var oCallback,
				oAjax,
				oFormParams = rk.util.form.getFieldsValue(this.oContainer),
				sURL;
									
			this.showLoading();
			
			sURL = $(this.oContainer).attr('action') || document.location.href;
			
			oFormParams._rkFormId = $(this.oContainer).attr('id');
			
			oFiles = $('input[type=file]', this.oContainer);
			if (oFiles.length > 0) {
				if (rk.util.isEmpty(self.oFileIFrame)) {
					//Create iFrame
					self.oFileIFrame = document.createElement('iframe');
					$(self.oFileIFrame).css('display', 'none');
					$(self.oFileIFrame).attr('src', 'about:blank');
					$(self.oFileIFrame).attr('id', self.sFileIFrameId);
					$(self.oFileIFrame).attr('name', self.sFileIFrameId);
					
					$('body').append(self.oFileIFrame);
					
				}
				//binding loading state
				$(self.oFileIFrame).unbind('load.iframe');
				$(self.oFileIFrame).bind('load.iframe', $.proxy(this.loadIFrameHandler, this));
				
				this.postFiles();
			} 
			else {				
				oCallback = new rk.event.callback(this.refreshContent, this, {});
				oAjax = new rk.ajax(sURL, oCallback, {sData: oFormParams});
				oAjax.query();
			}			
		},
		
		refreshContent: function (sData) {		
			var oModale = rk.box.manage().getWindowForNode(this.oContainer),
				oContainer = this.oContainer;
			
			if(!rk.util.isEmpty(this.oUpdateContainer)) {
				oContainer = this.oUpdateContainer;
			}
			//use ajax refesh content, cause sData is retrieve with ajax
			rk.ajax.postUpdater(sData, oContainer);
			
			oContainer = $('#' + this.sId);
			if(oContainer) {
				// the form is found back in new data
				if(!rk.util.isEmpty(this.oParams.oAfterUpdateCallback)) {
					this.oParams.oAfterUpdateCallback.launch(this);
				}
				
				if(oModale) {
					oModale.refreshPosition();
				}
			} else {
				// the form has not been returned, it doesnt exist anymore
				rk.util.form.removeInstance(this.sId);
			}
						
			
		},
		
		isSuccess: function() {
			return !rk.util.isEmpty(this.oParams.bSuccess);
		},
		
		submitHandler: function(oEvent, oParams) {
			oEvent.preventDefault();
			this.submit();
		},
						
		resetHandler: function(oEvent, oParams) {
			oEvent.preventDefault();
			$('.widget input, .widget select', this.oContainer).val('');
		},
		
		removeLoading: function () {
			if(!rk.util.isEmpty(this.oUpdateContainer)) {
				rk.widgets.loading.removeOverlay(this.oUpdateContainer);
			} else {
				rk.widgets.loading.removeOverlay(this.oContainer);				
			}
		},
		
		showLoading: function () {
			if(!rk.util.isEmpty(this.oUpdateContainer)) {
				rk.widgets.loading.addOverlay(this.oUpdateContainer);
			} else {
				rk.widgets.loading.addOverlay(this.oContainer);				
			}
		}
	});
	
	rk.util.form.oInstance = {};
	rk.util.form.getInstance = function (mParams) {
		var sId,
			oParams,
			oNewParams = {};
		
		if(typeof mParams == 'string') {
			sId = mParams;
			oParams = false;
		} else {
			if(rk.util.isEmpty(mParams) || rk.util.isEmpty(mParams.sId)) {
				rk.util.die('invalid params');
			}
			sId = mParams.sId;
			oParams = mParams;
		}
		
				
		if (rk.util.isEmpty(self.oInstance[sId])) {
			// no instance, we create it if params were given
			if(rk.util.isEmpty(oParams)) {
				rk.util.die('unknown instance');
			}
			self.oInstance[sId] = new rk.util.form(oParams);
		} else if(!rk.util.isEmpty(oParams)) {
			// instance already exists : we update its params by calling init
			$.extend(oNewParams, self.oInstance[sId].oParams, oParams);
			self.oInstance[sId].init(oNewParams);
		}
		
		return self.oInstance[sId];
	};
	
	rk.util.form.removeInstance = function(sId) {
		if (!rk.util.isEmpty(self.oInstance[sId])) {
			delete self.oInstance[sId];
		}
	};
	
	rk.util.form.checkPatternForInput = function(oInput) {
		var rPattern = new RegExp($(oInput).attr('pattern'));
		if(!rk.util.isEmpty(rPattern)) {
			return rPattern.test($(oInput).val());
		}
		return false;
	};
	
	rk.util.form.getFieldValue = function(aInput) {
		var sValue = '';
		
		if (aInput.type == 'checkbox') {
	    	sValue = aInput.checked ? '1' : '0';
	    } 
		else if (aInput.type == 'radio') {
	    	sValue = aInput.checked ? $(aInput).val() : false;
	    } 
		else {
	    	sValue = $(aInput).val();
	    }
	
		return sValue;
	};

	rk.util.form.getFieldsValue = function(mForm) {
	    var myObj, 
	    	aInputs, 
	    	oPostParams = {},
	    	sName,
		    i,
		    sValue;
		
	    myObj = $(mForm);
	
	    aInputs = $('input, textarea, select', myObj);
	
	    for(i = 0; i < aInputs.length; i++) {
	    	if (!rk.util.isEmpty(aInputs[i].name)) {
	    		if(aInputs[i].name.indexOf('[]') == aInputs[i].name.length - 2) {
	    			sName = aInputs[i].name.substr(0, aInputs[i].name.length - 2);
	    			if(rk.util.isEmpty(oPostParams[sName])) {
	    				oPostParams[sName] = [];
	    			}	    			
	    			sValue = rk.util.form.getFieldValue(aInputs[i]);
	    			if (sValue !== false) {
	    				oPostParams[sName].push(sValue);
	    			}
	    		} else {
	    			sValue = rk.util.form.getFieldValue(aInputs[i]);
	    			if (sValue !== false) {
	    				oPostParams[aInputs[i].name] = sValue;	
	    			}
	    		}
	    		
	    	}
	    }
		
	    return oPostParams;
	};
	
	var self = rk.util.form;
	
	rk.util.form.sFileIFrameId = 'rkFileIFrame';
	rk.util.form.oFileIFrame = {};
	
})();

