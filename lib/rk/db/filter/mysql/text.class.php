<?php

namespace rk\db\filter\mysql;

use \rk\db\builder as builder;

class text extends \rk\db\filter\text {

	public function getWherePart($operator, \rk\db\table $table, \rk\db\criteria $criteria) {
		switch($operator) {
			case builder::OPERATOR_ILIKE:
				$clause = ' UPPER(' . $this->getCriteriaIdentifier($table, $criteria) . ') LIKE UPPER($$) ';
				return array($clause, $criteria->getValue());
			break;
			case builder::OPERATOR_NOTILIKE:
				$clause = ' (UPPER(' . $this->getCriteriaIdentifier($table, $criteria) . ') NOT LIKE UPPER($$) OR ' . $this->getCriteriaIdentifier($table, $criteria) . ' IS NULL)';
				return array($clause, $criteria->getValue());
			break;
			default:
				return parent::getWherePart($operator, $table, $criteria);
		}
	}

}