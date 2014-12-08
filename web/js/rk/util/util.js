(function() {
	
	rk.util.checkMissingValue = function (mValue, sName) {
		if (rk.util.isEmpty(mValue)) {
			rk.util.die('missing param "' + sName + '"');
		}
	};
	
	rk.util.checkMissingParam = function (oParams, sParamName) {
		rk.util.checkMissingValue(oParams[sParamName], sParamName);
	};
	
	rk.util.checkValidContainer = function (mContainer) {
		var oContainer = rk.util.getContainer(mContainer);
		if(!oContainer) {
			rk.util.die('invalid container', mContainer);
		}
		
		return oContainer;
	};
	
	rk.util.execJavaScript = function (mNode) {
		var iScriptIndex,
			aScripts;

		aScripts = $('script[type="text/javascript"], script[type="text/rkscript"]', mNode);
		for (iScriptIndex = 0; iScriptIndex < aScripts.length; iScriptIndex++) {
			eval(aScripts[iScriptIndex].innerHTML);
		}
	};
	
	rk.util.die = function (sMessage, oParams) {
		oParams = oParams || {};
		try {
			//We send an error to get the call stack
			throw new Error();
		} catch(e) {
			console.log(e.stack);
			if (!rk.util.isEmpty(oParams)) {
				console.log(oParams);
			}
			throw new Error(sMessage);
		}
	};
		
	rk.util.htmlDecode = function(sValue){
		$return = '';
	    if (sValue) {
	    	sValue = sValue.replace(/\<script.*<\/script>/g, '');
	    	$return = $('<div />').html(sValue).text();
	    	$return = $.trim($return);
	    }
	    return $return;
	};
	
	rk.util.getDoubleDigit = function(sString) {
		if (parseInt(sString) < 10) {
			return '0' + sString;
		}
		return sString;
	};
	
	rk.util.stripTags = function(sValue){
		if (rk.util.isEmpty(sValue)) {
			return '';
		}
	    return sValue.replace('<', '&lt;', 'g').replace('>', '&gt;', 'g').replace('"', '&quot;', 'g');
	};
	
	rk.util.isInBody = function (mContainer) {
		var oContainer = rk.util.getContainer(mContainer);
		
		if(mContainer === document) {
			return true;
		}
		
		if(oContainer && $(oContainer).closest('body').length > 0) {
			return true;
		}
		
		return false;
	};
	
	rk.util.isEmpty = function(mValue) {
		if(mValue !== undefined && mValue != null && mValue.constructor && mValue.constructor == RegExp) {
			// it's a regExp
			return false;
		}
		
		if(typeof mValue === 'function') {
			// fonction => not empty
			return false;
		}
		
		if(mValue === false) {
			// boolean false ==> not empty
			return true;
		}
		if(mValue === true) {
			// boolean true ==> not empty
			return false;
		}
		if(mValue === undefined) {
			// undefined : empty
			return true;
		}
		if(typeof mValue === "string") {
			// strings
			if(mValue !== "") {
				return false;
			} else {
				return true;
			}
		}
		if(typeof mValue === "object") {
			// object
			return $.isEmptyObject(mValue);	
		}
		
		if(typeof mValue === "number") {
			if (isNaN(mValue)) {
				return true;
			}
			return false;
		}

		throw Error('type inconnue pour isEmpty');
	};
	
	rk.util.getContainer = function(mContainer) {
		var oReturn = false,
			oResults;
		
		if(typeof mContainer == 'string') {
			oResults = $(mContainer);
			if(oResults && oResults[0]) {
				oReturn = oResults[0];
			} else if(mContainer.indexOf('#') !== 0 && mContainer.indexOf('.') !== 0) {
				oResults = $('#' + mContainer);
				if(oResults && oResults[0]) {
					oReturn = oResults[0];
				}
			}
		} else {
			oResults = $(mContainer);
			if(oResults && oResults[0]) {
				oReturn = oResults[0];
			}
		}
		
		return oReturn;
	};

	rk.util.getDOMQueryString = function(mTarget) {
		if(typeof mTarget == 'string') {
			return mTarget;
		}
		
		var oTarget = $(mTarget);
		
		if($(oTarget).attr('id')) {
			return oTarget[0].tagName + '#' + $(oTarget).attr('id');
		}
		
		if($(oTarget).attr('class')) {
			return oTarget[0].tagName + '.' + $(oTarget).attr('class');
		}
		
		return oTarget[0].tagName.toLowerCase();
	};

	/**
	 * @brief Get the four margin of a node
	 * ex call : "#divId", 'margin'
	 * ex return : {
	 * 			top: 10,
	 * 			right: 10,
	 * 			bottom: 10,
	 * 			left: 10
	 *  	}
	 */
	rk.util.getCssSize = function(mNode, sType) {
		if(sType != 'margin' && sType != 'padding' && sType != 'border') {
			throw new Error('incorrect type');
		}
		
		var oReturn = {
			top: rk.util._getCssSize(mNode, sType, 'top'),
			right: rk.util._getCssSize(mNode, sType, 'right'),
			bottom: rk.util._getCssSize(mNode, sType, 'bottom'),
			left: rk.util._getCssSize(mNode, sType, 'left')
		};
		
		return oReturn;
	};
	
	rk.util._getCssSize = function(mNode, sType, sSide) {
		var sProperty = sType + '-' + sSide,
			sSize,
			iSize;
			
		if(sType == 'border') {
			// border property are : "border-top-width"
			sProperty += '-width';
		}
			
		sSize = $(mNode).css(sProperty);
		
		if(rk.util.isEmpty(sSize)) {
			iSize = 0;
		} else {
			iSize = parseInt(sSize.replace("px", ""));					
		}
		
		return iSize;
	};
	
	rk.util.getZIndex = function(mNode) {
		var oNode = rk.util.getContainer(mNode),
			iZIndex = $(oNode).css('z-index');
		if(iZIndex === 'auto') {
			iZIndex = 2;
		}
		
		return iZIndex;
	};
	
	/**
	 * set the top/left CSS attributes of mNodeToCenter so that it is displayed in the middle of mTarget.
	 * If omitted, 'body' will be used as mTarget
	 */
	rk.util.centerNode = function(mNodeToCenter, mTarget) {
		mTarget = mTarget || 'body';
		
		var oNode = rk.util.getContainer(mNodeToCenter),
			oTarget = rk.util.getContainer(mTarget),
			iHeight,
			iWidth,
			iScrollTop = $(document).scrollTop(),
			iScrollLeft = $(document).scrollLeft(),
			iTop,
			iLeft;
		
		$(oNode).css('top', '0');
		$(oNode).css('left', '0');
		
		iHeight = $(oNode).outerHeight(true);
		iWidth = $(oNode).outerWidth(true);

		
		if(mTarget == 'body') {
			// ask to center node relatively to body, so we take scrolls into account
			iTop = iScrollTop + $(window).height() / 2 - iHeight / 2;			
			iLeft = iScrollLeft + $(window).width() / 2 - iWidth / 2;
		} else {			
			iTop = $(oTarget).height() / 2 - iHeight / 2;			
			iLeft = $(oTarget).width() / 2 - iWidth / 2;
		}
		


		if(iTop < 0) {
			iTop = 0;
		}
		if(iLeft < 0) {
			iLeft = 0;
		}
		
		
		$(mNodeToCenter).css('top', iTop);
		$(mNodeToCenter).css('left', iLeft);
		
//		return {
//			iTop: Math.round(iTop),
//			iLeft: Math.round(iLeft),
//		};
	};
	
	rk.util.slugify = function(sVal) {
		
		var sRes = '',
			i,
			oMap = {
				'ã': 'a', 'à': 'a', 'á': 'a', 'ä': 'a', 'â': 'a', 
				'ẽ': 'e', 'è': 'e', 'é': 'e', 'ë': 'e', 'ê': 'e', 
				'ì': 'i', 'í': 'i', 'ï': 'i', 'î': 'i', 
				'õ': 'o', 'ò': 'o', 'ó': 'o', 'ö': 'o', 'ô': 'o', 
				'ù': 'u', 'ú': 'u', 'ü': 'u', 'û': 'u',
				'ñ': 'n',
				'ç': 'c',
				'.': '-', '/': '-', '_': '-', ',': '-', ':': '-', ';': '-'
			};
		
		if (!rk.util.isEmpty(sVal)) {
			
			sVal = $.trim(sVal);
			
			for (i=0; i<sVal.length; i++) {
				sRes += oMap[sVal[i]] || sVal[i];
			}
			
			sRes = sRes.toLowerCase();
	        sRes = sRes.replace(/[^a-z0-9-]/gi, '-');
		}
		
		return sRes;
	};

})();
