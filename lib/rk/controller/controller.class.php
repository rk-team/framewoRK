<?php

namespace rk;

abstract class controller {

	protected 
		$filesToInclude = array(),
		$debug = false,
		$minifier,
		$path,
		$IGNORES_FOR_INCLUDE_DIR = array ('.', '..'),
		$MIN_FILE_NAME,
		$TMP_FILE_PATH,
		$TMP_FILE_NAME;

	// to overload : extension for generated files (ex: "js")
	protected $extension;
	
	// to overload : base directory for files to parse (ex: "web/js/")
	protected $baseDir;
	
	// to overload : array of directories to scan in base directory (ex: [rk, vendor])
	protected $dirsToScan;
	

	protected static $knownMinifiers = array('none', 'yui-compressor', 'minify');
	
	/**
	 * @desc build the HTML to be added in debug mode for all files
	 */
	protected abstract function buildOutputForDebug($filePath);
	
	function __construct($debug) {
		
		$this->minifier = \rk\manager::getConfigParam('project.minifier');
		if(!in_array($this->minifier, self::$knownMinifiers)) {
			throw new \rk\exception('unknown minifier');
		}
		
		$this->TMP_FILE_NAME = 'tmp.' . $this->extension;
		$this->TMP_FILE_PATH = \rk\manager::getCacheDir() . '/' . $this->TMP_FILE_NAME;
		$this->MIN_FILE_NAME = 'cache.min.' . $this->extension;
		
		$this->debug = $debug;
		$this->path = \rk\manager::getRootDir() . $this->baseDir;
		
		$this->includeDirectories();
	}
	
	public function getMIN_FILE_NAME() {
		return $this->MIN_FILE_NAME;
	}
	
	protected function isDebug() {
		return $this->debug;
	}
	
	protected function includeDirectories() {
		foreach($this->dirsToScan as $oneDir) {
			$this->includeFilesForDirectory($this->path . '/' . $oneDir, $this->filesToInclude);
		}
	}
	
	public function getCacheFilePath() {
		return \rk\manager::getCacheDir() . '/' . $this->MIN_FILE_NAME;
	}
		
	public function getContent() {
		
		if ($this->debug === false && $this->minifier != 'none') {			
			// we want a minified version of our files
			$cached = null;
			if(file_exists($this->getCacheFilePath())) {
				$cached = file_get_contents($this->getCacheFilePath());
			}
			if (is_null($cached)) {
				//Creation du cache
				$cache = $this->generateCache();
				$res = $cache;
			} else {
				//Chargement du chache
				$res = $cached;
			}
			
		} else {
			// we want to serve each file one by one
			$res = $this->getOutputForDebugMode();
		}

		return $res;
	}
	
	protected function getOutputForDebugMode() {
		$res = '';
		
		foreach($this->filesToInclude as $oneFile) {
			$filePath = str_replace($this->path, '', $oneFile);
			$res .= $this->buildOutputForDebug($filePath);
		}
		
		return $res;
	}
	
	public function deleteCacheFile () {
		if(file_exists($this->getCacheFilePath())) {
			unlink($this->getCacheFilePath());
		}
	}
	
	function generateCache() {

		$start = microtime(true);
		
		// construit le fichiers temporaires grace aux fichiers indiqués dans $this->filesToInclude
		if (file_exists($this->TMP_FILE_PATH)) {
			unlink($this->TMP_FILE_PATH);
		}
		foreach($this->filesToInclude as $oneFile) {
			\rk\helper\fileSystem::file_put_contents($this->TMP_FILE_PATH, '/** ' . $oneFile . '**/ ' . file_get_contents($oneFile), FILE_APPEND);
		}
		
		$phpCmd = \rk\manager::getConfigParam('cli.php_cmd');
		
		if($this->minifier == 'minify') {
			// launch the minify compressor to get a minified version of files
			$cmd = 'cd ' . \rk\manager::getRootDir() . '/lib/vendor/minify-2.1.7/min_extras/cli && ' . $phpCmd . ' minify.php -t ' . $this->extension . ' -o  "' . $this->getCacheFilePath() . '" "' . $this->TMP_FILE_PATH . '" && chmod 775 "' . $this->getCacheFilePath() . '"';			
		} elseif($this->minifier == 'yui-compressor') {
			// launch the yui compressor to get a minified version of files
			$cmd = 'cd ' . \rk\manager::getRessourcesDir() . '/binaries && java -jar yuicompressor-2.4.8.jar "' . $this->TMP_FILE_PATH . '" -o  "' . $this->getCacheFilePath() . '" && chmod 775 "' . $this->getCacheFilePath() . '"';
		}
		
		if(!empty($cmd )) {
			$output = array();
			$ret = exec($cmd, $output, $returnVar);
			
			if ($returnVar !== 0) {
				$out = '';
				foreach($output as $line) {
					$out .= '<br />' . $line;
				}
				ini_set('error_reporting', E_ALL);
				ini_set('display_errors', 'On');
				ini_set('html_errors', 'On');
				throw new \rk\exception('Compression error : Command was ' . $cmd . "\n" . 'Output was ' . $out);
			}
			
			$logs = array(
				'GENERATED' => $this->extension,
				'selfDuration'	=> microtime(true) - $start,
			);
			\rk\webLogger::add($logs, 'CACHE');
			
			return file_get_contents($this->getCacheFilePath());
		}
		
		return file_get_contents($this->TMP_FILE_PATH);
	}

	
	public function includeFilesForDirectory($oneDir, &$return) {
		$toScanDirs = array();

		if(is_dir($oneDir)) {
			$files = scandir($oneDir);
			foreach($files as $oneFile) {
				if(!in_array($oneFile, $this->IGNORES_FOR_INCLUDE_DIR)) {
					if(is_file($oneDir . '/' . $oneFile)) {
						// c'est un fichier, on l'ajoute direct s'il finit en .css
						if(strpos($oneFile, '.' . $this->extension) == strlen($oneFile) - (strlen($this->extension) + 1)) {
							$return[] = $oneDir . '/' . $oneFile;
						}
					} else {
						// c'est un dossier, on le scannera après
						$toScanDirs[] = $oneDir . '/' . $oneFile;
					}
				}
			}
	
			foreach($toScanDirs as $oneScanDir) {
				$this->includeFilesForDirectory($oneScanDir, $return);
			}
		}
	}
}
