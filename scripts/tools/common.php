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
			$tpl = self::parseTplVar($tpl, $key, $value);
		}
		
		return $tpl;
	}
	
	protected static function parseTplVar($tpl, $name, $value, $namePrefix = '') {	
		if(!empty($namePrefix)) {
			$name = $namePrefix . $name;
		}
		
		if(is_array($value)) {
			// if value is an array, we search for corresponding START and END tags
			$startToken = '@' . $name . 'START@';
			$endToken = '@' . $name . 'END@';
			
			$start = strpos($tpl, $startToken);
			$end = strpos($tpl, $endToken) + strlen($endToken);
			if($start !== false && $end !== false) {
				$loop = substr($tpl, ($start), ($end - $start));
				
				$before = substr($tpl, 0, $start);
				$after = substr($tpl, $end);

				$all = '';
				$contentForLoop = $loop;
				if(is_string(key($value))) {
					foreach($value as $oneKey => $oneValue) {
						$contentForLoop = self::parseTplVar($contentForLoop, $oneKey, $oneValue, $name . '.');
					}
					$tpl = $contentForLoop;
				} else {
					foreach($value as $oneKey => $oneValue) {
						$all .= self::parseTplVar($loop, $name, $oneValue);
					}
					$tpl = $all;
				}
			} else {
				throw new \rk\exception('tpl error');
			}

			// replace loop by new content
			$tpl = $before . $tpl . $after;
			$tpl = str_replace($startToken, '', $tpl);
			$tpl = str_replace($endToken, '', $tpl);
			
		} else {
			$tpl = str_replace('@' . $name . '@',  $value, $tpl);
		}
		
		return $tpl;
	}
	
	public static function createFileFromTpl($tplPath, $tplVars, $destination) {
		$data = self::useTpl($tplPath, $tplVars);
		\rk\helper\fileSystem::file_put_contents($destination, $data);
	}
}