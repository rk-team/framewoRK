(function() {
	
	rk.widgets.webLog = rk.base.extend({

		oContainer: null,
		oContentContainer: null,
		oHeaderContainer: null,
		oFolding: null,
		
		iNbMaxPages: null,
		
		init: function(oParams) {
			oParams = oParams || {};
			var oNode,
				oBeforeOpenCallback,
				oCloseCallback,
				oOpenCallback,
				oCookieOptions;
			
			this.iNbMaxPages = oParams.iNbMaxPages || 20;
			
			oNode = $('#webLog');
			if(oNode.length == 1) {
				this.oContainer = oNode[0];
			} else {
				this.oContainer = document.createElement('div');
				$(this.oContainer).attr('id', 'webLog');
				$('body').append(this.oContainer);
				
				this.oHeaderContainer = document.createElement('div');
				$(this.oHeaderContainer).attr('class', 'header');
				$(this.oHeaderContainer).html('<span>WebLogs</span>');
				$(this.oContainer).append(this.oHeaderContainer);
				
				oNode = document.createElement('div');
				$(oNode).attr('class', 'icon move');
				$(this.oHeaderContainer).append(oNode);
				
				this.oContentContainer = document.createElement('div');
				$(this.oContentContainer).attr('class', 'content');
				$(this.oContainer).append(this.oContentContainer);
				
				oCookieOptions = rk.util.cookie.get('webLog.options');
				if(!rk.util.isEmpty(oCookieOptions)) {
					oCookieOptions = JSON.parse(oCookieOptions);
					$(this.oContainer)
						.css('top', oCookieOptions.top)
						.css('right', oCookieOptions.right)
						.css('height', oCookieOptions.height)
						.css('width', oCookieOptions.width);
					$(this.oContentContainer)
						.css('height', oCookieOptions.contentHeight)
						.css('display', oCookieOptions.contentDisplay);
				}
			}
			
			oBeforeOpenCallback = new rk.event.callback(this.contentBeforeOpenedHandler, this);
			oOpenCallback = new rk.event.callback(this.contentOpenedHandler, this);
			oCloseCallback = new rk.event.callback(this.contentClosedHandler, this);
			
			
			
			this.oFolding = new rk.widgets.folding({
				mLabel: $('span', this.oHeaderContainer),
				mContent: this.oContentContainer,
				oBeforeOpenCallback: oBeforeOpenCallback,
				oOpenCallback: oOpenCallback,
				oCloseCallback: oCloseCallback
			});
			
			$(this.oContainer).draggable({
				handle: '.header .move',
				stop: $.proxy(this.draggableStopHandler, this)
			});
			$(this.oContainer).resizable({
				minHeight: 23,
				minWidth: 300,
				resize: $.proxy(this.resizableResizeHandler, this),
				stop: $.proxy(this.resizableStopHandler, this)
			});
		},
		
		resizableResizeHandler: function() {
			this.updateContentContainerHeight();
		},
		
		updateContentContainerHeight: function() {
			var oHeight = $(this.oContainer).height();
			
			if(oHeight > 23) {
				$(this.oContentContainer).height(oHeight - 23);
				$(this.oContentContainer).css('display', '');
			} else {
				$(this.oContentContainer).css('display', 'none');
			}
		},
		
		resizableStopHandler: function() {
			this.updateCookie();
		},
		
		draggableStopHandler: function() {
			this.updateCookie();
		},
		
		contentBeforeOpenedHandler: function() {
			$(this.oContainer).css('height', 'auto');
		},
		
		contentOpenedHandler: function() {
			this.updateCookie();
		},
		
		contentClosedHandler: function() {
			$(this.oContainer).css('height', '23px');
			this.updateCookie();
		},
		
		updateCookie: function() {
			rk.util.cookie.set('webLog.options', JSON.stringify({
				height: $(this.oContainer).height(),
				width: $(this.oContainer).width(),
				top: $(this.oContainer).css('top'),
				right: $(this.oContainer).css('right'),
				contentDisplay: $(this.oContentContainer).css('display'),
				contentHeight: $(this.oContentContainer).height()
			}));
		},
		
		
		getMeterBar: function(oData, sKey, fTotalDuration) {
			// build a "meter bar" for given data. Also adds the value and %
			var fDuration = 0.0,
				fRelativeDuration = 0.0,
				sReturn = '';
			
			if(!rk.util.isEmpty(oData[sKey])) {
				fDuration = parseFloat(oData[sKey]);
				fRelativeDuration = (100 / fTotalDuration) * fDuration;
				
				if(sKey == 'selfDuration') {
					sReturn += ' self : ';
				} else {
					sReturn += 'cumulative : ';
				}
				sReturn +=  fDuration.toFixed(4) + ' sec ';
				sReturn += '<span class="meter ' + sKey + '"><span class="value" style="width: ' + fRelativeDuration.toFixed(2) + 'px;"></span></span> (' + fRelativeDuration.toFixed(2) + '%)';
				delete oData[sKey];
			}
			
			return sReturn;
		},
		
		getTypesFromLogs: function(aLogs) {
			var i,
				oFoundTypes = {},
				sType = '';
			
			for(i = 0; i < aLogs.logs.length; i++) {
				sType = aLogs.logs[i].type;
				if(oFoundTypes[sType] === undefined) {
					oFoundTypes[sType] = 1;
				} else {
					oFoundTypes[sType]++;
				}
			}
			
			return oFoundTypes;
		},
		
		getTypesHeader: function(oAllTypes) {
			var sContent = '',
				i;
			
			// add type headers for tabview
			sContent += '<div class="logTypes">';
			sContent += '<div class="logType" data-type="ALL">ALL</div>';
			for(i in oAllTypes) {
				sContent += '<div class="logType" data-type="' + i + '">' + i + ' (' + oAllTypes[i] + ')</div>';
			}
			sContent += '</div>';
			
			return sContent;
		},
		
		getTotalDuration: function(aLogs) {
			return aLogs.logs[aLogs.logs.length - 1].data.cumulativeDuration;
		},
		
		computeIndentation: function(oData, iNbStartedActions, sType) {
			var iNbIndent = iNbStartedActions;
			
			if(sType == 'OUTPUT' && !rk.util.isEmpty(oData.END)) {
				iNbIndent--;
			}
			
			return iNbIndent;
		},
		
		handleSQLLog: function(oData) {
			if(oData.originalQuery && oData.originalBinds) {
				
				
				oData.sentQuery = '<span class="SQLFoldingHead">show</span>';
				oData.sentQuery += '<div class="SQLFoldingContent" style="display: none">Query : ' + oData.originalQuery;
				oData.sentQuery += 'Binds : ' + oData.originalBinds + '</div>';
				
				delete oData.originalQuery;
				delete oData.originalBinds;
			}
		},
		
		addLogs: function(aLogs) {
			var i, 
				j,
				sStyle,
				sContent,
				sLogContent,
				oSQLLogs,
				oCurrentPage,
				oCurrentPages = $('.logsForOnePage', this.oContainer),
				iNbCurrentPages = oCurrentPages.length,
				iPagesToRemove,
				aHeaders = [],
				fTotalDuration = 0.0,
				iNbStartedActions = 0,
				aContents = [],
				oAllTypes,
				iNbIndent = 0,
				sType;
			
			// rotate logs to limit to this.iNbMaxPages items in list
			if(iNbCurrentPages >= this.iNbMaxPages) {
				iPagesToRemove = iNbCurrentPages - (this.iNbMaxPages - 1);
				for(i = 0; i < iPagesToRemove; i++) {
					$(oCurrentPages[i]).remove();
				}
			}
			
			fTotalDuration = this.getTotalDuration(aLogs);
			oAllTypes = this.getTypesFromLogs(aLogs);
			
			sContent = '<div class="logsForOnePage">';
			sContent += '<div class="pageURL">URL : ' + decodeURI(aLogs.URL) + '</div>';
			
			sContent += '<div class="pageLogs" style="display: none">';
			
			sContent += this.getTypesHeader(oAllTypes);
			
			sContent += '<div class="logsContent">';
			for(i = 0; i < aLogs.logs.length; i++) {
				sLogContent = '';
				sType = aLogs.logs[i].type;
				
				iNbIndent = this.computeIndentation(aLogs.logs[i].data, iNbStartedActions, sType);
				
				sStyle = ' style="margin-left: ' + iNbIndent * 30 + 'px;" ';
				sLeftStyle  = ' style="margin-left: -' + iNbIndent * 30 + 'px;" ';
				
				
				
				// build log entry title
				sLogContent += '<div class="logTitle">' + aLogs.logs[i].date;
				if(!rk.util.isEmpty(aLogs.logs[i].caller)) {
					sLogContent += ' - ' + aLogs.logs[i].caller;
				}
				sLogContent += '</div>';

				// add timers
				sLogContent += '<div class="logTimers">';
				sLogContent += this.getMeterBar(aLogs.logs[i].data, 'cumulativeDuration', fTotalDuration);
				sLogContent += this.getMeterBar(aLogs.logs[i].data, 'selfDuration', fTotalDuration);
				sLogContent += '</div>';
				
				sLogContent += '<div class="oneLog"' + sStyle + '>';

				if(sType == 'SQL') {
					this.handleSQLLog(aLogs.logs[i].data);
				}
				
				for(j in aLogs.logs[i].data) {
					// count imbricated actions
					if(sType == 'OUTPUT' && j == 'START') {
						iNbStartedActions++;
					} else if(sType == 'OUTPUT' && j == 'END') {
						iNbStartedActions--;
					}
					sLogContent += '<div class="logData">';
					sLogContent += '<span class="logName">' + j + ' : </span>';
					
					sLogContent += '<span class="logValue">';
					
					
					
					sLogContent += aLogs.logs[i].data[j];
					sLogContent += '</span>';
					
					sLogContent += '</div>';				
				}
				sLogContent += '</div>';	// end .oneLog

				sContent += '<div class="logsForType" data-type="' + sType + '">' + sLogContent + '</div>';
			}
			sContent += '</div>';	// end .logsContent
			
			sContent += '</div>';	// end .pageLogs
			
			sContent += '</div>';	// end .logsForOnePage
			
			$(this.oContentContainer).append(sContent);
						
						
			oCurrentPage = $('.logsForOnePage:last-child', this.oContainer);
			
			new rk.widgets.folding({
				mLabel: $('.pageURL', oCurrentPage),
				mContent: $('.pageLogs', oCurrentPage)
			});
			
			// build the tabview for each log type
			aHeaders = [$('.logType[data-type="ALL"]', oCurrentPage)];
			aContents = [$('.logsForType', oCurrentPage)];
			for(j in oAllTypes) {
				aHeaders.push($('.logType[data-type="' + j + '"]', oCurrentPage));
				aContents.push($('.logsForType[data-type="' + j + '"]', oCurrentPage));
			}
			
			oSQLLogs = $('.logsForType[data-type="SQL"]', oCurrentPage);
			for(i = 0; i < oSQLLogs.length; i++) {
				new rk.widgets.folding({
					mLabel: $('.SQLFoldingHead', oSQLLogs[i]),
					mContent: $('.SQLFoldingContent', oSQLLogs[i])
				});
			}
		
			new rk.widgets.tabview({
				aHeaders: aHeaders,
				aContents: aContents
			});
			
			
		}
	});
	
	var self = rk.widgets.webLog;
	self.oInstance = null;
	
	self.getInstance = function() {
		if(rk.util.isEmpty(self.oInstance)) {
			self.oInstance = new rk.widgets.webLog();
		}
		
		return self.oInstance;
	};
	
})();