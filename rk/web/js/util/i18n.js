(function() {
	
	rk.util.i18n.get = function(sKey, oParams) {
		oParams = oParams || {};
		
		var sLanguage = oParams.sLanguage || self.language;
		
		if(!rk.util.isEmpty(self.translations[sLanguage][sKey])) {
			return self.translations[sLanguage][sKey];
		}
		
		return sKey;
	};
	
	var self = rk.util.i18n;
})();
