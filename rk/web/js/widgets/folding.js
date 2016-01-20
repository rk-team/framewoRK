(function(){

	rk.widgets.folding = rk.base.extend({

		oParams: {},		
		oLabel: null,
		oContent: null,
		sHeaderClass: '',
		sContentClass: '',
		
		oBeforeOpenCallback: null,
		oOpenCallback: null,
		oBeforeCloseCallback: null,
		oCloseCallback: null,
		
		bLeftButton: false,
		bRightButton: false,
		bEffects: true,	
		
		init: function(oParams) {
			oParams = oParams || {};
			if (rk.util.isEmpty(oParams.mLabel)
				|| rk.util.isEmpty(oParams.mContent)) {
				rk.util.die('missing param for folding');
			}

			//Param obligatoire
			this.oLabel = $(rk.util.getContainer(oParams.mLabel));
			this.oContent = $(rk.util.getContainer(oParams.mContent));

			if(this.oLabel.length === 0 || this.oContent.length === 0) {
				rk.util.die('invalid params for folding');
			}
			
			//Param optionnel
			this.oBeforeOpenCallback = oParams.oBeforeOpenCallback || null;
			this.oOpenCallback = oParams.oOpenCallback || null;
			this.oBeforeCloseCallback = oParams.oBeforeCloseCallback || null;		
			this.oCloseCallback = oParams.oCloseCallback || null;		
			this.sHeaderClass = oParams.sHeaderClass || rk.widgets.folding.sHeaderClass;
			
			this.bLeftButton = oParams.bLeftButton || false;
			this.bRightButton = oParams.bRightButton || false;
			this.sContentClass = oParams.sContentClass || rk.widgets.folding.sContentClass;

			if(oParams.bEffects !== undefined && oParams.bEffects === false) {
				this.bEffects = oParams.bEffects;
			}
			
			$(this.oLabel).addClass(this.sHeaderClass);
			$(this.oContent).addClass(this.sContentClass);
			
			this.oParams = oParams;

			this.initFolding();	
		},
		
		initFolding: function() {
			var oDivLeft,
				oDivRight;
			
			//On bind le click pour le toogle
			$(this.oLabel).unbind('click', $.proxy(this.labelClickedHandler, this));
			$(this.oLabel).bind('click', $.proxy(this.labelClickedHandler, this));
			
			this.oLabel.disableSelection();
			
			if(this.bLeftButton) {
				if(this.oLabel.children('div.left').length === 0) {
					//On ajoute la div pour l'icone de gauche
					oDivLeft = document.createElement('div');
					$(oDivLeft).addClass('left');
					$(this.oLabel).prepend(oDivLeft);				
				}
			}
			
			if (this.bRightButton) {
				if(this.oLabel.children('div.right').length === 0) {
					//Si l'icone de droite doit être placée
					//on prepare le div
					oDivRight = document.createElement('div');
					$(oDivRight).addClass('right');
					//on le positionne pour connaitre sa largeur
					$(this.oLabel).prepend(oDivRight);
				}
			}
			
			this.changeSkin();
		},
		
		changeSkin: function() {			
			if(this.oContent.css('display') == 'none') {
				$(this.oLabel)
					.addClass('closed')
					.removeClass('opened');
			} else {
				$(this.oLabel)
					.addClass('opened')
					.removeClass('closed');
			}
		},
		
		labelClickedHandler: function(e) {

			if ((e.target.tagName.toLowerCase() !== 'a')) {
				
				if(this.oContent.css('display') == 'none') {
					this.open();
				} else {
					this.close();
				}
			}
		},
		
		open: function() {
			var oScope = this,
				fHandler = function() {
					if(!rk.util.isEmpty(oScope.oOpenCallback)) {
						oScope.oOpenCallback.launch();
					}
					oScope.changeSkin();
				};
			if(!rk.util.isEmpty(this.oBeforeOpenCallback)) {
				this.oBeforeOpenCallback.launch();
			}
			if(this.bEffects) {
				this.oContent.clearQueue();
				this.oContent.show('blind', fHandler);
			} else {
				this.oContent.css('display', '');
				fHandler();
			}
		},
		
		close: function() {
			var oScope = this,
				fHandler = function() {
					if(!rk.util.isEmpty(oScope.oCloseCallback)) {
						oScope.oCloseCallback.launch();
					}
					oScope.changeSkin();
				};
			if(!rk.util.isEmpty(this.oBeforeCloseCallback)) {
				this.oBeforeCloseCallback.launch();
			}
			if(this.bEffects) {
				this.oContent.clearQueue();
				this.oContent.effect('blind', fHandler);
			} else {
				this.oContent.css('display', 'none');
				fHandler();
			}
		},
		
		/** détruit les éléments du folding du DOM **/
		remove: function() {
			this.oLabel.remove();
			this.oContent.remove();
		}
	});
	
	rk.widgets.folding.sHeaderClass = 'rkFoldingHeader';
	rk.widgets.folding.sContentClass = 'rkFoldingContent';
	
})();
