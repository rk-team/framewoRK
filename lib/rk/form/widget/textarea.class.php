<?php

namespace rk\form\widget;

class textarea extends \rk\form\widget {

	
	public function getValue() {
		$value = parent::getValue();
		
		$value = str_replace('<br />', "\n", $value);
		
		return $value;
	}
	
	public function getFormattedValue() {
		$value = parent::getFormattedValue();
		
		$value = str_replace("\n", '<br />', $this->value);
		
		return $value;
	}

	
}