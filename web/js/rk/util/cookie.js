(function() {
	
	rk.util.cookie.set = function(sKey, sValue, oParams) {
		oParams = oParams || {};
		rk.util.checkMissingValue(sKey, 'sKey');
		
		var oExpirationDate,
			sCookieValue,
			iNbExpirationDays = oParams.iNbExpirationDays || 1000;
		
		oExpirationDate = new Date();
		oExpirationDate.setDate(oExpirationDate.getDate() + iNbExpirationDays);
		
		sCookieValue = escape(sValue) + ((oExpirationDate==null) ? "" : "; expires="+oExpirationDate.toUTCString()) + "; path=/";
	
		document.cookie=sKey + "=" + sCookieValue;
	};

	rk.util.cookie.get = function(sKey) {
		var i,
			x,
			y,
			aRRcookies=document.cookie.split(";");
		
		for (i=0;i<aRRcookies.length;i++) {
			
			x=aRRcookies[i].substr(0,aRRcookies[i].indexOf("="));
			y=aRRcookies[i].substr(aRRcookies[i].indexOf("=")+1);
			x=x.replace(/^\s+|\s+$/g,"");
			
			if (x==sKey) {
				return unescape(y);
			}
		}
		return false;
	};
})();
