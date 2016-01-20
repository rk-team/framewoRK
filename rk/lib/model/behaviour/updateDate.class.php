<?php

namespace rk\model\behaviour;

class updateDate extends \rk\model\behaviour {
	
	protected $triggers = array('insert', 'update');
	
	public function getValue() {
		return date('Y-m-d H:i:s');
	}
}