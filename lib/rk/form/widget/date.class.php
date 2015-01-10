<?php

namespace rk\form\widget;

class date extends \rk\form\widget {
	
	public function setValue($value) {
		$this->value = \rk\date::get($value);
// 		var_dump($value);
// 		parent::setValue($value);
	}
	
}