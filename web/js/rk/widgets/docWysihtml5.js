(function() {
	
	rk.widgets.docWysihtml5 = rk.widgets.wysihtml5.extend({

		sGetArticleUrl: '',
		sCategoryUrl: '',
		sIndexUrl: '',
		
		init: function(oParams) {
			
			//Call parent to create editor
			this._super(oParams);
			
			//Looking for required URL to create article links
			rk.util.checkMissingParam(oParams, 'sGetArticleUrl');
			rk.util.checkMissingParam(oParams, 'sCategoryUrl');
			rk.util.checkMissingParam(oParams, 'sIndexUrl');
			
			this.sGetArticleUrl = oParams.sGetArticleUrl;
			this.sCategoryUrl = oParams.sCategoryUrl;
			this.sIndexUrl = oParams.sIndexUrl;
			
			//Binding category selector in article creation box, to change article list
			$('#categorySelector').bind('change', $.proxy(this.updateArticle, this));

			//Launching first article list update
			this.updateArticle();
			
			//Adding wysihtml5 functionnality to add internal link
			wysihtml5.commands.createArticleLink = {
					
			    exec: function(oComposer, oCommand, sElementValue) {
			    	
			        var oAnchors = wysihtml5.commands.formatInline.state(oComposer, oCommand, "span", "menuLink", /menuLink/),
				        sElement,
			    		sNewClass,
			    		oELem;
			        
			        if (oAnchors) {
			        	// Selection contains links, removing links and continue
			        	oComposer.selection.executeAndRestore(function() {
			        		$(oAnchors).remove();
			          	});
			        } else {
			        	// Create links value format is : target:class:url:label
				    	sElementValue = sElementValue.href.split(/:/);
				    	
				    	sTarget = sElementValue[0];
				    	sClass = sElementValue[1];
				    	sHref = sElementValue[2];
				    	sLabel = sElementValue[3];
				    	
				    	oElem = document.createElement('span');
				    	$(oElem).attr('data-href', sHref);
				    	$(oElem).attr('data-target', sTarget);
				    	$(oElem).addClass(sClass);
				    	$(oElem).html(sLabel);
				    					    	
				    	oComposer.selection.insertNode(oElem);
			        }
			    },
			    
			    //Used to know if current selection contains a link
			    state: function(oComposer, oCommand, sElementClass) {
			    	return wysihtml5.commands.formatInline.state(oComposer, oCommand, "span", "menuLink", /menuLink/);
			    }
			};	
		},
		
		//handler used to setup article select options, when changing category selector
		updateArticleHandler: function (oData) {
			var oJSON = JSON.parse(oData),
				aArticles,
				i,
				sUrl,
				sClass,
				sTarget;
			
			aArticles = oJSON.articles;

			//Clears article choices
			$('#articleSelector', this.oContainer).html('');
			sDisplayedCategory = $('#category').attr('data-idx');
						
			//If category selected is the same as the displayed category, then we links inner article
			if ($('#categorySelector', this.oContainer).val() == sDisplayedCategory) {
				
				sUrl = '';
				sClass = 'menuLink article';
				sTarget = '_self';
			}
			//Else it can links other category or articles in other category
			else {
			
				sUrl = rk.util.url.addParams(this.sIndexUrl, {categoryIdx: $('#categorySelector', this.oContainer).val() });
				sClass = 'menuLink';
				sTarget = '_blank';
				
				//Linking category with the empty choice
				$('#articleSelector', this.oContainer).append('<option value="' + sTarget + ':menuLink:' + sUrl + ':' + $.trim($('#categorySelector option:selected', this.oContainer).text()) + '"></option>');
			}
			
			//Setting option with value for createArticleLink action
			//target:class:url:label
			for (i=0; i<aArticles.length; i++) {
				$('#articleSelector', this.oContainer).append('<option value="' + sTarget + ':' + sClass + ':' + sUrl + '#cat-' + sDisplayedCategory + '-art-' + aArticles[i].idx + ':' + aArticles[i].title + '">' + aArticles[i].title + '</option>');
			} 		
		},

		//get article list for the category selected
		updateArticle: function() {
			var oCallback = new rk.event.callback(this.updateArticleHandler, this, {}),
			oAjax;

			oAjax = new rk.ajax(
				rk.util.url.addParams(this.sGetArticleUrl, {idx: $('#categorySelector', this.oContainer).val() }), 
				oCallback, 
				{sData: {category_idx: $('#categorySelector', this.oContainer).val()}}
			);
			oAjax.query();
		}
	});
	
	var self = rk.widgets.docWysihtml5;	
})();
