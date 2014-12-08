<?php

namespace rk\helper;

class url {
	
	public static function urlFor($url, array $getParams = array()) {
		$return = '';
		
		$app = \rk\manager::getInstance()->getRequestHandler()->getApplicationName();
		
		//removing /dev from url if present to simplify analysing
		if (strpos($url, '/dev') === 0) {
			$url = substr($url, 4);
		}
		
		if(\rk\manager::isDevMode()) {
			$return .= '/dev';
		}

		// adds _<application>
		if ((strpos($url, '_') !== 0) && (strpos($url, '/_') !== 0)) {
			$return .= '/_' . $app;
		}
		
		// adds a "/" at begining if not given 
		if(strpos($url, '/') !== 0) {
			$return .= '/' . $url;
		} else {
			$return .= $url;
		}
		
		$return = self::addParamsToURL($return, $getParams);
		
		return $return;
	}
	
	public static function addParamsToURL ($url, array $params = array()) {
	
		$paramsFromURL = self::getParamsFromURL($url);
		$params = array_merge($paramsFromURL, $params);
		
		$urlStrParams = self::getStrParams($params);
		
		$pos = strpos($url, '?');
		if ($pos !== false) {
			$url = substr($url, 0, $pos);
		}
	
		if (!empty($urlStrParams)) {
			return $url . '?' . substr($urlStrParams, 1);
		} else {
			return $url;
		}
	}
	
	public static function getParamsFromURL($url) {
		$urlParams = array();
		
		$pos = strpos($url, '?');
		if ($pos !== false) {
			$urlParamsString = substr($url, $pos + 1);
			$urlParamsString = '&' . $urlParamsString;
				
			$url = substr($url, 0, $pos);
			
			$chars = preg_split('/&([\[\]a-zA-Z0-9_\-%]+)=/', $urlParamsString, -1, PREG_SPLIT_DELIM_CAPTURE);
			
			/**
			 * $chars will look like 
			 *	array (size=7)
			 *		0 => string '' (length=0)
			 *		1 => string 'nbItemsPerPage' (length=14)
			 *		2 => string '10' (length=2)
			 *		3 => string '_rkFormId' (length=9)
			 *		4 => string '474624-528689' (length=13)
			 * 		5 => string 'criterias' (length=9)
			 *		6 => string '{"o":"and","c":[{"o":"or","c":[{"f":"status","o":"equal","v":"NEW"},{"f":"status","o":"equal","v":"IN PROGRESS"},{"f":"status","o":"equal","v":"ACCEPTED"}]},{"o":"or","c":[{"f":"text","o":"notequal","v":"bite\u00e9\u00e9\u00e9\u00e9\u00e9\u00e9\"ooo\" \u00e9\"\u00e9\"'' \u00e9\"'\u00e9\"'\u00e9\"'"}]}]}' (length=304)
			 *
			 */
			
			for($i = 1; $i < count($chars); $i += 2) {
				$urlParams[$chars[$i]] = $chars[$i+1];
			}
		}
		return $urlParams;
	}
	
	public static function getStrParams($params) {
		$urlParams = '';
		foreach ($params as $key => $value) {
	
			if (is_array($value)) {
				foreach ($value as $subKey => $subValue) {
					$urlParams .= self::getStrParams(array($key . '[' . $subKey . ']' => $subValue));
				}
				continue;
			}
			$urlParams .= '&' . $key . '=' . $value;
		}
		return $urlParams;
	}
}