<?php

namespace rk\db\filter\mysql;

use \rk\db\builder as builder;

class boolean extends \rk\db\filter\boolean {

	public function getWherePart($operator, \rk\db\table $table, \rk\db\criteria $criteria) {
				
		list($clause, $value) = parent::getWherePart($operator, $table, $criteria);
		
		if (empty($value)) {
			$value = 0;
		} else {
			$value = 1;
		}			
		
		return array($clause, $value);
	}

}