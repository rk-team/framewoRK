<?php

namespace rk\form;

class subForm extends \rk\form {

	protected
		$required = true,
		$isSubForm = true,
		$templateName = 'subForm.table.php';
		
	
	public function setParam($name, $value) {
		if($name == 'required') {
			$this->required = $value;
		} else {
			parent::setParam($name, $value);
		}
	}
	
	
// 	public function addWidgets() {
// 		parent::addWidgets($widgets);
		
// 		foreach($this->widgets as $one) {
			
// 		}
// 	}
	
	public function validate(array $params) {
		
		$this->isValid = true;
		
		// validate each widgets
		foreach($this->widgets as $oneWidget) {
			
			if(!$this->required) {
				$oneWidget->setParam('required', false);
			}
			
			$widgetValue = null;
			if(array_key_exists($oneWidget->getBaseName(), $params)) {
				$widgetValue = $params[$oneWidget->getBaseName()];
			}
		
			$validWidget = $oneWidget->validate($widgetValue);
			if(!$validWidget) {
				$this->isValid = false;
			} else {
				$this->validatedValues[$oneWidget->getBaseName()] = $oneWidget->getFormattedValue();
			}
		}
		return $this->isValid;
	}
}