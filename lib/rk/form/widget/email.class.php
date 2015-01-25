<?php

namespace rk\form\widget;

class email extends \rk\form\widget\text {
	
	public function checkValidity($value) {
		$valid = parent::checkValidity($value);
		
		if($valid) {
			if($this->required) {
				
				$checkEmailFormat = filter_var($value, FILTER_VALIDATE_EMAIL);
				if($checkEmailFormat === false) {
					$this->addError('form.error.invalid_format');
					return false;
				}
			}
		}
		
		return $valid;
	}
	
}