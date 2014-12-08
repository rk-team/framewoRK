(function(){

	rk.widgets.pager = rk.base.extend({
		
		iCurrentPage: null,
		oContainer: null,
		sBaseURL: null,
		
		isSortable: false,
		aOldOrder: [],
		
		oAdvancedFilters: null,
		
		bHasPagination: true,
		
		init: function(oParams) {
			
			if(arguments.callee.caller.caller != self.createInstance) {
				throw Error('pager constructor should not be called directly. use the rk.widgets.pager.createInstance method instead');
			}
			
			this.checkMissingParam(oParams, 'sId');
			this.checkMissingParam(oParams, 'sBaseURL');
			this.checkMissingParam(oParams, 'mContainer');
			
			if (rk.util.isEmpty(oParams.bSortable)) {
				this.checkMissingParam(oParams, 'iNbPaginationLinks');
				this.checkMissingParam(oParams, 'iNbPages');
			}
						
			this.iCurrentPage = oParams.iCurrentPage || 1;
			this.sId = oParams.sId;
			this.sBaseURL = oParams.sBaseURL;
			
			this.bHasPagination = oParams.bHasPagination || true;
			
			this.oParams = oParams;
			
			this.retrieveContainers();
			
			this.initHandlers();
			
			this.updateActionColumnWidth();
			
			this.highlightResults();
		},
		
		highlightResults: function(mContainer) {
			// by default, we want to highlight the content of the pager
			MContainer = mContainer || $('tbody', this.oContainer);
			var i,
				j,
				k,
				sToSearch,
				sReplaceBy,
				sHTML,
				sNewHTML,
				oTDs,
				oContainer = rk.util.getContainer(mContainer);
			
			if(!rk.util.isEmpty(this.oParams.oSearchHighlights)) {
				for(i in this.oParams.oSearchHighlights) {
					// highlights are indexed by filter name
					oTDs = $('[data-col="' + i + '"][data-table="' + this.oParams.sModel + '"]', oContainer);
					if(oTDs.length > 0) {
						for(j = 0; j < oTDs.length; j++) {
							// for each column with highlight
							for(k = 0; k < this.oParams.oSearchHighlights[i].length; k++) {
								// foreach highlight value, we replace the content of the TD
								sHTML = $(oTDs[j]).html();
								if(this.oParams.oSearchHighlights[i][k].operator == 'ilike') {
									sToSearch = this.oParams.oSearchHighlights[i][k].value;
									// removing heading % for ilike
									if(sToSearch.indexOf('%') === 0) {
										sToSearch = sToSearch.substr(1);
									}
									// removing trailing % for ilike
									if(sToSearch.indexOf('%') === sToSearch.length - 1) {
										sToSearch = sToSearch.substr(0, sToSearch.length - 1);
									}
									sNewHTML = this.getHighlightedString(sToSearch, sHTML);
									$(oTDs[j]).html(sNewHTML);
								} else if(this.oParams.oSearchHighlights[i][k].operator == 'equal') {
									if(sHTML == this.oParams.oSearchHighlights[i][k].value) {
										sReplaceBy = '<span class="highlight">' + this.oParams.oSearchHighlights[i][k].value + '</span>';
										$(oTDs[j]).html(sReplaceBy);
									}
								}								
							}
						} 
					}
				}
			}
		},
		
		getHighlightedString: function(sToSearch, sString) {
			var iPos,
				sStart,
				sValue,
				sEnd,
				iLength,
				sReturn = '',
				oRegexp = new RegExp(sToSearch, 'gi');
			
			iPos = sString.search(oRegexp);
			if(iPos != -1) {
				sStart = sString.substr(0, iPos);
				sValue = sString.substr(iPos, sToSearch.length);
				sEnd = sString.substr(iPos + sToSearch.length);
				sReturn = sStart + '<span class="highlight">' + sValue + '</span>';
				iLength = sReturn.length;
				iPos = sEnd.search(oRegexp);
				if(iPos != -1) {
					// if search string is still present in sEnd, we add the highlight to the end of our sReturn
					sReturn += this.getHighlightedString(sToSearch, sEnd);
				} else {
					sReturn += sEnd;
				}
			} else {
				sReturn = sString;
			}
			
			return sReturn;
		},
		
		retrieveContainers: function() {
			var oFiltersHeader,
				oFiltersContent,
				oAdvancedFilters;
			
			this.oContainer = $(this.oParams.mContainer);
			
			oFiltersHeader = $('.filtersHeader', this.oContainer);
			oFiltersContent = $('.filtersContent', this.oContainer);
			
			if(oFiltersHeader.length > 0 && oFiltersContent.length > 0) {
				new rk.widgets.folding({
					mLabel: 	oFiltersHeader,
					mContent: 	oFiltersContent
				});
				oAdvancedFilters = $('.advancedFilters', this.oContainer);
				if(oAdvancedFilters.length > 0) {
					this.oAdvancedFilters = rk.util.advancedFilters.getInstance($(oAdvancedFilters).attr('id'));
				}
			}
			
		},
		
		initHandlers: function() {
			// pagination links
			$('.pagination a', this.oContainer).unbind('click.pager');
			$('.pagination a', this.oContainer).bind('click.pager', $.proxy(this.paginationLinkClickedHandler, this));
			
			// sortable columns
			$('.sortButtons a', this.oContainer).unbind('click.pager');
			$('.sortButtons a', this.oContainer).bind('click.pager', $.proxy(this.columnSortableLinkClickedHandler, this));
			
			// different types of rk box
			$('a.rkConfirm', this.oContainer).unbind('click.pagerConfirm');
			$('a.rkConfirm', this.oContainer).bind('click.pagerConfirm', $.proxy(this.confirmLinkClickedHandler, this));
			
			$('a.rkModale', this.oContainer).unbind('click.pagerModale');
			$('a.rkModale', this.oContainer).bind('click.pagerModale', $.proxy(this.modaleLinkClickedHandler, this));
			
			$('a.rkWindow', this.oContainer).unbind('click.pagerWindow');
			$('a.rkWindow', this.oContainer).bind('click.pagerWindow', $.proxy(this.windowLinkClickedHandler, this));
			
			$('a.move', this.oContainer).unbind('click.move');
			$('a.move', this.oContainer).bind('click.move', $.proxy(this.moveClickedHandler, this));
			
			if (!rk.util.isEmpty(this.oParams.bSortable)) {
				this.setSortable();
			}
		},
		
		moveClickedHandler: function(e) {
			e.preventDefault();
		},
		
		getSortableOrder: function() {
			var aOrder = [],
				oRows = $('.row', this.oContainer),
				i;
			
			for(i = 0; i < oRows.length; i++) {
				aOrder[i] = $(oRows[i]).attr('data-pk');
			}
			
			return aOrder;
		},
		
		sortStopHandler: function () {
			var aOrder;
			
			aOrder = this.getSortableOrder();
			
			if (JSON.stringify(aOrder) != JSON.stringify(this.aOldOrder)) {
				this.aOldOrder = aOrder;
				this.refresh(rk.util.url.addParams(this.sBaseURL, {
					changeOrder: aOrder
				}));
			}
		},
		
		setSortable: function() {
			this.isSortable = true;
			this.aOldOrder = this.getSortableOrder();
			
			$('.content', this.oContainer).sortable({
				handle: $('.move', $('.content', this.oContainer)),
				stop: $.proxy(this.sortStopHandler, this),
				opacity: 0.3
			});
		},
		
		/**
		 * @desc set the width of the action column according to the size of its <a>
		 */
		updateActionColumnWidth: function() {
			var oActionTDs = $('td[data-col="actions"]', this.oContainer),
				oActionLinks,
				i,
				iWidth = 0;
			
			if(oActionTDs.length > 0) {
				oActionLinks = $('a', oActionTDs[0]);
				for(i = 0; i < oActionLinks.length; i++) {
					iWidth += $(oActionLinks[i]).outerWidth(true);
				}
				$('th[data-col="actions"]', this.oContainer).css('width', iWidth);
			}
		},
		
		paginationLinkClickedHandler: function(e) {
			e.preventDefault();
			rk.ajax.updater($(e.target).attr('href'), this.oContainer);
		},
		
		columnSortableLinkClickedHandler: function(e) {
			e.preventDefault();
			rk.ajax.updater($(e.target).attr('href'), this.oContainer);
		},
		
		confirmLinkClickedHandler: function(e) {
			// disable all other handlers on link
			e.preventDefault();
			e.stopImmediatePropagation();
			
			this.launchRKLink('confirm', {
				sValidateURL: $(e.target).attr('href'),
				sText: $(e.target).attr('data-rkConfirmText'),
				sTitle: $(e.target).attr('data-rkWindowTitle')
			});
		},
		
		modaleLinkClickedHandler: function(e) {
			// disable all other handlers on link
			e.preventDefault();
			e.stopImmediatePropagation();
			
			this.launchRKLink('modale', {
				sURL: $(e.target).attr('href'),
				sTitle: $(e.target).attr('data-rkWindowTitle')
			});
		},		
		
		windowLinkClickedHandler: function(e) {
			// disable all other handlers on link
			e.preventDefault();
			e.stopImmediatePropagation();
			
			this.launchRKLink('window', {
				sURL: $(e.target).attr('href'),
				sTitle: $(e.target).attr('data-rkWindowTitle')
			});
		},
		
		/**
		 * @desc handles the creation of a box after a rk box link is clicked
		 * @param sType		string : window|modale|confirm
		 * @param oParams	object
		 */
		launchRKLink: function(sType, oParams) {
			var sId = this.sId + '-' + sType,
				oDefaultParams = {
					sId: sId,
					bHasCloseButton: true, 
					bHasMoveBUtton: true,
					sType: sType
				},
				oBoxParams = {},
				oCallbackParams = {};

			$.extend(oBoxParams, oDefaultParams, oParams);
			
			if(sType == 'confirm') {
				// for confirm, we want to add a callback that refresh the pager and close the confirm when validate is clicked
				oBoxParams.oValidateCallback = new rk.event.callback(this.afterConfirmHandler, this);
			} else {
				// for both window and modale we want to refresh the pager IF the modale contains a form that has been successfully submitted
				if(sType == 'modale') {
					// for modale we also want to close the modale
					oCallbackParams.bCloseModale = true;
				}
				oBoxParams.oAfterUpdateCallback = new rk.event.callback(this.afterRKWindowUpdateHandler, this, oCallbackParams);
			}
			
			rk.box.manage().createInstance(oBoxParams);
		},
		
		afterRKWindowUpdateHandler: function(oWindow, oParams) {
			// search for forms in given modale, and add them a callback
			var oForms = $('form', oWindow.oContainer),
				i,
				oFormInstance;
			
			
			this.highlightResults(oWindow.oContainer);
			
			for(i = 0; i < oForms.length; i++) {
				oFormInstance = rk.util.form.getInstance($(oForms[i]).attr('id'));
				if(oFormInstance) {
					oFormInstance.oParams.oAfterUpdateCallback = new rk.event.callback(this.afterFormUpdateHandler, this, oParams);
				}
			}
		},
		
		afterFormUpdateHandler: function(oForm, oParams) {	
			var oModale;
			if(oForm.isSuccess()) {
				oModale = rk.box.manage().getWindowForNode(oForm.oContainer);
				if(oModale) {
					if(oParams.bCloseModale) {
						oModale.close();
					}
					this.refresh();
				}
			}
		},
		
		
		afterConfirmHandler: function(oConfirm) {
			this.refresh();
			oConfirm.close();
		},
		
		refresh: function(sURL) {
			sURL = sURL || this.getURL();
			rk.ajax.updater(sURL, this.oContainer);
		},
		
		getURL: function() {
			var oParams = {};
			
			if (this.bHasPagination) {
				oParams.page = this.iCurrentPage;
			}
			if(!rk.util.isEmpty(this.oAdvancedFilters)) {
				oParams.criterias = JSON.stringify(this.oAdvancedFilters.getCriteriaSet());
			}
//			if(!rk.util.isEmpty(this.oParams.oAdvancedCriterias)) {
//				oParams.criteria = this.oParams.oAdvancedCriterias;
//			}
			
			return rk.util.url.addParams(this.sBaseURL, oParams);
		}
		
	});

	
	var self = rk.widgets.pager;
	
	self.oInstances = {};
	
	self.createInstance = function(oParams) {
		oParams = oParams || {};
		
		self.oInstances[oParams.sId] = new rk.widgets.pager(oParams);
	};
	
	self.getInstance = function(sId) {
		if (rk.util.isEmpty(self.oInstances[sId])) {
			return false;
		}
		return self.oInstances[sId];
	};
	
	self.hasInstance = function(sId) {
		if (!rk.util.isEmpty(self.oInstances[sId])) {
			return true;
		}
		return false;
	};
	
	self.removeInstance = function(sId) {
		if (rk.util.isEmpty(self.oInstances[sId])) {
			throw Error('unknown pager id ' + sId);
		}
	};
	
	self.refreshPager = function(sId) {
		if(self.hasInstance(sId)) {
			self.getInstance(sId).refresh();
		}
	};
})();
