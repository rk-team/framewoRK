<?php

namespace rk\helper;

class cli {
	
	public static function parseArgs($argc, $argv) {
		$params = array();
		for($i = 1; $i < $argc; $i++) {
			list($name, $value) = self::parseOneArg($argv[$i]);
			if(!empty($params[$name])) {
				throw new \rk\exception('param ' . $name . ' already given');
			}
			$params[$name] = $value;
		}
		return $params;
	}
	
	
	public static function parseOneArg($arg) {
		$equalPos = strpos($arg, '=');
		$hyphenPos = strpos($arg, '--');
		if($hyphenPos === false) {
			throw new \rk\exception('cant parse CLI param', array('arg' => $arg));
		}
	
		if (!empty($equalPos)) {
			$name = substr($arg, ($hyphenPos + 2), ($equalPos - 2));
			$value = substr($arg, ($equalPos + 1));
		} else {
			$name = substr($arg, ($hyphenPos + 2));
			$value = '';
		}
			
		$commaPos = strpos($value, ',');
		if($commaPos !== false) {
			$value = explode(',', $value);
		}
	
		return array($name, $value);
	}
	
}