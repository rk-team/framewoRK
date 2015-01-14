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
	
	
	public static function slugify($text) { 
		
		// remove accents
	    $table = array(
	        'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
	        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
	        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
	        'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
	        'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
	        'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
	        'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
	        'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
	    );

    	$text = strtr($text, $table);
    
		// replace non letter or digits by -
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);
			
		// trim
		$text = trim($text, '-');
			
		// transliterate
		$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
			
		// lowercase
		$text = strtolower($text);
			
		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);
		
	  return $text;
	}
}