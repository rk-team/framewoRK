<?php

namespace rk\app;

abstract class action {
	
	protected
		$tplParams = array(),	// params utilisés dans le template et remplis par la fonction execute
		$params = array(),		// params d'entrée
		$applicationName,
		$isIncluded = false,
		$prependContent = array(),	// content to be prepended to ouput
		$appendContent = array(),	// content to be appended to ouput
		$moduleName,
		$actionName,
		$requiredParams = array(),
		$outputFormat = null,
		$defaultTemplateFileName,
		$templateFileName,
		$templateFullPath,
		$outputWith;
	
	abstract function execute();
	
	public function __construct(array $params = array()) {
		$this->init($params);
	}
	
	
	public function getTplPath() {
		if($this->templateFileName === false) {
			return false;
		}
		if(empty($this->templateFileName)) {
			return $this->templatePath . '/' . $this->defaultTemplateFileName;
		}
		
		return $this->templatePath . '/' . $this->templateFileName;
	}
	
	public function setTemplate($fileName) {
		$this->templateFileName = $fileName;
		$this->outputWith = null;
	}
	
	public function init(array $params) {
		// on split le nom namespacé de la classe
		$class = get_class($this);
		$data = explode('\\', $class);
		
		$this->applicationName = $data[1];
		$this->moduleName = $data[3];
		$this->actionName = $data[4];
		
		$this->templatePath = \rk\manager::getRootDir() . '/app/' . $this->applicationName . '/modules/' . $this->moduleName . '/templates/';
		$this->defaultTemplateFileName = $this->actionName . '.php';
		
		$this->params = $params;
		
		if(!empty($this->requiredParams)) {
			foreach($this->requiredParams as $one) {
				if(!array_key_exists($one, $this->params)) {
					throw new \rk\exception('missing param for action ' . $one);
				}
			}
		}
	}
	
	public function setParam($name, $value) {
		$this->params[$name] = $value;
	}
	public function getParam($name) {
		if(!empty($this->params[$name])) {
			return $this->params[$name];
		}
		return null;
	}
	public function hasParam($name) {
		if(array_key_exists($name, $this->params)) {
			return true;
		}
		return false;
	}
	
	protected function securityCheck() {
		if(method_exists($this, 'checkCredentials')) {
			// a checkCredentials method is defined on the action : we use it
			return $this->checkCredentials();
		} elseif(!empty($this->requiresAuth) && !\rk\manager::isUserAuth()) {
			// action requires user to be authentified
			return false;
		} elseif(!empty($this->requiresGroup)) {
			// action requires user to be part of given group(s)
			$groups = $this->requiresGroup;
			if(!is_array($groups)) {
				$groups = array($groups);
			}
			if(!\rk\manager::getUser()->hasGroup($groups)) {
				return false;
			}
		}
		
		return true;
	}
	
	public function isJSONOutput() {
		return ($this->getOutputFormat() == 'json' || (!empty($this->params['outputFormat']) && $this->params['outputFormat'] == 'json'));
	}
	
	public function getOutput() {
		$start = microtime(true);
		if(!$this->securityCheck()) {
			throw new \rk\exception\security('invalid access');
		}
		
		$logs = array('START' => $this->applicationName . ' / ' . $this->moduleName . ' / ' . $this->actionName);
		\rk\webLogger::add($logs, 'OUTPUT');
		
		$this->execute();
		
		$out = '';
		
		if($this->isJSONOutput()) {
			$out = json_encode($this->tplParams);
		} else {
			
			if (!empty($this->outputWith)) {
				$out = $this->outputWith->getOutput();
			} elseif(!empty($this->getTplPath())) {
				$tplPath = $this->getTplPath();
				$out = \rk\helper\output::getOutput($tplPath, $this->tplParams);
			}
			
			if(!empty($this->prependContent)) {
				foreach ($this->prependContent as $oneContent) {						
					$out = $oneContent . $out;
				}
			}
			if(!empty($this->appendContent)) {
				foreach ($this->appendContent as $oneContent) {
					$out = $out . $oneContent;
				}
			}
		}
		
		$duration = microtime(true) - $start;
		$logs = array(
			'END' => $this->applicationName . ' / ' . $this->moduleName . ' / ' . $this->actionName,
			'selfDuration' => $duration
		);
		\rk\webLogger::add($logs, 'OUTPUT');
		
		return $out;
	}
	
	public function prependContent($content) {
		$this->prependContent[] = $content;
	}
	
	public function appendContent($content) {
		$this->appendContent[] = $content;
	}
	
	public function getOutputFormat() {
		return $this->outputFormat;
	}
	
	public function getTplParams() {
		return $this->tplParams;
	}
	
	
	public function redirect($url) {
		header('Location: ' . $url);
		exit;
	}
	
	
	public function markAsIncluded() {
		$this->isIncluded = true;
	}
	
	public function isIncluded() {
		return $this->isIncluded;
	}
	
	public function outputWith ($obj) {
		if (!method_exists($obj, 'getOutput')) {
			throw new \rk\exception('invalid object for outputWith');
		}
		$this->outputWith = $obj;
	}	
	
	/**
	 * @return \rk\pager
	 */
	public function getPager($pagerClass, $requestParams, array $pagerParams = array()) {
		$class = '\user\pager\\' . $pagerClass;
		
		if (empty($pagerParams ['destination'])) {
			$pagerParams['destination'] = $this->getURL();
		}
			
		$pager = new $class($requestParams, $pagerParams);
		$this->outputWith($pager);
		
		return $pager;
	}
	
	/**
	 * @return \rk\crud
	 */
	public function getCrud($modelClass, $requestParams, array $params = array()) {
		
		$modelClass = '\user\model\\' . $modelClass;
		
		if (empty($params ['destination'])) {
			$params['destination'] = $this->getURL();
		}
				
		$crud = new \rk\crud($modelClass, $requestParams, $params);
		$this->outputWith($crud);
		
		return $crud;
	}

	/**
	 * @var \rk\form
	 */
	protected function getForm($formClass, $formValues = array(), array $formParams = array()) {
		
		$class = '\user\form\\' . $formClass;
		
		if(empty($formParams['destination'])) {
			$formParams['destination'] = $this->getURL();
		}
				
		$form = new $class($formValues, $formParams);
		$this->outputWith($form);
		
		return $form;
	}
	
	/**
	 * @var \rk\form
	 */
	public function getNewForm($formClass, $resquestParams = array(), array $formParams = array()) {	
		return $this->getForm($formClass, $resquestParams, $formParams);
	}
	/**
	 * @var \rk\form
	 */
	public function getEditForm($object, array $formParams = array()) {
		$class = get_class($object);
		$formClass = str_replace('user\object\\', '', $class);
				
		return $this->getForm($formClass, $object, $formParams);
	}
	
	
	public function getURL() {
		$requestParams = array();
		foreach ($this->requiredParams as $oneRequired) {
			$requestParams[$oneRequired] = $this->params[$oneRequired];
		}
		
		return urlFor('/_' . $this->applicationName . '/' . $this->moduleName . '/' . $this->actionName, $requestParams);
	}
}
