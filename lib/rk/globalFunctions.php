<?php 

function shutdown_handler() {
	$lastError = error_get_last();
	if(!empty($lastError) && (
		$lastError['type'] == E_ERROR || $lastError['type'] == E_PARSE || $lastError['type'] == E_CORE_ERROR || 
		$lastError['type'] == E_COMPILE_ERROR || $lastError['type'] == E_USER_ERROR || $lastError['type'] == E_RECOVERABLE_ERROR
	)) {
		
/*		if($lastError['type'] == E_ERROR && class_exists('\rk\manager') && \rk\manager::isDevMode()) {
			header('HTTP/1.1 500 Internal Server Error');
			var_dump($lastError);
			die('An exception has occured');
		}*/
		
		log_error($lastError['type'], $lastError['message'], $lastError['file'], $lastError['line']);
		
		$script = '<script type="text/rkscript">
		' . \rk\webLogger::getLogsJSOutput() . '
		</script>';
		header('HTTP/1.1 500 Internal Server Error');
		die($script.'An exception has occured');
	}
	return false;
}

function log_error($errno, $errstr, $errfile, $errline) {
	switch($errno) {
		case E_ERROR:
		case E_PARSE:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_USER_ERROR:
		case E_RECOVERABLE_ERROR:
			$level = 'FATAL';
		break;
		
		case E_WARNING:
		case E_NOTICE:
		case E_CORE_WARNING:
		case E_COMPILE_WARNING:
		case E_USER_WARNING:
		case E_USER_NOTICE:
			$level = 'WARNING';
		break;
		
		case E_STRICT:
		case E_DEPRECATED :
		case E_USER_DEPRECATED :
			$level = 'INFO';
		break;

		default: 
			throw new \rk\exception('unknown error type');
	}
	
	\rk\webLogger::add(array('level' => $level, 'error' => $errstr, 'file' => $errfile, 'line' => $errline, 'code' => $errno), 'ERROR');
	
}

function error_handler($errno, $errstr, $errfile, $errline) {
	log_error($errno, $errstr, $errfile, $errline);
	return false;
}

function urlFor ($url, array $getParams = array()) {
	return \rk\helper\url::urlFor($url, $getParams);
}

function i18n ($key, array $replacements = array(), array $params = array()) {
	return \rk\i18n::get($key, $replacements, $params);
}

function i18nHTML ($key, array $replacements = array(), array $params = array()) {
	$params = array_merge($params, array('htlentities' => true));
	return \rk\i18n::get($key, $replacements, $params);
}


// from http://php.net/manual/en/function.apache-request-headers.php#74592
if( !function_exists('apache_request_headers') ) {
	function apache_request_headers() {
		$arh = array();
		$rx_http = '/\AHTTP_/';
		foreach($_SERVER as $key => $val) {
			if( preg_match($rx_http, $key) ) {
				$arh_key = preg_replace($rx_http, '', $key);
				$rx_matches = array();
				// do some nasty string manipulations to restore the original letter case
				// this should work in most cases
				$rx_matches = explode('_', $arh_key);
				if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
					foreach($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
					$arh_key = implode('-', $rx_matches);
				}
				$arh[$arh_key] = $val;
			}
		}
		return( $arh );
	}
}