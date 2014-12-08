<?php

namespace rk\form\widget;

class text extends \rk\form\widget {

	public function getValue() {
		$value = parent::getValue();
		
		$value = htmlentities($value);
				
		return $value;
	}
}