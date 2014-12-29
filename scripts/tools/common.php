<?php

namespace rk\scripts\tools;

class common {
	
	// shell color variables
	public static $PRINT_YELLOW = "\033[01;33m";
	public static $PRINT_CYAN = "\033[01;36m";
	public static $PRINT_GREEN = "\033[01;32m";
	public static $PRINT_BOLD = "\033[1;37m";
	public static $PRINT_STD = "\033[00m";
	public static $PRINT_RED = "\033[01;31m";

	
	public static function checkLauncher () {
		
		if (php_sapi_name() != "cli") {
			echo 'This script must be launched in console mode !' . "\n";
			exit(1);
		}
	}
	
	
	
	public static function useTpl($tplPath, $vars) {
		$tpl = file_get_contents($tplPath);
		foreach($vars as $key => $value) {
			
			$tpl = str_replace('@' . $key . '@',  $value, $tpl);
		}
		
		return $tpl;
	}
	
	public static function createFileFromTpl($tplPath, $tplVars, $destination) {
		$data = self::useTpl($tplPath, $tplVars);
		\rk\helper\fileSystem::file_put_contents($destination, $data);
	}
}