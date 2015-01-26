<?php

namespace rk;

class exception extends \Exception {
	
	protected
		$params;
	
	public function __construct ($messageKey = null, $params = array()) {
		parent::__construct($messageKey);
		if(!is_array($params)) {
			$params = array($params);
		}
		$this->params = $params;
	}
	
	public function mergeParams (array $params) {
		$this->params = array_merge($this->params, $params);
	}
	
	public function showAndDie() {
		
		if(\rk\manager::hasInstance() && \rk\manager::getInstance()->isDevMode()) {
			$devMode = true;
		} else {
			$devMode = false;
		}
		
		if (PHP_SAPI === 'cli') {
			$cliMode = true;
		} else {
			$cliMode = false;
		}

		$br = '<br />';
		if($cliMode) {
			$br = "\n";
		}
	
		$strParams = print_r($this->params, true);
		$error = 'An exception occured : ';
		$error .= $br;
		$error .= 'Message : ' . $this->getMessage();
		$error .= $br;
		$error .= $br;
		$error .= 'From file : ' . $this->getFile();
		$error .= $br;
		$error .= 'At line : ' . $this->getLine();

		$trace = $this->getTraceAsString();
		if($cliMode) {
			$error .= "\n\n" . '__ Stack trace __: ' . "\n". $trace;
			$error .= "\n\n" . '__ Params __ : ';
			$error .= "\n" . $strParams . "\n";
		} else {
			$error .= '<pre>Stack trace : ' . $trace . '</pre>';
			$error .= 'Params : ';
			$error .= '<pre>' . $strParams . '</pre>';
		}
	

		error_log($error);
		
		if(!$devMode) {
			//Si on est pas en dev on ne donne pas d'info aux hackers
			if(!$cliMode) {
				$error = 'error.exception_occured';
			}
		} else {
			if(!$manager->getRequestHandler()->isAjax()) {
				$type = 'javascript';
			} else {
				$type = 'rkscript';
			}
			$error .= '<script type="text/' . $type . '">
			if(typeof rk !== "undefined") {
				' . \rk\webLogger::getLogsJSOutput() . '
			}
			</script>';
		}		
	
		if(!$cliMode) {
			header('HTTP/1.1 500 Internal Server Error');
		}

		unset($_SESSION['rkTime']);
		die($error);
	}
}