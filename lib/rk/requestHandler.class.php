<?php

namespace rk;

class requestHandler {
	
	private
		$applicationName = '',
		$moduleName = '',
		$actionName = '',
		$requestParams = array(),
		$postParams = array(),
		$getParams = array(),
		$requestHeaders = array(),
		$requestURL = '',
		$requestFiles = array(),
		$requestMode = 'plain',
		$outputFormat = null,
		$isDevMode = false,
		$isDbgMode = false;
	
	public function parseRequest() {
		$this->requestParams = $_REQUEST;
		$this->postParams = $_POST;
		$this->getParams = $_GET;
		$this->requestFiles = $_FILES;
		
		if (PHP_SAPI !== 'cli') {
			$this->requestURL = str_replace('//', '/', $_SERVER['REQUEST_URI']);
			$this->requestHeaders = apache_request_headers();
		}		
				
		if(!empty($this->requestFiles)) {
			// on rattache les données de $_FILES à leurs formulaires d'origine dans requestParams
			foreach($this->requestFiles as $formName => $data) {
				foreach($data as $dataName => $oneData) {
					foreach($oneData as $inputName => $value) {
						$this->requestParams[$formName][$inputName][$dataName] = $value;
					}
				}
			}
		}
	
		// on définit si la requête a été envoyée en AJAX ou en page pleine
		if(
				(!empty($this->requestHeaders['X-Requested-With']) && $this->requestHeaders['X-Requested-With'] == 'XMLHttpRequest')
				||
				(!empty($this->requestParams['rkForceAjax']))
		) {
			$this->requestMode = 'ajax';
		} else {
			$this->requestMode = 'plain';
		}
		
		
		// on définit le format de sortie en fonction des paramètres de l'URL
		if(!empty($this->requestParams['outputFormat'])) {
			$this->outputFormat = $this->requestParams['outputFormat'];
		}		
		
		if(!empty($_SERVER['REMOTE_ADDR'])) {
			$userIP = $_SERVER['REMOTE_ADDR'];
			$dbgIps = \rk\manager::getConfigParam('project.dbg_IPS', array());
			if(in_array($userIP, $dbgIps)) {
				$this->isDbgMode = true;
			}
		}
		
		// on commence par regarder si on est mode dev
		$pos = strpos($this->requestURL, '/dev/');
		if($pos === 0 || $this->requestURL === '/dev') {
			// l'url commence par dev : on active le mode dev et on retire le /dev de l'URL pour permettre un traitement normal
			//dsApplication::activateDevMode();
			$url = substr($this->requestURL, 4);
			$devIps = \rk\manager::getConfigParam('project.dev_IPS', array());
			if(in_array($userIP, $devIps)) {
				$this->isDevMode = true;
			} else {
				$this->die403();
			}
		} else {
			$url = $this->requestURL;
		}
		
		//Si index.php est demandé on renvoie le user sur le module home, action : index
		$pos = strpos($url, '/index.php');
		if ($pos === 0 || $url == '/') {
			$application = manager::getConfigParam('project.default_application');
			$module = 'home';
			$action = 'index';
		} else {
			
			//On vire les params de GET
			$pos = strpos($url, '?');
			if($pos !== false) {
				$url = substr($url, 0, $pos);
			}
			
			$split = explode('/', $url);	// comme l'URL commence par un /, l'explode donnera toujours au moins 2 résultats
			if((!empty($split[1])) && (strpos($url, '/_') === 0)) {
				// le premier param commence par un '_', on a donc une application
				$application = substr($split[1], 1);
				// il reste a determiner le module et l'action dans les splits suivants
				if(empty($split[2])) {
					// pas de module dans l'URL ? on utilise home par défaut
					$module = 'home';
				} else {
					$module = $split[2];
				}
				if(empty($split[3])) {
					// pas d'action dans l'URL ? on utilise index par défaut
					$action = 'index';
				} else {
					$action = $split[3];
				}
			} else {
				$application = manager::getConfigParam('project.default_application');
				if(empty($split[1])) {
					// pas de module dans l'URL ? on utilise home par défaut
					$module = 'home';
				} else {
					$module = $split[1];
				}
				if(empty($split[2])) {
					// pas d'action dans l'URL ? on utilise index par défaut
					$action = 'index';
				} else {
					$action = $split[2];
				}
			}
		}
		
		$this->applicationName = $application;
		$this->moduleName = $module;
		$this->actionName = $action;

		$actionClass = '\user\\' . $application . '\modules\\' . $module . '\\' . $action;
		if (!class_exists($actionClass, true)) {
			throw new \rk\exception\actionNotFound($actionClass);
		}
	}

	public function die404() {
		header('HTTP/1.1 404 Not Found');
		$tplHelper = new \rk\helper\template();
		echo $tplHelper->includeTpl('404.php', array('url' => $this->requestURL));
		die();
	}
	
	public function die403() {
		header('HTTP/1.1 403 Forbidden');
		$tplHelper = new \rk\helper\template();
		echo $tplHelper->includeTpl('403.php');
		die();
	}
	
	public function isAjax() {
		if($this->requestMode == 'ajax') {
			return true;
		}
		
		return false;
	}
	
	public function isDevMode() {
		return $this->isDevMode;
	}
	public function isDbgMode() {
		return $this->isDbgMode;
	}
	
	public function getModuleName() {
		return $this->moduleName;
	}
	
	public function getActionName() {
		return $this->actionName;
	}
	
	public function getApplicationName() {
		return $this->applicationName;
	}
	
	public function getRequestParams() {
		return $this->requestParams;
	}
	public function getPostParams() {
		return $this->postParams;
	}
	public function getGetParams() {
		return $this->getParams;
	}
	public function getRequestURL() {
		return $this->requestURL;
	}
	
	public function getRequestFiles() {
		return $this->requestFiles;
	}
	
	public function getOutputFormat() {
		return $this->outputFormat;
	}
	
}