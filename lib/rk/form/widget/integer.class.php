<?php

namespace rk\form\widget;

class integer extends \rk\form\widget\text {
	
	public function __construct($name, array $params = array()) {
		parent::__construct($name, $params);
		
		$this->templateName = 'text.php';
	}
	
	public function checkValidity($value) {
		$valid = parent::checkValidity($value);
		
		if($valid) {
			if($this->required && !is_numeric($value)) {
				$this->addError('form.error.invalid_format');
				return false;
			}
		}
		
		return $valid;
	}
	
}