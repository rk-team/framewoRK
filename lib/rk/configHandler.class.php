<?php

namespace rk;

class configHandler {
	
	private 
		$params = array(),		// tableau contenant tous les paramètres des fichiers de conf
		$defaultConfigPath;		// chemin vers le fichier de conf par défaut
	
	
	public function __construct() {
		$this->defaultConfigPath = manager::getRootDir() . '/app/config.ini';
		
		$this->loadConfig();
	}
	
	
	public function getParam($paramName, $defaultValue = null, $app = null) {
		if(empty($app)) {
			$app = 'base/config';
		}
		
		//Recherche en recursif sur la clef fourni
		$res = $this->recursGetParam($this->params[$app], $paramName);
		
		//Si aucun resultat n'est trouvé, et qu'il n'y a pas de defaut
		//on remonte une exception
		if (is_null($res) && is_null($defaultValue)) {
			throw new exception\system('config param not found', array('key' => $paramName, 'app' => $app));
		}
		//Si aucun résultat n'est trouvé et qu'il y a un defaut
		//on retourne le defaut
		if (is_null($res)) {
			$res = $defaultValue;
		}
		
		return $res;
	}
	
	
	
	/**
	 * @brief permet la recuperation des params en recursif dans la tableau de configuration
	 */
	private function recursGetParam (&$currentTabEntry, $completeKey) {
	
		//On split la clef pour trouver le premier token
		$pos = strpos($completeKey, '.');
		if ($pos !== false) {
			//On a un '.', on a donc notre première clef et la suite
			//a donner en recursif a la fonction
			$key = substr ($completeKey, 0, $pos);
			$newKey = substr($completeKey, $pos+1);
		} else {
			//Il s'agit du dernier element de la clef
			//on arrete la recherche après celui ci
			$key = $completeKey;
			$newKey = false;
		}
	
		if (!array_key_exists($key, $currentTabEntry)) {
			//L'entrée n'existe pas
			return null;
		} else if (!empty($newKey)) {
			//L'entrée existe et il reste des elements dans la clef a analyser,
			//on continu la recherche avec le reste de la clef
			return $this->recursGetParam($currentTabEntry[$key], $newKey);
		}
	
		//Fin de la recurtion, l'element est trouvés
		return $currentTabEntry[$key];
	}
	
	
	public function loadConfig() {
		$this->parseContent('base/config', $this->defaultConfigPath);
	}
	
	private function parseContent($appName, $filePath) {
		
		$content = file_get_contents($filePath);
		
		$contentSplit = explode("\n", $content);
		$nbLine = count($contentSplit);
		$currentModule = '';
		for ($i=0; $i<$nbLine; $i++) {
			$newLine = trim($contentSplit[$i]);
			if (!empty($newLine) && ($newLine[0] !== '#')) {
				if ($newLine[0] === '[') {
					//Changement de module
					$currentModule = substr($newLine, 1, strlen($newLine)-2);
				} else {
					//Nouveau param
					try {
						$this->addParam($currentModule, $appName, $newLine);
					} catch (\rk\exception $e) {
						$e->mergeParams(array('path' => $filePath, 'line' => $i));
						throw $e;
					}
				}
			}
		}
	}
	
	/**
	 * @brief appeler par loadConfig sur chaque ligne de configuration
	 * n'étant ni un module '[module]', ni un commentaire '#commentaire'
	 */
	private function addParam ($module, $appName, $paramLine) {
	
		//On verifie que le module n'est pas null
		if (empty($module)) {
			throw new exception\system('config_syntax_error');
		}
	
		//On cree l'entree dans params si elle existe pas
		if (empty($this->params[$appName])) {
			$this->params[$appName] = array();
		}
		if (empty($this->params[$appName][$module])) {
			$this->params[$appName][$module] = array();
		}
	
		//On split sur le '=' pour recuperer clef = value
		$pos = strpos($paramLine, '=');
		if ($pos === false) {
			throw new rkExceptionSystem('config_syntax_error');
		}
		$key = trim(substr($paramLine, 0, $pos));
		$value = $this->parseValue(substr($paramLine, $pos + 1));
	
		//On veut une value et une key
		if (empty($key)) {
			throw new exception\system('config_syntax_error');
		}
	
		if($value === 'false') {
			$value = false;
		} else if ($value === 'true') {
			$value = true;
		}
	
		//On split la clef sur les '.' (application.name...)
		//Et on parcours la clef pour créer les entrées dans params
		$currentEntry = $this->recursAddParam($this->params[$appName][$module], $key, $value);
	}
	
	private function parseValue($value) {
		$value = trim($value);
	
		$length = strlen($value);
	
		if(strpos($value, '[') === 0 && strrpos($value, ']') == $length - 1) {
			$value = substr($value, 1, -1);
				
			$value = explode(',', $value);
			foreach($value as &$one) {
				$one = trim($one);
			}
		}
	
		return $value;
	}
	
	/**
	 * @brief appelé par addParams pour ajouter des paramétres en recursif dans le tableau de configuration
	 * une fois tous les tests effectués
	 */
	private function recursAddParam (&$currentTabEntry, $key, $value) {
		$keySplit = explode('.', $key);
		$nbToken = count($keySplit);
	
		if (empty($currentTabEntry[$keySplit[0]])) {
			$currentTabEntry[$keySplit[0]] = array();
		}
		if (count($keySplit) > 1) {
			$pos = strpos($key, '.');
			$newKey = trim(substr($key, $pos + 1));
				
			$this->recursAddParam ($currentTabEntry[$keySplit[0]], $newKey, $value);
		} else {
			$currentTabEntry[$keySplit[0]] = $value;
		}
	
		return $currentTabEntry;
	}
	
	
}