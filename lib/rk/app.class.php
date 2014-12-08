<?php

namespace rk;

abstract class app {
	
	protected $userClassName = '\user\user';
	
	protected $layout;
	
	private static $DEFAULT_LAYOUT = 'default.php';
	
	protected $tplParams = array();
	
	/**
	 * returns application name
	 * @return string
	 */
	public function getAppName() {
		$className = \rk\helper::getClassFromNamespace($this, 2);
		
		return $className;
	}
	
	public function getUserClassName() {
		return $this->userClassName;
	}
	
	
	public function getCurrentAppDir() {
		return \rk\manager::getRootDir() . '/app/' . $this->getAppName();
	}
	
	public function getLayoutPath() {
		if(!empty($this->layout)) {
			// a layout attribute was defined : we use it 
			$path = \rk\manager::getLayoutTemplatePath($this->layout);
			if(!file_exists($customAppLayoutPath)) {
				throw new \rk\exception('cant find layout');
			}
		} else {
			// we search for a layout named <appName> in the layout dir
			try {
				$customAppLayoutPath = \rk\manager::getLayoutTemplatePath($this->getAppName() . '.php');
				if(file_exists($customAppLayoutPath)) {
					$path = $customAppLayoutPath;
				}
			} catch(\rk\exception\fileNotFound $e) {
				// if the template does not exist, we do no want any exception to be thrown			
			}
		}
		
		if(empty($path)) {
			$path = \rk\manager::getLayoutTemplatePath(self::$DEFAULT_LAYOUT);
		}
		
		return $path;
	}
	
	/**
	 * returns output for application : action called in web request + all included actions
	 * @param unknown_type $moduleName
	 * @param unknown_type $actionName
	 * @param array $params
	 * @return string
	 */
	public function getOutput($moduleName, $actionName, array $params = array()) {
		
		$actionObject = $this->getActionObject(
			$moduleName,
			$actionName,
			$params
		);
		
		$output = $this->getActionOutput($actionObject);
							
		if ($actionObject->isJSONOutput()) {
			return $output;
			
		}
		
		if(\rk\manager::isAjax()) {
			// ajax request : we just return the output
			$out = $output;
			
			$out .= '<script type="text/rkscript">' . \rk\webLogger::getLogsJSOutput() . '</script>';
			
			$out = str_replace('<script type="text/javascript">', '<script type="text/rkscript">', $out);
			
		} else {
			// we get the params for the template
			$tplParams = $this->prepareTplParams($output);
			
			// and use the layout
			$out = \rk\helper\output::getOutput($this->getLayoutPath(), $tplParams);
		}
		
		return $out;
	}
	
	protected function addToTplParams($name, $data) {
		$this->tplParams[$name] = $data;
	}
	
	protected function prepareTplParams($output) {
		// we add the content of the JS & CSS
		$jsController = new \rk\controller\JS(\rk\manager::isDevMode());
		$cssController = new \rk\controller\CSS(\rk\manager::isDevMode());
		
		$tplParams = array(
			'language' 	=> \rk\manager::getUser()->getLanguage(),
			'title' 	=> $this->getAppName(),
			'content' 	=> $output,
			'jsContent'	=> $jsController->getContent(),
			'cssContent'=> $cssController->getContent(),
		);
		
		foreach($this->tplParams as $key => $value) {
			$tplParams[$key] = $value;
		}
		
		return $tplParams;
	}
	
	/**
	 * 
	 * @param unknown_type $moduleName
	 * @param unknown_type $actionName
	 * @param array $params
	 * @return \rk\app\action
	 */
	public function getActionObject($moduleName, $actionName, array $params) {
	
		$actionClass = 'user\\' . $this->getAppName() . '\modules\\' . $moduleName . '\\' . $actionName;
		$action = new $actionClass($params);
	
		return $action;
	}
	
	/**
	 * might be overloaded. useful to add an application level menu for instance 
	 * @see \user\JSTest\application for an example
	 * @param \rk\app\action $action
	 * @param array $params
	 * @return string
	 */
	public function getActionOutput(\rk\app\action $action) {
		$actionOutput = $action->getOutput();

		return $actionOutput;
	}

	
	public function includeAction($module, $action, array $params = array()) {
// 		$logs = array('INCLUDE' => $this->getAppName() . ' / ' . $module . ' / ' . $action);
// 		\rk\webLogger::add($logs, 'OUTPUT');
		
		$action = $this->getActionObject(
				$module,
				$action,
				$params
		);
		$action->markAsIncluded();
		
		$actionOutput = $this->getActionOutput($action);
	
		return $actionOutput;
	}
}
