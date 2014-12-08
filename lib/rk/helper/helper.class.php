<?php

namespace rk;

class helper {
	
	/**
	 * returns a namespace part
	 * @param object|string $mixed
	 * @param integer $fromRight
	 * @throws \rk\Exception
	 * @return string
	 * 
	 * 	ex:
	 * 			with : '\user\myApp\modules\home' and 1
	 * 				=> returns 'home' (1st part from right)
 	 * 			with : '\user\myApp\modules\home' and 3
	 * 				=> returns 'myApp' (3rd part from right)
	 */
	public static function getClassFromNamespace($mixed, $fromRight = 1) {
		if(is_object($mixed)) {
			$namespace = get_class($mixed);
		} elseif(is_string($mixed)) {
			$namespace = $mixed;
		} else {
			throw new \rk\Exception('unknown type for $mixed');
		}
		
		
		$pos = 0;
		while($fromRight > 1) {	// cut last part of namespace foreach given $fromRight
			$pos = strrpos($namespace, '\\');
			$namespace = substr($namespace, 0, $pos);
			$fromRight--;
		}
		
		$pos = strrpos($namespace, '\\');
		$className = substr($namespace, $pos + 1);
		
		return $className;
	}
	
}