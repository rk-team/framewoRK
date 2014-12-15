<?php

namespace rk\model;

class manager {

	protected static $instances = array();
	
	public static function get($modelName) {
		
		if(strpos($modelName, '\\') === 0) {
			// remove first \ if any
			$modelName = substr($modelName, 1);
		}

		// get the "base" model name (without \user\model)
		$modelName = str_replace('user\model\\', '', $modelName);
		
		if(empty(self::$instances[$modelName])) {
			// create instance if it does not exist
			$class = '\user\model\\' . $modelName;
			if(!class_exists($class)) {
				throw new \rk\exception('invalid model name', $modelName);
			}
			self::$instances[$modelName] = new $class();
		}
		return self::$instances[$modelName];
	}
}