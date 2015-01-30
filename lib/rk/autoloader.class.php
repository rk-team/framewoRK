<?php

namespace rk;

class autoloader {
	
	const CACHE_NOT_FOUND = 1;

	private static
		$foldersToParse = array('../../app', '../../lib'),
		$includeStringSuffix = array('.class.php'),
		$cacheRepPath = 'cache/',
		$cacheFileName = 'autoload.cache',
		$classesPath = array(),
		$alreadyRebuilt = false;
	
	/**
	 * @brief Lancement de l'autoloader
	 */
	public static function init($cacheRepPath = 'cache/') {
		spl_autoload_register('\rk\autoloader::__autoload');
		
		self::$cacheRepPath = $cacheRepPath;
		self::setClassesPath();
	}
	
	/**
	 * @brief autoloader maison
	 */
	public static function __autoload($name) {
		$found = false;
		if(!empty(self::$classesPath[$name])) {
			$found = true;
		} else {
			if(class_exists('\rk\manager', false) && \rk\manager::hasInstance() && \rk\manager::getInstance()->isDevMode() && !self::$alreadyRebuilt) {
				// try to rebuild cache if class not found AND we are in dev mode AND cache was not rebuilt already
				self::$alreadyRebuilt = true;
				self::rebuildCache();
				if(!empty(self::$classesPath[$name])) {
					$found = true;
				}
			}
		}
		
		if(!$found) {
// 			throw new \Exception('autoload error : class ' . $name . ' not found');
		} else {
			include (__DIR__ . '/../../' . self::$classesPath[$name]);
		}
	}
	
	/**
	 * @brief retourne le path vers le ficher de cache
	 */
	public static function getCacheFileName() {
		$cacheFileName = self::$cacheRepPath . '/' . self::$cacheFileName;
		return $cacheFileName;
	}
	
	public static function rebuildCache() {
		spl_autoload_register('\rk\autoloader::__autoload');
		
		self::makeCache();
		self::$classesPath = unserialize(file_get_contents(self::getCacheFileName()));
		
		spl_autoload_register('\rk\autoloader::__autoload');
	}
	
	/** 
	 * @brief retourne le tableau de correspondances classe/fichier
	 */
	public static function setClassesPath() {
		
		if (!file_exists(self::getCacheFileName())) {
			self::makeCache();
		}
		
		self::$classesPath = unserialize(file_get_contents(self::getCacheFileName()));
	}
	
	/**
	 * @brief parse tous les dossiers $foldersToParse et ajoute les classes trouvés à un tableau stocké dans $cachePath en sérialisé
	 */
	public static function makeCache() {
		$toLoadFiles = array();
		self::loadFolders(self::$foldersToParse, $toLoadFiles);

		$toCache = array();
		foreach($toLoadFiles as $oneToLoad) {
			$classes = self::getDefinedClassesForFile($oneToLoad);
			foreach($classes as $oneClass) {
				$toCache[$oneClass] = str_replace(__DIR__ .  '/../../', '', $oneToLoad);
			}
		}
		
		file_put_contents(self::getCacheFileName(), serialize($toCache));
		chmod(self::getCacheFileName(), 0775);
	}
	
	/**
	 * @brief cherche les classes définies dans le fichier donné
	 * @param string $filePath
	 * @throws \Exception
	 * @return Array
	 */
	public static function getDefinedClassesForFile($filePath) {
		$return = array();
			
		$contents = file_get_contents($filePath);
		$matches = array();
		
		if(preg_match_all('/namespace ([a-zA-Z-_\\\]*);/', $contents, $matches, PREG_SET_ORDER)) {
			if(count($matches) > 1) {
				throw new \Exception('more than one namespace defined in file ' . $filePath);
			}
			$namespace = $matches[0][1];
		} else {
			throw new \Exception('no namespace defined in file ' . $filePath);
		}
			
		// on récupère toutes les classes définies dans le fichier
		if(preg_match_all('/class ([a-zA-Z-_0-9]*) /', $contents, $matches, PREG_SET_ORDER)) {
			foreach($matches as $oneMatch) {
				$return[] = $namespace . '\\' . $oneMatch[1];
			}
		}
		
		return $return;
	}
	
	
	/**
	 * @brief cherche en récursif tous les fichiers correspondant à $includeStringSuffix dans les dossiers donnés
	 * @param unknown_type $foldersToScan
	 * @param unknown_type $toLoadFiles
	 */
	private static function loadFolders($foldersToScan, &$toLoadFiles) {
		if(!is_array($foldersToScan)) {
			$foldersToScan = array($foldersToScan);
		}
	
		foreach($foldersToScan as $oneFolder) {
			$fullFolderPath = __DIR__ . '/' . $oneFolder;
			$files = scandir($fullFolderPath);
			foreach($files as $oneFile) {
				if($oneFile != '.' && $oneFile != '..') {
					$fullFilePath = $fullFolderPath . '/' . $oneFile;
					if(is_file($fullFilePath)) {
						foreach(self::$includeStringSuffix as $oneStringSuffix) {
							$suffixLen = strlen($oneFile) - strlen($oneStringSuffix);
							if ($suffixLen > 0) {
								$oneFileSuffix = substr($oneFile, $suffixLen);
								if($oneFileSuffix == $oneStringSuffix) {
									$toLoadFiles[] = $fullFilePath;
									break;
								}
							}
						}
					} elseif(is_dir($fullFilePath)) {
						self::loadFolders($oneFolder . '/' . $oneFile, $toLoadFiles);
					}
				}
			}
		}
	}
	
}

