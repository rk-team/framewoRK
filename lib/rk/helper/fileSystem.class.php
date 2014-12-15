<?php

namespace rk\helper;

class fileSystem {
	
	public static function mkdir($path) {
		if(!file_exists($path)) {
			mkdir($path, 0755, true);
		}
	}
		
	/**
	 * @desc recursive scandirs
	 * @param path string :
	 * @param params array : params (optionnel)
	 * 		$params = array(
	 * 			ignore : file name ignored
	 * 			minDepth : array : files under a defined depth will be ignored
	 * 			maxDepth : array : files over a defined depth will be ignored
	 * 			recursive bool : wether to recurs or not 
	 * 		)
	 * @return file list with filename in non recursive mode, or with full path if recursiv
	 */
	public static function scandir($path, array $params = array()) {
	
		if (empty($params['ignore'])) {
			$params['ignore'] = array();
		}
		$params['ignore'][] = '.';
		$params['ignore'][] = '..';
		
		if (empty($params['recursive'])) {
			$params['recursive'] = false;
		}
		
		if (strrpos($path, '/') == (strlen($path) - 1)) {
			$path = substr($path, 0, -1);
		}
	
		return self::recursScanDir($path, $params);
	}
	
	/**
	 * used by scandir
	 */
	protected static function recursScanDir($path, $params, $curDepth = 0) {
		
		$dh = opendir($path);
		
		if(!$dh) {
			return array();
		}
		
		$nextDepth = $curDepth + 1;
		$return = array();
	
		while (false !== ($file = readdir($dh))) {
	
			if(!in_array($file, $params['ignore'])) {
				
				$fullPath = $path . '/' . $file;
				
				$resPath = $file;
				if (!empty($params['recursive'])) {
					$resPath = $fullPath;
				}				
				
				if (empty($params['maxDepth']) || ($curDepth <= $params['maxDepth'])) {
						
					if (is_dir($fullPath) && !empty($params['recursive'])) {
						$return = array_merge($return, self::recursScanDir($fullPath, $params, $nextDepth));
					} 
					
					if (empty($params['minDepth']) || ($curDepth >= $params['minDepth'])) {
						$return[] = $resPath;
					}
				}
			}
		}
	
		closedir($dh);
		return $return;
	}
		
	/**
	 * return absolute path with the relative file path
	 */
	public static function getAbsolutePath($relativePath) {	
		return \rk\manager::getRootDir() . $relativePath;
	}
	
	/**
	 * return uri for relative path
	 */
	public static function getFileURI($relativePath) {
		$pos = strpos($relativePath, 'web');
		if ($pos !== 0) {
			throw new \rk\exception('invalid path', array('relativePath' => $relativePath));
		}
		
		return substr($relativePath, 3);
	}
}