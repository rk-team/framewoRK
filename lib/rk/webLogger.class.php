<?php

namespace rk;

class webLogger {
	
	protected static $logs = array();

	public static function add($data, $type = 'OTHER') {
		if(is_array($data) && !empty($data['selfDuration'])) {
			$data['selfDuration'] = sprintf('%.6f', $data['selfDuration']);
		}
		
		if(!is_array($data)) {
			$data = array(
				'data' => $data
			);
		}

		if(!empty($_SESSION['rkTime'])) {
			$data['cumulativeDuration'] =  sprintf('%.6f', microtime(true) - $_SESSION['rkTime']);
		}
		
		$logs = array();
		foreach($data as $key => $value) {
			if(!is_array($value) && !is_object($value)) {
				$logs[$key] = $value;
			} else {
				ob_start();
				var_dump($value);
				$logs[$key] = ob_get_clean();
			}
		}

		$params = array(
			'date' 	=> date('Y-m-d H:i:s'),
			'data'	=> $logs,
			'type'	=> $type,
		);
		
// 		if($type != 'REQUEST' && $type != 'OUTPUT' && $type != 'SQL' && $type != 'USER') {
			$trace = debug_backtrace();
			$caller = str_replace(\rk\manager::getRootDir(), '', $trace[0]['file']);
			$caller .= ' (line ' . $trace[0]['line'] . ')';
			$params['caller'] = $caller;
// 		}
		
		self::$logs[] = $params;
	}
	
	public static function addRequestLog() {
		$requestParams = array(
			'APPLICATION'	=> 'START',
			'URL'			=> \rk\manager::getRequestURL()
		);
		// format get and post params and add them to logs
		if(!empty(\rk\manager::getPostParams())) {
			$requestParams['$_POST'] = \rk\manager::getPostParams();
		}
		if(!empty(\rk\manager::getGetParams())) {
			$requestParams['$_GET'] = \rk\manager::getGetParams();
		}
		self::add($requestParams, 'APPLICATION');
	}
	
	public static function addUserLog() {
		$requestParams = array(
			'URL'	=> \rk\manager::getRequestURL()
		);
		// format get and post params and add them to logs
		if(!empty(\rk\manager::getPostParams())) {
			$requestParams['$_POST'] = \rk\manager::getPostParams();
		}
		if(!empty(\rk\manager::getGetParams())) {
			$requestParams['$_GET'] = \rk\manager::getGetParams();
		}
		$user = \rk\manager::getUser();
		$requestParams = array(
			'authentified'	=> $user->isAuth(),
			'language'		=> $user->getLanguage(),
		);
		if($user->isAuth()) {
			$requestParams['userName'] = $user->getUserName();
			$requestParams['groups'] = $user->getGroups();
		}
		self::add($requestParams, 'USER');
	}
	
	public static function getLogsJSOutput() {
		
		if(\rk\manager::isDbgMode()) {
			
			// add user to logs
			self::addUserLog();
	
			// add request time to logs
			$requestParams = array('APPLICATION' => 'END');
			self::add($requestParams, 'APPLICATION');
	
			
			$data = json_encode(array(
				'URL' => \rk\manager::getRequestURL(),
				'logs' => self::$logs
			));
			
			return 'rk.widgets.webLog.getInstance().addLogs(' . $data . ');';
		}
		
		return '';
	}
}