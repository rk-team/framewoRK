<?php

namespace rk\form\widget;

class image extends \rk\form\widget\file {
	
	public function checkValidity($value) {
		
		$valid = parent::checkValidity($value);
		
		if ($valid && !empty($value['tmp_path'])) {
			try {
				$image = new \Imagick($this->getAbsolutePath());
			}
			catch (\ImagickException $e) {
				$valid = false;
				$this->addError('form.error.image.wrong_format');		
			}
		}
										
		return $valid;
	}
	
	protected function removeOldFiles() {
		\rk\helper\image::removeFiles($this->oldPath);
	}
}
