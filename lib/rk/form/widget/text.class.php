<?php

namespace rk\form\widget;

class text extends \rk\form\widget {

	public function getValue() {
		$value = parent::getValue();
		
		$value = htmlentities($value);
				
		return $value;
	}
	
	public function useLabelAsPlaceholder() {
		$this->setParam('placeholder', i18n($this->getParam('label')));
		$this->setParam('label', '');
	}
	
}