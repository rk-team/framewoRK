<?php

namespace rk;

abstract class user {
	
	protected
		$authentified = false,
		$userName,
		$data,
		$groups = array(),
		$language;
		
	abstract public function login($login, $password);
	
	public function __construct() {
		if(!empty($_SESSION[$this->getSessionBaseName()])) {
			$this->language 	= $_SESSION[$this->getSessionBaseName()]['language'];
			$this->groups 		= $_SESSION[$this->getSessionBaseName()]['groups'];
			$this->userName 	= $_SESSION[$this->getSessionBaseName()]['userName'];
			$this->data 		= $_SESSION[$this->getSessionBaseName()]['data'];
			$this->authentified	= $_SESSION[$this->getSessionBaseName()]['authentified'];
		} else {
			$this->language = self::autoDetectLanguage();
		}
	}
	
	public function setLanguage($code) {
		$this->language = $code;
	}
	
	public static function autoDetectLanguage() {
		$languages = \rk\manager::getConfigParam('project.languages');
		
		if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
			$orderedLevels = explode(';', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			foreach ($orderedLevels as $oneLevel) {
				$orderedLanguages = explode(',', $oneLevel);
				foreach ($orderedLanguages as $oneLanguage) {
					// skipping 'q=0.x'
					if (strpos($oneLanguage, 'q=') === false) {
						// 2 possible formats : 'fr' ou 'fr-fr'
						if (strpos($oneLanguage, '-') !== false) {
							list($languageCode, $countryCode) = explode('-', $oneLanguage);
						} else {
							$countryCode = $languageCode = $oneLanguage;
						}
						if(in_array($languageCode, $languages)) {
							// we return the first language existing in conf
							return $languageCode;
						}
					}
				}
			}
		}
	
		return \rk\manager::getConfigParam('project.default_language');
	}
	
	protected function getSessionBaseName() {
		return 'rkUser-' . get_class($this);
	}
	
	public function __destruct() {
		// save data to session when script ends
// 		if(!empty($_SESSION[$this->getSessionBaseName()])) {
			$_SESSION[$this->getSessionBaseName()] = array(
				'language'		=> $this->language,
				'groups'		=> $this->groups,
				'userName'		=> $this->userName,
				'data'			=> $this->data,
				'authentified'	=> $this->authentified,
			);
// 		}
	}
	
	
	
	public function isAuth() {
		return $this->authentified;
	}	
	
	public function getLanguage() {
		return $this->language;
	}
	
	public function getUserName() {
		return $this->userName;
	}
	
	public function getGroups() {
		return $this->groups;
	}
	
	public function getData($name) {
		if(!empty($this->data[$name])) {
			return $this->data[$name];
		}
		return null;
	}
	
	public function hasGroup($groupName) {
		if(!is_array($groupName)) {
			$groupName = array($groupName);
		}
		foreach($groupName as $oneGroupName) {
			if(in_array($oneGroupName, $this->groups)) {
				return true;
			}
		}
		
		return false;
	}
	
	public function logout() {
		unset($_SESSION[$this->getSessionBaseName()]);
		$this->groups 		= array();
		$this->userName 	= null;
		$this->data 		= null;
		$this->authentified	= false;
	}
	
}