<?php

namespace rk\form\widget;

class checkbox extends \rk\form\widget {
	
	
	public function getParamsForTpl() {
		$tplParams = parent::getParamsForTpl();
		
		if($this->value) {
			$tplParams['checkedAttribute'] = ' checked="checked" ';
		} else {
			$tplParams['checkedAttribute'] = '';
		}
		
		return $tplParams;
	}

	public function getFormattedValue() {
		if(empty($this->value)) {
			return false;
		}
		return true;
	}
	
	public function checkValidity($value) {
		return true;
	}
}