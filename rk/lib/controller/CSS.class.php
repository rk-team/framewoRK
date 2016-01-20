<?php

namespace rk\controller;

class CSS extends \rk\controller {

	protected 
		$extension = 'css',
		$baseDir = 'web/css/',
		$dirsToScan = array('vendor', 'common');
	
	public function __construct($debug) {
		$app = \rk\manager::getInstance()->getRequestHandler()->getApplicationName();
		
		$this->dirsToScan[] = ('app/' . $app);
		
		parent::__construct($debug);
	}
	
	public function getCacheFilePath() {
		$app = \rk\manager::getInstance()->getRequestHandler()->getApplicationName();

		return \rk\manager::getCacheDir() . '/' . $app . '-' . $this->getMIN_FILE_NAME();
	}
	
	public function getContent() {
		$return = '';
			
		if (!$this->isDebug() && $this->minifier != 'none') {
			$return .= '<style type="text/css" media="screen">';
		}
		
		$return .= parent::getContent();
		
		if (!$this->isDebug() && $this->minifier != 'none') {
			$return .= '</style>';
		}
		
		return $return;
	}
	
	protected function buildOutputForDebug($filePath) {
		return '<link href="/css/' . $filePath . '" rel="stylesheet" media="all" type="text/css">' . "\n";;
	}
	
}
