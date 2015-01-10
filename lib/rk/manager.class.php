<?php

namespace rk;

class manager {
	
	/**
	 * @var \rk\requestHandler
	 */
	protected $requestHandler;
	
	
	/**
	 * @var rk\configHandler
	 */
	protected $configHandler;
	
	protected $user;
	
	/**
	 * @var \rk\app
	 */
	protected $application;
	
	private static $instance;
	
	
	public function __construct() {
		if(!empty(self::$instance)) {
			throw new \rk\exception('manager instance already running');
		}
		self::$instance = $this;
		
		$this->configHandler = new configHandler();
		$this->requestHandler = new requestHandler();
		
		try {
			$this->getRequestHandler()->parseRequest();
		} catch(\rk\exception\actionNotFound $e) {
			if(self::isDevMode()) {
				\rk\autoloader::makeCache();
			}
			$this->requestHandler->die404();
		}
		
		$app = self::getApplication();
		$userClass = $app->getUserClassName();
		
		$this->user = new $userClass();
	}
	
	
	public static function getInstance() {
		if(empty(self::$instance)) {
			new manager();
		}
		
		return self::$instance;
	}
	
	public static function getRootDir() {
		return str_replace('lib/rk', '', __DIR__);
	}
	
	public static function getWebDir() {
		return self::getRootDir() . 'web/';
	}
	
	//return relative path to web/uploads/app/
	public static function getWebUploadDirForApp($appName = null) {
		if (empty($appName)) {
			$appName = self::getApplication()->getAppName();
		}
		
		$dirName = \rk\manager::getWebDir() . 'uploads/' .$appName . '/';
		if (!file_exists($dirName)) {
			\rk\helper\fileSystem::mkdir($dirName);
		}
		
		return substr($dirName, strlen(self::getRootDir()));
	}	
		
	public static function getCacheDir() {
		return self::getRootDir() . 'cache/';
	}
	
	public static function getRessourcesDir() {
		return self::getRootDir() . 'ressources/';
	}
	
	//return relative path to ressources/uploads/app/
	public static function getRessourcesUploadDirForApp($appName = null) {
		if (empty($appName)) {
			$appName = self::getApplication()->getAppName();
		}
	
		$dirName = \rk\manager::getRessourcesDir() . 'uploads/' .$appName . '/';
		if (!file_exists($dirName)) {
			\rk\helper\fileSystem::mkdir($dirName);
		}
	
		return substr($dirName, strlen(self::getRootDir()));
	}
		
	public static function getTemplatesDir() {
		return self::getRessourcesDir() . 'templates/';
	}

	public static function getTemplatePath($fileName) {
		// try to get a user version of given template...
		$userPath = self::getTemplatesDir() . 'user/' . $fileName;
		if(file_exists($userPath)) {
			return $userPath;
		}
		
		// or a rk version of it
		$rkPath = self::getTemplatesDir() . 'rk/' . $fileName;
		if(file_exists($rkPath)) {
			return $rkPath;
		}

		throw new \rk\exception\fileNotFound('template not found ' . $fileName);
	}
	
	public static function getFormTemplatePath($fileName) {
		return self::getTemplatePath('forms/' . $fileName);
	}
	public static function getFormWidgetTemplatePath($fileName) {
		return self::getTemplatePath('forms/widgets/' . $fileName);
	}
	public static function getPagerTemplatePath($fileName) {
		return self::getTemplatePath('pagers/' . $fileName);
	}
	public static function getLayoutTemplatePath($fileName) {
		return self::getTemplatePath('layouts/' . $fileName);
	}
	public static function getActionTemplatePath($moduleName, $fileName) {
		$appName = self::getInstance()->getRequestHandler()->getApplicationName();
		return self::getRootDir() . '/app/' . $appName . '/modules/' . $moduleName . '/templates/' . $fileName;
	}
	
	public static function getRequestParams() {
		return self::getInstance()->getRequestHandler()->getRequestParams();
	}
	public static function getPostParams() {
		return self::getInstance()->getRequestHandler()->getPostParams();
	}
	public static function getGetParams() {
		return self::getInstance()->getRequestHandler()->getGetParams();
	}
	public static function getRequestURL() {
		return self::getInstance()->getRequestHandler()->getRequestURL();
	}
	
	public static function isDevMode() {
		return self::getInstance()->getRequestHandler()->isDevMode();
	}
	public static function isDbgMode() {
		return self::getInstance()->getRequestHandler()->isDbgMode();
	}
	
	public static function isAjax() {
		return self::getInstance()->getRequestHandler()->isAjax();
	}

	/**
	 * 
	 * @param unknown_type $app
	 * @return \rk\app
	 */
	public static function getApplication() {
		if(empty(self::getInstance()->application)) {
			$class = 'user\\' . self::getInstance()->getRequestHandler()->getApplicationName() . '\application';
		
			self::getInstance()->application = new $class();
		}
		
		return self::getInstance()->application;
	}
	
	public function renderRequest($app, $module, $action, array $params = array()) {
		$appObj = self::getApplication();
		
		return $appObj->getOutput($module, $action, $params);
	}
	
	
	private static function generateAllCaches() {
		\rk\autoloader::makeCache();
		\rk\i18n::makeCache();
	}
	
	public function getRequestAnswer() {
		if(self::isDevMode()) {
			// error display
			ini_set('display_errors', 1);
			ini_set('error_reporting', E_ALL);
			
			// regeneration of cache
			self::generateAllCaches();
		} else {
			ini_set('display_errors', 0);
			
			// check if i18n cache folder exists
			if(!is_dir(self::getCacheDir() . 'i18n')) {
				// if not : regeneration of i18n cache
				\rk\i18n::makeCache();
			}
		}
		
		$out = $this->renderRequest(
			$this->requestHandler->getApplicationName(), 
			$this->requestHandler->getModuleName(), 
			$this->requestHandler->getActionName(),
			$this->requestHandler->getRequestParams()
		);

		echo $out;
	}
	
	public static function getConfigParam($paramName, $defaultValue = null, $appName = null) {
		return self::getInstance()->configHandler->getParam($paramName, $defaultValue, $appName);
	}
	
	
	/**
	 * @return \rk\user
	 */
	public static function getUser() {
		return self::getInstance()->user;
	}
	public static function isUserAuth() {
		return self::getInstance()->user->isAuth();
	}
	
	/**
	 * @return rk\requestHandler
	 */
	public function getRequestHandler() {
		return $this->requestHandler;
	}
	
	
	
	public static function getUniqueId () {
		$return = '';
		
		// formatte un microtime dans un format court mais néanmoins unique
		$split = explode(' ', microtime());
		$return = substr($split[1], -6);
		$return .= '-' . substr($split[0], 2, -2);
		
		return $return;
	}
	
		
	public static function includeAction($module, $action, array $params = array()) {
		$appObj = self::getApplication();
		
		return $appObj->includeAction($module, $action, $params);
	}

	
}