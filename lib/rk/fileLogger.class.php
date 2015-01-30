<?php

namespace rk;

class fileLogger {
	
	private static $folder = 'log/';
	
	public static function add($message, $errfile = null, $errline = null) {
		$folder = \rk\manager::getRootDir() . self::$folder;
		\rk\helper\fileSystem::mkdir($folder);
		
		$path = $folder . \rk\manager::getApplication()->getAppName() . '.log';
		
		$content = '[' . date('Y-m-d H:i:s') . ']';
		if(!empty($_SERVER['REMOTE_ADDR'])) {
			$content .= ' (from ' . $_SERVER['REMOTE_ADDR'] . ') ';
		}
		
		$content .= \rk\manager::getFullURL() . "\n";
		$content .= $message . "\n";
		
		if(!empty($errfile) && !empty($errline)) {
			$content .= 'In ' . $errfile . ' at line ' . $errline . "\n\n";
		}
		
		\rk\helper\fileSystem::file_put_contents($path, $content, true);
	}
}