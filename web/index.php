<?php 

// check PHP version
if (version_compare(PHP_VERSION, '5.5.0', '<')) {
	die('incorrect PHP version. 5.5 or greater required');
}

session_start();
// used to compute generation times	
$_SESSION['rkTime'] = microtime(true);

// include global functions
include('../rk/lib/globalFunctions.php');

// mise en place de l'autoloader
include('../rk/lib/autoloader.class.php');
\rk\autoloader::init(__DIR__ . '/../cache');




register_shutdown_function("shutdown_handler");
set_error_handler("error_handler");

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

try {
	// launch manager
	$manager = \rk\manager::getInstance();
} catch(\Exception $e) {
	
	// exception occured during creation of the manager : we display it no matter what as it corresponds to an installation error
	print_exception($e);
	die();
}


ini_set('display_errors', 0);


try {
	$manager->init();
	
	// add the request to the webLogs
	\rk\webLogger::addRequestLog();	
	// add user at start
	\rk\webLogger::addUserLog();
		
	
	// parse request
	$manager->getRequestAnswer();
	
	unset($_SESSION['rkTime']);
} catch(\Exception $e) {
	if($e instanceof \rk\exception) {
		$e->showAndDie();
	} else {
		unset($_SESSION['rkTime']);
		print_exception($e);
	}
}