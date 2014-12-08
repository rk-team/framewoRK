<?php

namespace rk\model\behaviour;

class creationDate extends \rk\model\behaviour {
	
	protected $triggers = array('insert');
	
	public function getValue() {
		return date('Y-m-d H:i:s');
	}
}