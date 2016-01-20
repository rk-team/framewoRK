<?php

namespace rk\form\widget;

class file extends \rk\form\widget {
	
	/**
	 * @desc if the option destinationFolder is set, 
	 *			then the file will be move with his tmp_name into the folder describe
	 *	 		else the file will be move ine webUplaodDir
	 */
	
	protected
		$uploadInfo = array(),
		$oldPath;
	
	public function __construct($name, array $params = array()) {
		parent::__construct($name, $params);
		$this->oldPath = $this->value;
	}
	
	// no possible predefined value on input file
	public function getValueAttribute() {
		return '';
	}
	
	// we try to move the file to destinationFolder
	public function checkValidity($value) {
		
		$valid = false;
		
		if($this->required && empty($this->oldPath)) {
			// check required
			if(empty($value) || empty($value['tmp_name'])) {
				$this->addError('form.error.required');
				return $valid;
			}
		}
		
		//removing oldFile if updated
		if (!empty($value['tmp_name']) && !empty($this->oldPath)) {
			$this->removeOldFiles();
		}
		
		$this->uploadInfo = $value;
		$this->uploadInfo['final_path'] = $this->getRelativePath();
		
		$destFolder = $this->getParam('destinationFolder');
		
		if (!empty($value['tmp_name']) && !empty($value['error'])) {
			$this->addError('form.error.file.upload');
		}
		else if (!empty($value['tmp_name'])) {
			
			$absoluteDest = $this->getAbsolutePath();
			$absoluteFolder = dirname($absoluteDest);
			
			// check if target directory exists
			if(!file_exists($absoluteFolder)) {
				\rk\helper\fileSystem::mkdir($absoluteFolder);
			}
			
			// move file
			$valid = move_uploaded_file($value['tmp_name'], $absoluteDest);
			if (!$valid) {
				$this->addError('form.error.file.move_error');
			}
		} else {
			$valid = true;
		}
						
		return $valid;
	}

	/**
	 * @desc return information around the upload
	 * @return array 
	 * 	  'name' => user file name
	 *	  'type' => myme type
	 *	  'tmp_name' => path where the file was first uploaded
	 *	  'error' => error in the upload process
	 *	  'size' => file size
	 *	  'final_path' => path where the file is finally stored
	 */
	public function getUploadInfo() {
		return $this->uploadInfo;
	}
	
	public function getFormattedValue() {	
		return $this->getRelativePath();
	}
	
	public function getDisplayValue() {
		return $this->getRelativePath();
	}
	
	protected function getRelativePath() {
		
		$destFolder = $this->getParam('destinationFolder');
		if (!empty($destFolder)) {
			$relativePath = $this->getParam('destinationFolder') . '/' . basename($this->value['tmp_name']);
		} else {
			$relativePath = \rk\manager::getWebUploadDirForApp() . basename($this->value['tmp_name']);
		}
		
		return $relativePath;
	}
	
	protected function getAbsolutePath() {
		return \rk\manager::getRootDir() . $this->getRelativePath();
	}
	
	protected function removeOldFiles() {
		unlink($this->oldPath);
	}
}
