<?php

namespace rk\db\filter;

use \rk\db\builder as builder;

class boolean extends \rk\db\filter {
	
	protected
		$defaultOperator = builder::OPERATOR_EQUAL,
		$allowedOperators = array(
			builder::OPERATOR_EQUAL, 
			builder::OPERATOR_NOTEQUAL
		);
		
		public function getWherePart($operator, \rk\db\table $table, \rk\db\criteria $criteria) {
					
			list($clause, $value) = parent::getWherePart($operator, $table, $criteria);
			
			if (empty($value)) {
				$value = 'FALSE';
			} else {
				$value = 'TRUE';
			}			
			
			return array($clause, $value);
		}
}