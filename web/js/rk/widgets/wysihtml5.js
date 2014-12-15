(function() {
	
	rk.widgets.wysihtml5 = rk.base.extend({

		oEditor: null,
		oContainer: null,
		
		sInputId: '',
		sToolBarId: '',
		sEditorCss: '',
		
		oToolButtons: {},
		oParams: {},
		oJSParams: {},
		
		init: function(oParams) {
			
			rk.util.checkMissingParam(oParams, 'sInputId');
			rk.util.checkMissingParam(oParams, 'sToolBarId');
			rk.util.checkMissingParam(oParams, 'sContainerId');
			
			this.sInputId = oParams.sInputId;
			this.sToolBarId = oParams.sToolBarId;
			this.sContainerId = oParams.sContainerId;
			
			if (!rk.util.isEmpty(oParams.sEditorCss)) {
				this.sEditorCss = oParams.sEditorCss;
			}
			
			if (!rk.util.isEmpty(oParams.oJSParams)) {
				this.oJSParams = oParams.oJSParams;
			}
			
			this.oContainer = rk.util.getContainer(this.sContainerId);
			
			this.oParams = oParams;
			
			this.createEditor();
			
			this.addTagWithClassFunctionnality();
			
			//Binding change for highLight
			this.oEditor.on("aftercommand:composer", $.proxy(this.changeHandler, this));
			
			//Launch highLight
			this.oEditor.on("load", $.proxy(this.highlight, this));
		},
		
		createEditor: function() {
			
			oParams = this.oJSParams;
			if (rk.util.isEmpty(oParams['parserRules'])) {
				oParams['parserRules'] = wysihtml5ParserRules;
			} else {
				oParams['parserRules'] = eval(oParams['parserRules']);
			}
			
			oParams['toolbar'] = this.sToolBarId;
			oParams['stylesheets'] = this.sEditorCss;
			
			this.oEditor = new wysihtml5.Editor(this.sInputId, oParams);
		},
		
		addTagWithClassFunctionnality: function() {
			
			//Adding wysihtml5 functionnality to add tag with class
			wysihtml5.commands.addTagWithClass = {
					
			    exec: function(oComposer, oCommand, sElementClass) {
			    	
			    	var sElement,
			    		sNewClass,
		    			oExp,
		    			oAnchor = this.state(oComposer, oCommand, sElementClass),
		    			sStrippedValue; 
			    	
			    	if (oAnchor) {
			    		$('br', oAnchor).replaceWith("\n");
			    		sStrippedValue = $(oAnchor).text().replace(/\n/g, "<br />");
			    		$(oAnchor).replaceWith(sStrippedValue);
			    	} else {
			    	
				    	sElementClass = sElementClass.split(/:/);
				    	sElement = sElementClass[0];
				    	sNewClass = sElementClass[1];
				    	oExp = new RegExp(sNewClass, 'g');
	
				    	wysihtml5ParserRules['classes'][sNewClass] = 1;
				    	if (rk.util.isEmpty(wysihtml5ParserRules['tags'][sElement])) {			    		
				    		wysihtml5ParserRules['tags'][sElement] = 1;
				    	}
				    	
				    	return wysihtml5.commands.formatBlock.exec(oComposer, oCommand, sElement, sNewClass, oExp);
			    	}
			    },
			    
			    state: function(oComposer, oCommand, sElementClass) {
			    	
			    	var sElement,
		    			sNewClass,
		    			oExp;
			    	
			    	sElementClass = sElementClass.split(/:/);
			    	sElement = sElementClass[0];
			    	sNewClass = sElementClass[1];
			    	oExp = new RegExp(sNewClass, 'g');
			    	
			    	return wysihtml5.commands.formatBlock.state(oComposer, oCommand, sElement, sNewClass, oExp);
			    }
			};
		},
		
		highlight: function() {
			var oBlock,
				oCode,
				i;
			
			oCode = $('.wysihtml5-sandbox').contents().find('code');
			
			for (i=0; i<oCode.length; i++) {
				if (!$(oCode[i]).hasClass('hljs')) {
					hljs.highlightBlock(oCode[i]);
				}
			}
		},
		
		changeHandler: function(e) {
			this.highlight();
		}
	});
		
	var self = rk.widgets.wysihtml5;	
})();