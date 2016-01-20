(function() {

	
	rk.util.url._getParams = function (oUrlParams, sParamName, sParamValue) {
		
		var sNextStepName = '';
				
		if (sParamName.indexOf('[') == 0) {
			sIndex = sParamName.substr(1);
			sIndex = sIndex.substr(0, sIndex.indexOf(']'));
			
			sNextStepName = sParamName.substr(1);
			if (sNextStepName.indexOf('[') == -1) {
				sNextStepName = '';
			} else {
				sNextStepName = sNextStepName.substr(sNextStepName.indexOf('['));
			}
		} else if (sParamName.indexOf(']') != -1) {
			sIndex = sParamName.substr(0, sParamName.indexOf(']') - 2);
			sNextStepName = sParamName.substr(sParamName.indexOf('['));
		} else {
			sIndex = sParamName;
		}
		
		if (rk.util.isEmpty(oUrlParams[sIndex])) {
			oUrlParams[sIndex] = {};
		}
		
		if (rk.util.isEmpty(sNextStepName)) {
			oUrlParams[sIndex] = sParamValue;
		} else {
			self._getParams(oUrlParams[sIndex], sNextStepName, sParamValue);
		}
	}
	
	rk.util.url.getParams = function (sURL) {
		
		var i,
			sParams,
			sParamName,
			sParamValue,
			iPos,
			oUrlParams = {},
			aMatches;
		
		sURL = decodeURIComponent(sURL);
		sURL = rk.util.htmlDecode(sURL);
		
		iPos = sURL.indexOf('?');
		if (iPos !== -1) {
			// There are params in this URL
			sParams = sURL.substr(iPos + 1);
			sParams = '&' + sParams;
			
			sURL = sURL.substr(0, iPos);

			aMatches = sParams.match(/&([\!<>\[\]a-zA-Z0-9_-]+)=([^&]+)/g);
			if(!rk.util.isEmpty(aMatches)) {
				for(i = 0; i < aMatches.length; i++) {
					iPos = aMatches[i].indexOf('=');
					sParamName = aMatches[i].substr(1, iPos - 1);
					sParamValue = aMatches[i].substr(iPos + 1);
					
					self._getParams(oUrlParams, sParamName, sParamValue);
				}						
			}
		}
		
		return oUrlParams;
	};
	
	rk.util.url._addParams = function (oParams, sPrefix) {
		var sURLParams = '',
			sAddedParams = '',
			sTmpPrefix,
			i,
			j;
		
		for (i in oParams) {
			if (typeof oParams[i] == 'object') {
				for(j in oParams[i]) {
					sAddedParams = '';
					
					if (typeof oParams[i][j] == 'object') {						
						
						if (rk.util.isEmpty(sPrefix)) {
							sTmpPrefix = i + '[' + j + ']';
						} else {
							sTmpPrefix = sPrefix + '[' + i + ']' + '[' + j + ']';
						}
						
						sAddedParams += self._addParams(oParams[i][j], sTmpPrefix);
					} else {
						
						if (rk.util.isEmpty(sPrefix)) {
							sTmpPrefix = i + '[' + j + ']';
						} else {
							sTmpPrefix = sPrefix + '[' + i + ']' + '[' + j + ']';
						}
						
						sAddedParams += sTmpPrefix + '=' + oParams[i][j];
					}
					
					if (rk.util.isEmpty(sURLParams)) {				
						sURLParams += sAddedParams;
					} else {
						sURLParams += '&' + sAddedParams;
					}
				}
			} else {
				if(sPrefix !== '') {
					sAddedParams = sPrefix + '[' + i + ']' + '=' + oParams[i];
				} else {
					sAddedParams = i + '=' + oParams[i];
				}
				

				if (rk.util.isEmpty(sURLParams)) {				
					sURLParams += sAddedParams;
				} else {
					sURLParams += '&' + sAddedParams;
				}
			}
			
		}
		
		return sURLParams;
	};
	
	rk.util.url.addParams = function (sURL, oParams) {
		var sURLParams = '';
		
		sURL = decodeURIComponent(sURL);
		sURL = rk.util.htmlDecode(sURL);
		
		oParams = oParams || {};
		oParams = $.extend(rk.util.url.getParams(sURL), oParams);
		
		iPos = sURL.indexOf('?');
		if (iPos !== -1) {	
			sURL = sURL.substr(0, iPos);
		}
				
		sURLParams = '?' + self._addParams(oParams, '');
		
		return sURL + sURLParams;
	};
	
	var self = rk.util.url;
})();
