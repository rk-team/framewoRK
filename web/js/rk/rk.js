rk = {		
	ajax: {},
	box: {},
	event: {},
	i18n: {},
	util: {
		i18n: {
			translations: {}
		},
		cookie: {},
		url: {},
		util: {}
	},
	widgets: {
		loading: {}
	}
};

(function(){
	var initializing = false, fnTest = /xyz/.test(function(){xyz;}) ? /\b_super\b/ : /.*/;
 
	// The base Class implementation (does nothing)
	this.rk.base = function(){};
	 
	// Create a new Class that inherits from this class
	rk.base.extend = function(prop) {
		var _super = this.prototype;
		 
		// Instantiate a base class (but only create the instance,
		// don't run the init constructor)
		initializing = true;
		var prototype = new this();
		initializing = false;

		// Copy the properties over onto the new prototype
		for (var name in prop) {
			if(typeof prop[name] == "function" &&
			typeof _super[name] == "function" && fnTest.test(prop[name])) {
				prototype[name] = (function(name, fn){
					return function() {
						var tmp = this._super;
						 
						// Add a new ._super() method that is the same method
						// but on the super-class
						this._super = _super[name];
						
						// The method only need to be bound temporarily, so we
						// remove it when we're done executing
						var ret = fn.apply(this, arguments);
						this._super = tmp;
						return ret;
					};
				})(name, prop[name]);
			} else {
				prototype[name] = prop[name];
			}	
		}
		
		// add an "overload" method to all functions to allow easy customisation
		for(var name in prototype) {
			if(typeof prototype[name] == "function") {
				var fOriginal = prototype[name];
				prototype[name].overload = (function(name, fOriginal) {
					return function() {
						prototype[name] = (function(fNew, fOriginal) {
							return function() {
								this._super = fOriginal;
								var ret = fNew.apply(this, arguments);
								return ret;
							};
						})(arguments[0], fOriginal);
					};
				})(name, fOriginal);
			}
		}

		// The dummy class constructor
		rk.base = function() {
			// All construction is actually done in the init method
			if ( !initializing && this.init )
				this.init.apply(this, arguments);
		};
		 
		// Populate our constructed prototype object
		rk.base.prototype = prototype;
		 
		// Enforce the constructor to be what we expect
		rk.base.prototype.constructor = rk.base;
		 
		// And make this class extendable
		rk.base.extend = arguments.callee;
		
		// Fonction de check de params
		rk.base.prototype.checkMissingValue = function(mValue, sName) {
			rk.util.checkMissingValue(mValue, sName);
		};
		rk.base.prototype.checkMissingParam = function(oParams, sParamName) {
			rk.util.checkMissingParam(oParams, sParamName);
		};
		rk.base.prototype.checkValidContainer = function(mContainer) {
			return rk.util.checkValidContainer(mContainer);
		};
		 
		return rk.base;
	};
})();
