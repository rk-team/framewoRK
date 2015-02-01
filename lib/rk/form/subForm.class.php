<?php

namespace rk\form;

class subForm extends \rk\form {

	protected
		$required = true,
		$templateName = 'subForm.table.php';
		
	
	public function setParam($name, $value) {
		if($name == 'required') {
			$this->required = $value;
		} else {
			parent::setParam($name, $value);
		}
	}
	
	
	protected function handleSave() {
		if($this->saveNeeded()) {
			parent::handleSave();
		}

	}
	
	public function validate(array $params) {		
		$this->isValid = true;
		
		$hasUserValues = false;
		
		// validate each widgets
		foreach($this->widgets as $oneWidget) {
			$widgetValue = null;
			if(array_key_exists($oneWidget->getBaseName(), $params)) {
				$widgetValue = $params[$oneWidget->getBaseName()];
			}
		
			
			if(!$this->required) {
				// subForm is not required : we check if at least one field has been set by user
				if(!$oneWidget instanceof \rk\form\widget\hidden) {
					if($widgetValue != '') {
						$hasUserValues = true;
					}
				}
			}
			
			$validWidget = $oneWidget->validate($widgetValue);
			if(!$validWidget) {
				$this->isValid = false;
			} else {
				$this->validatedValues[$oneWidget->getBaseName()] = $oneWidget->getFormattedValue();
			}
		}
		
		$this->hasUserValues = $hasUserValues;
		
		if(!$hasUserValues) {
			$this->isValid = true;
		}
		return $this->isValid;
	}
	
	public function saveNeeded() {
		if($this->hasUserValues) {
			return true;
		}
		
		return false;
	}
	
	
	public function removeWidgetsError() {
		foreach($this->widgets as $oneWidget) {
			$oneWidget->removeErrors();
		}
	}
	
	
// 	public function addWidgets() {
// 		parent::addWidgets($widgets);
		
// 		foreach($this->widgets as $one) {
			
// 		}
// 	}
	
// 	public function validate(array $params) {
		
// 		$this->isValid = true;
		
// 		// validate each widgets
// 		foreach($this->widgets as $oneWidget) {
			
// 			if(!$this->required) {
// 				$oneWidget->setParam('required', false);
// 			}
			
// 			$widgetValue = null;
// 			if(array_key_exists($oneWidget->getBaseName(), $params)) {
// 				$widgetValue = $params[$oneWidget->getBaseName()];
// 			}
		
// 			$validWidget = $oneWidget->validate($widgetValue);
// 			if(!$validWidget) {
// 				$this->isValid = false;
// 			} else {
// 				$this->validatedValues[$oneWidget->getBaseName()] = $oneWidget->getFormattedValue();
// 			}
// 		}
// 		return $this->isValid;
// 	}
}