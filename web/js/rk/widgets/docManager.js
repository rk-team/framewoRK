(function() {
	
	rk.widgets.docManager = rk.base.extend({

		iCategoryIndex: 0,
		iCurrentCategory: 0,
		
		sMenuContainer: '',
		sContentContainer: '',
		
		oMenuContainer: {},
		oContentContainer: {},
		
		sRefreshContentURL: '',
		sRefreshMenuURL: '',		
		
		oMenuFolds: {},
		
		sCurrentHash: '',
		
		init: function(oParams) {
			
			//check params
			this.checkMissingParam(oParams, 'iCategoryIndex');
			this.checkMissingParam(oParams, 'iCurrentCategory');
			
			this.checkMissingParam(oParams, 'sMenuContainer');
			this.checkMissingParam(oParams, 'sContentContainer');
			
			this.checkMissingParam(oParams, 'sRefreshContentURL');
			this.checkMissingParam(oParams, 'sRefreshMenuURL');
			
			//save params
			this.iCategoryIndex = oParams.iCategoryIndex;
			this.iCurrentCategory = oParams.iCurrentCategory;
			
			this.sMenuContainer = oParams.sMenuContainer;
			this.sContentContainer = oParams.sContentContainer;
			
			this.sRefreshContentURL = oParams.sRefreshContentURL;
			this.sRefreshMenuURL = oParams.sRefreshMenuURL;
			
			this.oMenuContainer = $(this.sMenuContainer);
			this.oContentContainer = $(this.sContentContainer);
						
			this.initMenu();
			this.initContent();
			
			//change category content if needed
			if (!rk.util.isEmpty(document.location.hash)) {
				this.hashChange();
			}
			
			this.setupCurrentCategory();
			
			//bind hash change for history
			$(window).unbind('hashchange');
			$(window).bind('hashchange', $.proxy(this.hashChangeHandler, this));
		},
		
		initContent: function() {
			this.ajaxifyMenuLink(this.oContentContainer);
			this.initLinkedClassesFolding();
			this.highlightCode();
			this.transformSpanMenuLink();
		},
		
		initMenu: function() {
			this.initMenuFolding();
			this.ajaxifyMenuLink(this.oMenuContainer);
			this.initSearchTool();
		},
		
		//called when hash changed to get the category content
		hashChange: function() {
			var aSplits,
				iNextCategory;
		
			if (document.location.hash !== this.sCurrentHash) {
				
				this.sCurrentHash = document.location.hash;
				aSplits = this.sCurrentHash.split('-');
				
				if (this.iCurrentCategory != aSplits[1]) {
					iNextCategory = parseInt(aSplits[1]);
					if (iNextCategory === undefined) {
						iNextCategory = this.iCategoryIndex;
					}
					this.changeContent(rk.util.url.addParams(this.sRefreshContentURL, {idx: iNextCategory}));
				}
			}
		},
		
		//location.hash change handler
		hashChangeHandler: function() {
			this.hashChange();
		},
		
		//get category hash
		setCurrentCategoryHash: function() {
			this.sCurrentHash = 'cat-' + this.iCurrentCategory + '-' + rk.util.slugify($('#categoryTitle', this.oContentContainer).text());
			document.location.hash = this.sCurrentHash;
		},
		
		//call to change location.hash
		setUpCurrentHash: function() {
			var sName,
				oElem;
			
			if (this.sCurrentHash.indexOf('cat-' + this.iCurrentCategory + '-') !== 1) {
				this.setCurrentCategoryHash();
			}
		},
		
		//called to refresh menu after some edition
		refreshMenu: function() {		
			var oCallBack = new rk.event.callback(this.postMenuUpdateHandler, this, {});			
			rk.ajax.updater(this.sRefreshMenuURL, this.oMenuContainer, {oAfterUpdateCallback: oCallBack});
		},
		
		//called to refresh content after some edition
		refreshContent: function() {		
			this.changeContent(rk.util.url.addParams(this.sRefreshContentURL, {idx: this.iCurrentCategory}));
		},
		
		//called to refresh menu and content after some edition
		refreshAll: function() {
			this.refreshMenu();
			this.refreshContent();
		},		
		
		//called to change the skin of the current category in menu
		//has to be called after content change
		setupCurrentCategory: function() {
			var oMenuItem,
				oParentMenuItem,
				aStayOpenned = [],
				i = 0;
			
			//research is on
			if (($('#searchResults', this.oContentContainer).length == 1)) {
				
				//no bold
				$('.category .menuLink', this.oMenuContainer).css('font-weight', '');
				
				//no fold
				for (i in this.oMenuFolds) {
					if ($(this.oMenuFolds[i].oLabel).closest('.category').attr('data-lvl') >= 1) {
						this.oMenuFolds[i].close();
					}
				}
			} else {
			
				this.iCurrentCategory = $('#category', this.oContentContainer).attr('data-idx');
				oMenuItem = $('.category[data-idx=' + this.iCurrentCategory  + ']', this.oMenuContainer);
				
				//bold current menu
				$('.category .menuLink', this.oMenuContainer).css('font-weight', '');
				$('> .foldingHeader .menuLink', oMenuItem).css('font-weight', 'bold');
				
				//set up fold
				$('> .foldingContent', oMenuItem).show('blind');
				
				//fold parents
				oParentMenuItem = oMenuItem;
				while (oParentMenuItem.length > 0) {
					aStayOpenned.push($(oParentMenuItem).attr('data-idx'));
					$('> .foldingContent', oParentMenuItem).show('blind');
					oParentMenuItem = $(oParentMenuItem).parent().closest('.fold');
				}
				
				//close others
				for (i in this.oMenuFolds) {
					if (($.inArray(i, aStayOpenned) === -1) 
						&& ($(this.oMenuFolds[i].oLabel).closest('.category').attr('data-lvl') >= 1)
						&& ($(this.oMenuFolds[i].oContent).css('display') == 'block')) {
						
						this.oMenuFolds[i].close();
					}
				}
				
				//change browser url
				this.setUpCurrentHash();
			}			
		},
				
		//called when content has be retrieved from server
		postContentUpdateHandler: function(sData, oParams) {
			this.oContentContainer = $(this.sContentContainer);
			this.setupCurrentCategory();
			this.initContent();
		},
		
		//called when menu has be retrieved from server
		postMenuUpdateHandler: function(sData) {
			this.oMenuContainer = $(this.sMenuContainer);
			this.initMenu();
		},
		
		//used to change main content
		changeContent: function(sHref, oParams) {
			var oCallBack = new rk.event.callback(this.postContentUpdateHandler, this, {});			
			rk.ajax.updater(sHref, this.oContentContainer, {oAfterUpdateCallback: oCallBack});
		},
		
		//called whe a menuLink is clicked to change main content
		menuLinktHandler: function(e) {
			e.preventDefault();
			this.changeContent(this.sRefreshContentURL + '?idx=' + $(e.currentTarget).attr('data-category-idx'));
		},
		
		//launch search
		searchHandler: function(e) {
			var sValue = $('#search input[type="text"]', this.oMenuContainer).val(),
				sURL = $('#search .icon', this.oMenuContainer).attr('href');
			
			e.preventDefault();
			
			if (!rk.util.isEmpty(sValue)) {
				this.changeContent(rk.util.url.addParams(sURL, {search: sValue}), {bReplace: false});
			}
		},
		
		//set up folding for menu
		initMenuFolding: function() {
			
			var oFolds = $('.fold', this.oMenuContainer),
				i;
			
			for (i=0; i < oFolds.length; i++) {
				this.oMenuFolds[$(oFolds[i]).attr('data-idx')] = new rk.widgets.folding({
					mContent: $('.foldingContent', oFolds[i]),
					mLabel: $('.foldingHeader', oFolds[i])
				});
			}
		},
		
		//init linkedClass folding
		initLinkedClassesFolding: function() {
			
			var oLinkedClasses = $('.linkedClass', this.oContentContainer),
				i;
			
			for (i=0; i < oLinkedClasses.length; i++) {
				new rk.widgets.folding({
					mContent: $('.foldingContent', oLinkedClasses[i]),
					mLabel: $('.foldingHeader', oLinkedClasses[i])
				});
			}
		},
		
		//set up bind for search tool
		initSearchTool: function() {
			$('.search', this.oMenuContainer).unbind('click.search');
			$('.search', this.oMenuContainer).bind('click.search', $.proxy(this.searchHandler, this));
		},
			
		//set up ajax for links
		ajaxifyMenuLink: function(oContainer) {
			$('.menuLink:not(.article)', oContainer).unbind('click.menu');
			$('.menuLink:not(.article)', oContainer).bind('click.menu', $.proxy(this.menuLinktHandler, this));
		},
		
		//set up highlight code
		highlightCode: function() {
			var oBlock;
			
			$('code', this.oContentContainer).each(function(i, oBlock) {
				if (!$(oBlock).hasClass('hljs')) {
					hljs.highlightBlock(oBlock);
				}
			});
		},
		
		//modify all span.menuLink by a representing <a>
		transformSpanMenuLink: function() {
			var oLinks = $('span.menuLink', this.oContentContainer),
				i;
			for (i=0; i < oLinks.length; i++) {
				$(oLinks[i]).replaceWith('<a target="' + $(oLinks[i]).attr('data-target') + '" href="' + $(oLinks[i]).attr('data-href') + '" class="' + $(oLinks[i]).attr('class') + '">' + $(oLinks[i]).text() + '</a>');
			}
		}
	});
	
	rk.widgets.docManager.oInstance = {};
	rk.widgets.docManager.getInstance = function (oParams) {
		oParams = oParams || {};
		
		if (rk.util.isEmpty(self.oInstance) || !rk.util.isInBody(self.oInstance.oContentContainer)) {
			self.oInstance = new rk.widgets.docManager(oParams);
		}
		return self.oInstance;
	};
	
	var self = rk.widgets.docManager;	
})();
