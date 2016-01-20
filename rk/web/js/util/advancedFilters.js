(function(){
	
	rk.util.advancedFilters = rk.util.form.extend({

		oSummaryContainer: null,
		aCurrentCriterias: [],
		sBetweenFiltersOperator: 'and',
		sBetweenSameFilterCriteriasOperator: 'or',
		
		sSummaryClass: 'advancedFilterSummary',
		
		initForm: function() {		
			this._super();
			
			this.initAdvancedFilters();
			this.initPresets();
		},
		
		initPresets: function() {
			var oButton = $('.presets button.load', this.oContainer);
			
			if(oButton.length > 0) {
				$(oButton).bind('click.preset', $.proxy(this.loadPresetClickedHandler, this));
			}
		},
		
		loadPresetClickedHandler: function(e) {
			var oSelectedOption = $('.presets select option:selected', this.oContainer),
				oCriteriaSet = {};
			
			if(oSelectedOption.length > 0) {
				if(!rk.util.isEmpty(this.oParams.oPresets[oSelectedOption.val()])) {
					oCriteriaSet = $.parseJSON(this.oParams.oPresets[oSelectedOption.val()]);
					this.resetCriterias();
					this.loadCriteriaSet(oCriteriaSet);
					this.submit();
				}
			}
		},
		
		resetHandler: function(oEvent, oParams) {
			this._super(oEvent, oParams);
			this.resetCriterias();
		},
		
		resetCriterias: function() {
			this.aCurrentCriterias = [];
			this.resetSummaryContainer();
		},
		
		resetSummaryContainer: function() {
			$(this.oSummaryContainer).html('<div class="summaryTitle">' + rk.util.i18n.get('advancedFilters.summary') + '</div>');
		},
		
		initAdvancedFilters: function() {
			var oItems = $('input[type="text"], input[type="checkbox"], input[type="radio"], select', $('.filters', this.oContainer)),
				i;
			
			for(i = 0; i < oItems.length; i++) {
				this.addAdvancedFiltersToNode(oItems[i]);
			}
			
			$('.advancedFilter .operator', this.oContainer).unbind('click.formAdvancedFilters');
			$('.advancedFilter .operator', this.oContainer).bind('click.formAdvancedFilters', $.proxy(this.advancedOperatorClickedHandler, this));
			
			$('.filter input[type="text"]', this.oContainer).unbind('keydown.formAdvancedFilters');
			$('.filter input[type="text"]', this.oContainer).bind('keydown.formAdvancedFilters', $.proxy(this.inputKeydownHandler, this));

			// initialise the DOM for summary
			this.initSummary();
			
			// reset all criterias
			this.resetCriterias();
			
			// load criterias from params
			this.loadParamsValues();
		},
		
		inputKeydownHandler: function(e, oParams) {
			var oOperator;
			
			if(e.keyCode == 13) {
				// when user presses ENTER while in an input : we click the first operator of that input to add it to the summary before the form is submitted (default ENTER behaviour)
				oOperator = $('.operator', $(e.target).parent());
				$(oOperator[0]).click();
			}
		},
		
		
		// load criteriaSet from params if any
		loadParamsValues: function() {
			var oCriteriaSet;
				
			if(!rk.util.isEmpty(this.oParams.oAdvancedCriterias)) {
				oCriteriaSet = $.parseJSON(this.oParams.oAdvancedCriterias);
				
				this.loadCriteriaSet(oCriteriaSet);
			}
		},
		
		loadCriteriaSet: function(oCriteriaSet) {
			this._loadCriteriaSet(oCriteriaSet);
		},
		
		// recursively load criterias from given criteriaSet
		_loadCriteriaSet: function(oCriteriaSet) {
			var i,
				j;
			
			if(!rk.util.isEmpty(oCriteriaSet.c)) {
				for(i = 0; i < oCriteriaSet.c.length; i++) {
					if(!rk.util.isEmpty(oCriteriaSet.c[i].c)) {
						this._loadCriteriaSet(oCriteriaSet.c[i]);
					} else {
						if(typeof oCriteriaSet.c[i].v == 'object') {
							// value is an array, we add call addCriteria for each value
							for(j = 0; j < oCriteriaSet.c[i].v.length; j++) {
								this.parseAndAddCriteria({
									f: oCriteriaSet.c[i].f,
									o: oCriteriaSet.c[i].o,
									v: oCriteriaSet.c[i].v[j]
								});
							}
						} else {
							this.parseAndAddCriteria(oCriteriaSet.c[i]);
						}
					}
				}
			}
		},
		
		parseAndAddCriteria: function(oCriteria) {
			if(oCriteria.o == 'ilike' || oCriteria.o == 'notilike') {
				// unescape % and _ that are wildcards in SQL
				oCriteria.v = oCriteria.v.substr(1, oCriteria.v.length - 2);
				oCriteria.v = oCriteria.v.replace(/\\%/g, '%');
				oCriteria.v = oCriteria.v.replace(/\\_/g, '_');
			}
			this.addCriteria(oCriteria.f, oCriteria.v, oCriteria.o);
		},
		
		// add the summary container to the DOM if it does not exist
		initSummary: function() {
			if($('.' + this.sSummaryClass, this.oContainer).length == 0) {
				$(this.oContainer).prepend('<div class="' + this.sSummaryClass + '"></div>');
			}
			
			this.oSummaryContainer = $('.' + this.sSummaryClass, this.oContainer);
		},
		
		// add advanced filter buttons to given node
		addAdvancedFiltersToNode: function(oNode) {
			var sButtons,
				sOperators = '',
				aOperators = [],
				i;
			
			if($('.advancedFilter', $(oNode).parent()).length == 0) {
				sOperators = $(oNode).parent().attr('data-operators');
				if(!rk.util.isEmpty(sOperators)) {
					aOperators = sOperators.split(',');
				}
				
				sButtons = '<span class="advancedFilter" data-inputid="' + $(oNode).attr('id') + '">';
				
				for(i = 0; i < aOperators.length; i++) {
					sButtons += '	<button class="button operator" data-operator="' + aOperators[i] + '" type="button">' + aOperators[i] + '</button>';
				}
				
				sButtons += '</span>';
				$(oNode).parent().append(sButtons);
			}
		},
		
		// handler for 'click' on a advanced filter button
		advancedOperatorClickedHandler: function(e) {
			var oTarget = $(e.target),
				sOperator = $(oTarget).attr('data-operator'),
				sId = '',
				oInput,
				sValue = '';
			
			sId = $(oTarget).parent().attr('data-inputid');
			oInput = $('#' + sId);
			sValue = $(oInput).val();
			
			if(!rk.util.isEmpty(sValue) && !rk.util.isEmpty(sOperator)) {
				this.addCriteria($(oInput).attr('name'), sValue, sOperator);
			}
		},
		
		// add a criteria
		addCriteria: function(sInputName, sInputValue, sOperator) {
			var sFieldLabel = sInputName,
				sInputDisplayValue = sInputValue,
				oFilterSummary,
				oInput,
				oLabel;
			
			// nothing to do if the exact same criteria is already applied
			if(!this.isAlreadyAppliedCriteria(sInputName, sInputValue, sOperator)) {
				// gather various values
				oInput = $('[name="' + sInputName + '"]', this.oContainer);
				if(oInput.length > 0) {
					oLabel = $('label[for="' + $(oInput).attr('id') + '"]', this.oContainer);
					if(oLabel.length > 0) {
						// use the label of the field if possible
						sFieldLabel = $(oLabel).html();
					}
					
					if(oInput[0].tagName.toLowerCase() == 'select') {
						// for select, we get a "displayValue" from the selected option
						sInputDisplayValue = $('option[value="' + sInputValue + '"]', oInput[0]).html();
					}
				}
				
				// add the criteria to the internal aCurrentCriterias array
				this.aCurrentCriterias.push({
					field: sInputName,
					value: sInputValue,
					operator: sOperator
				});
				
				// update the summary
				oFilterSummary = this.getSummaryFilterContainer(sInputName, sFieldLabel);
				this.addCriteriaToFilterSummary(oFilterSummary, sOperator, sInputDisplayValue, this.aCurrentCriterias.length - 1);						
			}
		},
		
		// returns the container for a field in the summary
		getSummaryFilterContainer: function(sInputName, sFieldLabel) {
			var oFilterSummary = $('.filter[data-filter="' + sInputName + '"]', this.oSummaryContainer),
				oAllFilters = $('.filter', this.oSummaryContainer),
				sAndOr;
			
			if(oFilterSummary.length == 0) {
				// create the container for the field
				if(oAllFilters.length > 0) {
					sAndOr = this.sBetweenFiltersOperator;
					sAndOr = rk.util.i18n.get('advancedFilters.' + sAndOr);
					// at least one other filter is active, so we add our sBetweenFiltersOperator
					$(this.oSummaryContainer).append('<div class="operator">' + sAndOr + '</div>');
				}
				
				oFilterSummary = document.createElement('div');
				$(oFilterSummary).attr('data-filter', sInputName);
				$(oFilterSummary).addClass('filter');
				$(oFilterSummary).html('<span class="name">' + sFieldLabel + '</span>');
				$(this.oSummaryContainer).append(oFilterSummary);
			}
			
			return oFilterSummary;
		},
		
		// adds a criteria to the summary DOM
		addCriteriaToFilterSummary: function(oFilterSummary, sOperator, sInputDisplayValue, iCriteriaIndex) {
			var sDisplayOperator,
				sAndOr;
			switch(sOperator) {
				case self.OPERATOR_NOTEQUAL:
					sDisplayOperator = ' != ';
				break;
				
				case self.OPERATOR_EQUAL:
					sDisplayOperator = ' = ';
				break;
				
				case self.OPERATOR_ILIKE:
					sDisplayOperator = rk.util.i18n.get('advancedFilters.contains');
				break;
				
				case self.OPERATOR_NOTILIKE:
					sDisplayOperator = rk.util.i18n.get('advancedFilters.contains_not');
				break;
					
				default:
					sDisplayOperator = sOperator;
				break;
			}
			
			sAndOr = this.sBetweenSameFilterCriteriasOperator;
			sAndOr = rk.util.i18n.get('advancedFilters.' + sAndOr);
			
			if($('.criteria', oFilterSummary).length > 0) {
				// filter already has another criteria : we add this.sBetweenSameFilterCriteriasOperator
				sDisplayOperator = ' ' + sAndOr + ' ' + sDisplayOperator;
			}
			
			// iCriteriaIndex corresponds to the index of the criteria in this.aCurrentCriterias (used to remove a filter)
			$(oFilterSummary).append('<span class="criteria" data-criteriaindex="' + iCriteriaIndex + '">' + sDisplayOperator + ' ' + sInputDisplayValue + '<span class="icon delete"></span></span><div class="clear"></div>');
			
			$('.criteria .delete', this.oSummaryContainer).unbind('click.advFilters');
			$('.criteria .delete', this.oSummaryContainer).bind('click.advFilters', $.proxy(this.deleteCriteriaClickedHandler, this));
		},
		
		// click on the remove icon of a criteria
		deleteCriteriaClickedHandler: function(e) {
			var oTarget = $(e.target),
				iIndex = $(oTarget).parent().attr('data-criteriaindex');
			
			if(!rk.util.isEmpty(iIndex)) {
				this.removeCriteria(iIndex);
			}
		},
		
		// remove a criteria from the form (both in internal array AND in DOM)
		removeCriteria: function(iCriteriaIndex) {
			var i,
				aNewCriterias;

			if(!rk.util.isEmpty(this.aCurrentCriterias[iCriteriaIndex])) {
				aNewCriterias = this.aCurrentCriterias;
				this.resetCriterias();
				for(i = 0; i < aNewCriterias.length; i++) {
					if(i != iCriteriaIndex) {
						this.addCriteria(aNewCriterias[i].field, aNewCriterias[i].value, aNewCriterias[i].operator);
					}
				}	
			}
		},
		
		// check if given criteria already exists in the internal array
		isAlreadyAppliedCriteria: function(sInputName, sInputValue, sOperator) {
			var i;
			
			for(i = 0; i < this.aCurrentCriterias.length; i++) {
				if(this.aCurrentCriterias[i].field == sInputName && this.aCurrentCriterias[i].value == sInputValue && this.aCurrentCriterias[i].operator == sOperator) {
					return true;
				}
			}
			
			return false;
		},
		
		submit: function() {
			var oCallback,
				oAjax,
				oAjaxParams = {},
				sURL;
						
			this.showLoading();
			
			sURL = $(this.oContainer).attr('action') || document.location.href;
						
			oAjaxParams = {
				_rkFormId: $(this.oContainer).attr('id')
			};
			
			// add json criterias to the params
			oAjaxParams.criterias = JSON.stringify(this.getCriteriaSet());
			
			oCallback = new rk.event.callback(this.refreshContent, this, {});
			oAjax = new rk.ajax(sURL, oCallback, {sData: oAjaxParams});
			oAjax.query();
		},
		
		getCriteriaSet: function() {
			var i,
				j,
				oCriteriasByField = {},
				oCriteriasetForFilter,
				oCriteriaSet = {
					o: this.sBetweenFiltersOperator,
					c: []
				};
			
			// first we sort criterias by name
			for(i = 0; i < this.aCurrentCriterias.length; i++) {
				if(!rk.util.isEmpty(this.aCurrentCriterias[i])) {
					if(rk.util.isEmpty(oCriteriasByField[this.aCurrentCriterias[i].field])) {
						oCriteriasByField[this.aCurrentCriterias[i].field] = [];
					}
					
					oCriteriasByField[this.aCurrentCriterias[i].field].push(this.aCurrentCriterias[i]);					
				}
			}
			
			// then we build our criteriaSets
			for(i in oCriteriasByField) {
				oCriteriasetForFilter = {
					o: this.sBetweenSameFilterCriteriasOperator, 
					c: []
				};
				for(j in oCriteriasByField[i]) {
					
					if(oCriteriasByField[i][j].operator == 'ilike' || oCriteriasByField[i][j].operator == 'notilike') {
						oCriteriasByField[i][j].value = oCriteriasByField[i][j].value.replace(/%/g, '\\%');
						oCriteriasByField[i][j].value = oCriteriasByField[i][j].value.replace(/_/g, '\\_');
						oCriteriasByField[i][j].value = '%' + oCriteriasByField[i][j].value + '%';
					}
					
					
					oCriteriasetForFilter.c.push({
						f: i,
						o: oCriteriasByField[i][j].operator,
						v: oCriteriasByField[i][j].value
					});
				}
				oCriteriaSet.c.push(oCriteriasetForFilter);
			}
			
			return oCriteriaSet;
		}
	});
	
	var self = rk.util.advancedFilters;
	
	self.oInstances = {};
	self.getInstance = function (mParams) {
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
		
				
		if (rk.util.isEmpty(self.oInstances[sId])) {
			// no instance, we create it if params were given
			if(rk.util.isEmpty(oParams)) {
				rk.util.die('unknown instance');
			}
			self.oInstances[sId] = new self(oParams);
		} else if(!rk.util.isEmpty(oParams)) {
			// instance already exists : we update its params by calling init
			$.extend(oNewParams, self.oInstances[sId].oParams, oParams);
			self.oInstances[sId].init(oNewParams);
		}
		
		return self.oInstances[sId];
	};
	
	
	self.OPERATOR_EQUAL = 'equal';
	self.OPERATOR_NOTEQUAL = 'notequal';
	self.OPERATOR_LIKE = 'like';
	self.OPERATOR_NOTLIKE = 'notlike';
	self.OPERATOR_ILIKE = 'ilike';
	self.OPERATOR_NOTILIKE = 'notilike';
	
	self.OPERATOR_GREATER = 'greater';
	self.OPERATOR_LOWER = 'lower';
	self.OPERATOR_GREATEREQUAL = 'greaterEqual';
	self.OPERATOR_LOWEREQUAL = 'lowerEqual';
	
})();

