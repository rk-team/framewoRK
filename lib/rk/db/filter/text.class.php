<?php

namespace rk\db\filter;

use \rk\db\builder as builder;

class text extends \rk\db\filter {
	
	protected
		$defaultOperator = builder::OPERATOR_EQUAL,
		$allowedOperators = array(
			builder::OPERATOR_EQUAL, 
			builder::OPERATOR_NOTEQUAL,
			builder::OPERATOR_LIKE,
			builder::OPERATOR_NOTLIKE,				
			builder::OPERATOR_ILIKE,				
			builder::OPERATOR_NOTILIKE,	
		);
	
	public function getWherePart($operator, \rk\db\table $table, \rk\db\criteria $criteria) {
		switch($operator) {
			case builder::OPERATOR_NOTEQUAL:
			case builder::OPERATOR_NOTLIKE:
			case builder::OPERATOR_NOTILIKE:
				list($clause, $value) = parent::getWherePart($operator, $table, $criteria);

				$clause = '(' . $clause . ' OR ' . $this->getCriteriaIdentifier($table, $criteria) . ' IS NULL)';
				return array($clause, $value);
			default:
				return parent::getWherePart($operator, $table, $criteria);
		}
	}
}