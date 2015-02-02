<?php 

namespace rk;


/**
 * Global cache process :
 * 		- translations are edited in the \rk\i18n::$CATALOGS_PATH with the following base format :
 				<source key="app.welcome">
					<trans lang="fr">Bienvenue</trans>
					<trans lang="en">Welcome</trans>
				</source>
			Possible parameters are described in the \rk\i18n::get() function
		- when the \rk\i18n::makeCache function is called, all translations files are splitted into cache catalogs
			One cache file is created for each language and catalog
				EX : you have form.i18n.xml and app.i18n.xml with fr and en translations
				Once \rk\i18n::makeCache is called, you will have in your \rk\i18n::$CACHE_PATH folder the following files :
					en/form.cache
					en/app.cache
					fr/form.cache
					fr/app.cache
		- when you call the get function, the corresponding catalog will be fully loaded into memory in \rk\i18n::$translations
 *
 */
class i18n {
	
	private static
		$CATALOGS_PATH = 'ressources/i18n/',
		$CACHE_PATH = 'cache/i18n/',
		$translations = array();
	
	/**
	 * returns the translation for given key
	 * @param string $key
	 * @param array $params optionnal. May contain :
	 * 			- vars => array of variables to be given to translation. 
	 * 					EX : 
		 				<source key="app.welcome">
							<trans lang="fr">Bienvenue %name%</trans>
							<trans lang="en">Welcome %name%</trans>
						</source>
	 * 				If you give $params = array('vars' => array('name' => 'Foo Bar')) you will get "Welcome Foo Bar" in english
	 * 			- count => specify your count for a singular/plural translation 
	 * 					EX : 
						<source key="app.contact">
							<trans lang="fr">
								<singular>Contact</singular>
								<plural>Contacts</plural>
							</trans>
							<trans lang="en">
								<singular>Contact</singular>
								<plural>Contacts</plural>
							</trans>	
						</source>
						
	 *				If you give $params = array('count' => 1) you will get "Contact" in english
	 *				with $params = array('count' => 10) you will get "Contacts" in english
	 *			- language => used to force language to given value
	 *					Can be used to get an english translation even if user has a french culture
	 *
	 *		All these parameters may be combined
	 * 		
	 */
	public static function get($key, array $replacements = array(), array $params = array()) {
		
		if (empty($key)) {
			return '';
		}
		
		if(empty($params['language'])) {
			$user = \rk\manager::getUser();
			$language = $user->getLanguage();
		} else {
			$language = $params['language'];
		}
		
		
		if(array_key_exists('count', $params)) {
			$count = $params['count'];
		} else {
			$count = 1;
		}


		$pos = strpos($key, '.');	// i18n keys are formatted like : <database>.<table>.<field>, or <table>.<field>
		$catalog = substr($key, 0, $pos);
		
		// try to find a translation with given params
		$trans = self::getTranslation($language, $catalog, $key, $count);
		
		
			
		if($trans === false || $trans == $key) {
			$trace = debug_backtrace();
			$callers = array();
			$max = 3;
			if(count($trace) < $max) {
				$max = count($trace);
			}
			for($i = 0; $i < $max; $i++) {
				$callers[] = str_replace(\rk\manager::getRootDir(), '', $trace[$i]['file']) . ' (line ' . $trace[$i]['line'] . ')';
			}

			\rk\webLogger::add(array('key' => $key, 'language' => $language), 'I18N');
		}
		
		// no translation found in given language. We try to find one in the project default language
		if(($trans === false) && !\rk\manager::isDevMode()) {
			$secondChanceLanguage = \rk\manager::getConfigParam('project.default_language');
			if($secondChanceLanguage != $language) {
				$trans = self::getTranslation($secondChanceLanguage, $catalog, $key, $count);
			}
		}
		
		if($trans !== false) {
			// a translation was found, we handle 'vars' replacement
			if(!empty($replacements)) {
				foreach($replacements as $paramName => $paramValue) {
					$trans = str_replace('%' . $paramName . '%', $paramValue, $trans);
				}
			}
			return $trans;
		}
		
		if (!empty($params['htmlentities'])) {
			$key = htmlentities($key, ENT_QUOTES);
		}
		
	
		// no translation was found : we return the key
		return $key;
	}
	
	/**
	 * get a translation from self::$translations. If catalog is not loaded already, it will be loaded from its cache file 
	 * @param string $language
	 * @param string $catalog
	 * @param string $key
	 * @param integer $count
	 */
	private static function getTranslation($language, $catalog, $key, $count) {
		self::loadCatalogFromCache($language, $catalog);
		
		$trans = false;
		if(!empty(self::$translations[$catalog][$language]) && array_key_exists($key, self::$translations[$catalog][$language])) {
			if (is_array(self::$translations[$catalog][$language][$key])) {
				if ($count > 1) {
					$trans = self::$translations[$catalog][$language][$key]['plural'];
				} else {
					$trans = self::$translations[$catalog][$language][$key]['singular'];
				}
			} else {
				$trans = self::$translations[$catalog][$language][$key];
			}
		}
		
		return $trans;
	}
	
	/**
	 * Loads a translation catalog from its cache file into memory
	 * @param string $languageName
	 * @param string $catalogName
	 */
	private static function loadCatalogFromCache($languageName, $catalogName) {
		if(empty(self::$translations[$catalogName])) {
			$path = self::getCatalogPath($languageName, $catalogName);
			if(file_exists($path)) {
				self::$translations[$catalogName][$languageName] = unserialize(file_get_contents($path));
			}
		}
	} 
	
	public static function getCatalog($catalogName) {
		$nbDot = substr_count($catalogName, '.') - 1;
		$catalogPath = preg_replace('/\./', '/\//', $catalogName, $nbDot);
		
		$return = array();
		
		$path = \rk\manager::getRootDir() . self::$CACHE_PATH;
		$fullFilePath = '';
		
		$files = \rk\helper\fileSystem::scandir($path);
		foreach($files as $oneFile) {
			
			
			$fullPath = $path . '/' . $oneFile . '/' . $catalogPath . '.cache';
			if(file_exists($fullPath)) {
				$return[$oneFile] = unserialize(file_get_contents($fullPath));
			}
		}

		return $return;
	}
	
	
	/**
	 * Gets the cache path for given language and catalog
	 * 	If no catalogName is given, it will return the fodler path for given language 
	 * @param string $languageName
	 * @param string $catalogName optionnal
	 */
	private static function getCatalogPath($languageName, $catalogName = false) {
		$return = \rk\manager::getRootDir() . self::$CACHE_PATH . '/' . $languageName;
		if(!empty($catalogName)) {
			$return .= '/' . $catalogName . '.cache';
		}
		
		return $return;
	}
	
	/**
	 * Build cache files from all i18n files found in self::$CATALOGS_PATH folder
	 * @throws \rk\exception
	 */
	public static function makeCache() {
		$start = microtime(true);
		//Start building cache with framework extendable values
		self::_makeCache(self::$CATALOGS_PATH . '/rk/');
		//Then extend with user ones
		self::_makeCache(self::$CATALOGS_PATH . '/user/', true);
		
		$requestParams = array(
			'GENERATED' => 'i18n',
			'selfDuration' => microtime(true) - $start,
		);
		\rk\webLogger::add($requestParams, 'CACHE');
	}
	
	
	public static function _makeCache($path, $extend = false, $prefix = '') {
		$translationSet = array();
		
		$basePath = \rk\manager::getRootDir() . $path;
		$files = \rk\helper\fileSystem::scandir($basePath);
			
		foreach($files as $oneFile) {
			$fullPath = $basePath . '/' . $oneFile;
			if (is_dir($fullPath)) {
				self::_makeCache($path . '/' . $oneFile, $extend, $oneFile . '.');
			} elseif(strpos($oneFile, '.i18n.xml') !== false) {
				$catalog = str_replace('.i18n.xml', '', $oneFile);
				
				$doc = new \DOMDocument();
				$load = $doc->loadXML(file_get_contents($fullPath));
				if(!$load) {
					throw new \rk\exception('cant parse translations catalog', array('catalog' => $catalog));
				}
				
				$sources = $doc->getElementsByTagName('source');
				foreach($sources as $oneSource) {
					$key = $prefix . $oneSource->getAttribute('key');
					$trans = $oneSource->getElementsByTagName('trans');
					foreach($trans as $oneTrans) {
						$singularNode = $oneTrans->getElementsByTagName('singular');
						$pluralNode = $oneTrans->getElementsByTagName('plural');
						if (($singularNode->length > 0) && ($pluralNode->length > 0)) {
				
							$out = new \DOMDocument();
							foreach($singularNode->item(0)->childNodes as $oneChild) {
								$out->appendChild($out->importNode($oneChild,true));
							}
							$value = $out->saveHTML();
							$translationSet[$oneTrans->getAttribute('lang')][$catalog][$key]['singular'] = trim($value);
				
							$out = new DOMDocument();
							foreach($pluralNode->item(0)->childNodes as $oneChild) {
								$out->appendChild($out->importNode($oneChild,true));
							}
							$value = $out->saveHTML();
							$translationSet[$oneTrans->getAttribute('lang')][$catalog][$key]['plural'] = trim(html_entity_decode($value, ENT_QUOTES));
						} else {
							if($oneTrans->hasChildNodes()) {
								$out = new \DOMDocument();
								foreach($oneTrans->childNodes as $oneChild) {
									$out->appendChild($out->importNode($oneChild,true));
								}
								$value = $out->saveHTML();
							} else {
								// no translation found in XML
								$value = '';
							}
							$translationSet[$oneTrans->getAttribute('lang')][$catalog][$key] = trim(html_entity_decode($value, ENT_QUOTES));
						}
					}
				}
			}
				
		}
		
		// we create one cache file for each language and catalog		
		foreach($translationSet as $languageName => $catalogSet) {
			foreach($catalogSet as $catalogName => $catalogTranslations) {
				// ensure directory exists
				$destination = self::getCatalogPath($languageName);
				\rk\helper\fileSystem::mkdir($destination);
				
				// write cache file
				$destination = self::getCatalogPath($languageName, $prefix . $catalogName);
				
				// extend previous value if needed
				if ($extend && file_exists($destination)) {
					$previousValues = unserialize(file_get_contents($destination));
					$catalogTranslations = array_merge($previousValues, $catalogTranslations);
				}
				
				\rk\helper\fileSystem::file_put_contents($destination, serialize($catalogTranslations));
			}
		}
	}
	
	
	
	public static function getORMKey($connectorName, $tableName, $fieldName = null) {
		$return = '';
		
		if($connectorName != 'default') {
			$return = $connectorName . '.';
		}
		
		$return .= $tableName;
		
		if(!empty($fieldName)) {
			$return .= '.' . $fieldName;
		}
		
		return $return;
	}
	
	
	
	public static function getPreferedLanguage(array $i18nData) {
		$lang = \rk\manager::getUser()->getLanguage();
		if(isset($i18nData[$lang])) {
			return $lang;
		}
		
		$lang = \rk\manager::getConfigParam('project.default_language');
		if(isset($i18nData[$lang])) {
			return $lang;
		}
		
		$keys = array_keys($i18nData);
		$lang = $keys[0];
		if(isset($i18nData[$lang])) {
			return $lang;
		}
		
		return false;
	}
	
	
	public static function getFieldInPreferedLanguage(array $i18nData, $fieldName) {
		$lang = self::getPreferedLanguage($i18nData);
		
		if(!empty($lang) && isset($i18nData[$lang][$fieldName])) {
			if($lang == \rk\manager::getUser()->getLanguage()) {
				return $i18nData[$lang][$fieldName];
			} else {
				return '(' . $lang . ') ' . $i18nData[$lang][$fieldName];
			}
		}
		
		return null;
	}
	
	
	
	
}