<?php

namespace rk\scripts\tools;

class common {
	
	//variable de coloration texte
	public static $PRINT_YELLOW = "\033[01;33m";
	public static $PRINT_CYAN = "\033[01;36m";
	public static $PRINT_GREEN = "\033[01;32m";
	public static $PRINT_BOLD = "\033[1;37m";
	public static $PRINT_STD = "\033[00m";
	public static $PRINT_RED = "\033[01;31m";

	//Verifie que l'on se trouve dans le bon repertoire
	public static function checkLauncher () {
		
		$ppid = posix_getppid();
		$error = "Ce script doit être lancé via rkTools !\n";
		
		if (empty($ppid)) {
			echo $error;
			exit(1);
		}
		
		$pCmdline = file_get_contents('/proc/' . $ppid . '/cmdline');
				
		if (strpos($pCmdline, 'rkTools') == false) {
			echo $error;
			exit(1);
		}
	}
}