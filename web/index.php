<?php 

// check PHP version
if (version_compare(PHP_VERSION, '5.5.0', '<')) {
	die('incorrect PHP version. 5.5 or greater required');
}

session_start();
// used to compute generation times	
$_SESSION['rkTime'] = microtime(true);

// include global functions
include('../lib/rk/globalFunctions.php');

// mise en place de l'autoloader
include('../lib/rk/autoloader.class.php');
\rk\autoloader::init(__DIR__ . '/../cache');

register_shutdown_function("shutdown_handler");

set_error_handler("error_handler");

try {
	// launch manager
	$manager = \rk\manager::getInstance();
		
	
	// add the request to the webLogs
	\rk\webLogger::addRequestLog();
	
	// add user at start
	\rk\webLogger::addUserLog();
	
	
	
	// parse request
	$manager->getRequestAnswer();
	
	unset($_SESSION['rkTime']);
} catch(\rk\exception $e) {
	if($e instanceof \rk\exception) {
		$e->showAndDie();
	} else {
		unset($_SESSION['rkTime']);
		if(!empty($e->xdebug_message)) {
			echo('<table>' . $e->xdebug_message . '</table>');
		} else {
			var_dump('Unhandled exception occured', $e);
		}
	}
}