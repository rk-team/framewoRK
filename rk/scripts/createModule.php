<?php

include('rk/scripts/tools/common.php');
use rk\scripts\tools;

tools\common::checkLauncher();

//Load rkFmwk Classes
include('lib/rk/autoloader.class.php');
\rk\autoloader::init();

if($argc >= 2) {
	$app = $argv[1];
	$module = $argv[2];
	$action = empty($argv[3])?'index':$argv[3];

	// application
	$appPath = \rk\manager::getRootDir() . 'app/' . $app . '/';
	if(!file_exists($appPath)) {
		// application doesn't exist : we create its folders and class
		\rk\helper\fileSystem::mkdir($appPath);
		\rk\helper\fileSystem::mkdir($appPath . 'modules/');
		
		$tplPath = \rk\manager::getRootDir() . 'scripts/tools/createModule/templates/application.tpl';
		$tplVars = array('app' => $app);
		$destination = $appPath . 'application.class.php';
		
		tools\common::createFileFromTpl($tplPath, $tplVars, $destination);
		
		echo tools\common::$PRINT_CYAN . "\n" . 'Application ' . $app . ' created ' . tools\common::$PRINT_STD . "\n";
	} else {
		echo tools\common::$PRINT_GREEN . "\n" . 'Application ' . $app . ' already exists ' . tools\common::$PRINT_STD . "\n";
	}
	
	// module
	$modulePath = $appPath . 'modules/' . $module . '/';
	if(!file_exists($modulePath)) {
		// module doesn't exist : we create its folders and class
		\rk\helper\fileSystem::mkdir($modulePath);
		\rk\helper\fileSystem::mkdir($modulePath . 'templates/');
		
		$tplPath = \rk\manager::getRootDir() . 'scripts/tools/createModule/templates/module.tpl';
		$tplVars = array(
			'app'		=> $app,
			'module'	=> $module,
			'action'	=> $action
		);
		$destination = $modulePath . $module . '.class.php';
		
		tools\common::createFileFromTpl($tplPath, $tplVars, $destination);
		
		\rk\helper\fileSystem::file_put_contents($modulePath . 'templates/' . $action . '.php', '');
		
		echo tools\common::$PRINT_CYAN . "\n" . 'Module ' . $module . ' created ' . tools\common::$PRINT_STD . "\n";
	} else {
		echo tools\common::$PRINT_GREEN . "\n" . 'Module ' . $module . ' already exists ' . tools\common::$PRINT_STD . "\n";
	}
	
	
} else {
	echo tools\common::$PRINT_CYAN . "\n" . 'Creates a module for an application' . "\n";
	echo tools\common::$PRINT_GREEN . "\n" . 'Usage : ./rkTools createModule <applicationName> <moduleName> [<actionName>]' . "\n";
	echo tools\common::$PRINT_BOLD . "\t" . '<applicationName> : name of the application (will be created if necessary)' . "\n";
	echo "\t" . '<moduleName> : name of the module' . "\n";
	echo "\t" . '<actionName> (optionnal) : name of the action to be created in the module. Defaults to "index"' . tools\common::$PRINT_STD . "\n";	
}
