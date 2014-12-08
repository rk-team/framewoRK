<?php

namespace rk\controller;

class JS extends \rk\controller {

	protected 
		$extension = 'js',
		$baseDir = 'web/js/',
		$dirsToScan = array('vendor', 'rk', 'user');

	
	public function getContent() {
		$return = '';
		if (!$this->isDebug()) {
			$return .= '<script type="text/javascript">';
		}
		
		$return .= parent::getContent();
		
		if (!$this->isDebug()) {
			$return .= '</script>';
		}	
		
		return $return;
	}
	
	/**
	 * @desc extended js class has to be included after the parent class
	 * @see \rk\controller::includeDirectories()
	 */
	protected function includeDirectories() {
		
		$orderedFiles = array();
		
		//retrieve all files to include
		foreach($this->dirsToScan as $oneDir) {
			$allFiles = array();
			$this->includeFilesForDirectory($this->path . '/' . $oneDir, $allFiles);
			
			if($oneDir != 'vendor') {
				$seenClass = array();
				$nbIteration = 0;
				
				//Class cannot be child of 6 consecutive parent (arbitrary fixed)
				while (!empty($allFiles) && ($nbIteration < 6)) {
					$nbIteration++;
					
					foreach ($allFiles as $oneKey => $oneFile) {
						
						//Looking for parent and class in files
						$content = file_get_contents($oneFile);				
						preg_match('/(rk\.[\w\.]*)([ ]*=[ ]*)([\w\.]*)\.extend\({/', $content, $matches);
						if (empty($matches)) {
							preg_match('/(user\.[\w\.]*)([ ]*=[ ]*)([\w\.]*)\.extend\({/', $content, $matches);
						}
		
						//No class creation, appending file and continuing
						if (empty($matches)) {
							$orderedFiles[] = $oneFile;
							unset($allFiles[$oneKey]);
							
							continue;
						}
		
						$class = $matches[1];
						$parent = $matches[3];
										
						//Extended rk.base has not to be specificaly ordered
						if ($parent == 'rk.base') {
							$orderedFiles[] = $oneFile;
							unset($allFiles[$oneKey]);
							
							$seenClass[] = $class;
							continue;
						}
						
						//Parent is already in seenClass, we happend this new class
						//Else we keep the file in allFiles, and it will be included in next iteration
						if (in_array($parent, $seenClass)) {
							$orderedFiles[] = $oneFile;
							unset($allFiles[$oneKey]);
							
							$seenClass[] = $class;
							continue;
						}
					}
				}
						
				if (!empty($allFiles)) {
					throw new \rk\exception('cannot order JS class', array('parent_not_found' => $allFiles));
				}
			} else {
				foreach ($allFiles as $oneKey => $oneFile) {
					$orderedFiles[] = $oneFile;
				}
			}
			
			
		}
		
		
		$this->filesToInclude = $orderedFiles;
	}
	
	protected function buildOutputForDebug($filePath) {
		return '<script type="text/javascript" src="/js/' . $filePath . '" ></script>' . "\n";
	}
	
	protected function getOutputForDebugMode() {
		$return = parent::getOutputForDebugMode();
		$return .= '<script type="text/javascript">' . $this->getFormattedTranslations() . '</script>';
		
		return $return;
	}
		
	function generateCache() {
		parent::generateCache();
		
		// add transalations to cache file
		$data = self::getFormattedTranslations();
		file_put_contents(self::getCacheFilePath(), "\n" . $data, FILE_APPEND);
		
		return file_get_contents($this->getCacheFilePath());
	}

	protected function getFormattedTranslations() {
		$translations = \rk\i18n::getCatalog('js');
		
		$return = '';
		foreach($translations as $language => $data) {
			$return .= 'rk.util.i18n.translations.' . $language . ' = {};' . "\n";
			foreach($data as $key => $value) {
				$key = substr($key, 3);	// remove the heading "js." (like in "js.my_key")
				$return .= 'rk.util.i18n.translations.' . $language . '[\'' . $key . '\'] = \'' . str_replace('\'', '\\\'', $value) . '\';' . "\n";
			}
		}
		
// 		$return .= 'rk.util.i18n.culture = \'fr_FR\';' . "\n";
		$return .= 'rk.util.i18n.language = \'' . \rk\manager::getUser()->getLanguage() . '\';' . "\n";
		
		return $return;
	}
}
