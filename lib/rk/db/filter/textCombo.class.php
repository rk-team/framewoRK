<?php

namespace rk\db\filter;

class textCombo extends \rk\db\filter\text {
	
	public function __construct($fieldIdentifier, array $params = array()) {
		$this->fieldIdentifier = $fieldIdentifier;
		$this->params = $params;

		if(empty($this->params['basedOn'])) {
			throw new \rk\exception('no basedOn given for combo');
		}
		
		$comboType = false;
		foreach($this->params['basedOn'] as $one) {
			if(!$one instanceof \rk\db\filter\text) {
				throw new \rk\exception('all basedOn filters must be instances of \rk\db\filter\text');
			}
		}
	}
	
	public function getWherePart($operator, \rk\db\table $table, \rk\db\criteria $criteria) {
		$clause = '';
		$values = array();
		
		foreach($this->params['basedOn'] as $one) {
			$crit = new \rk\db\criteria($one->getFieldIdentifier(), $criteria->getValue(), $operator);
			list($oneClause, $oneValue) = $one->getWherePart($operator, $table, $crit);
			if(!empty($clause)) {
				if($operator == \rk\db\builder::OPERATOR_EQUAL
				|| $operator == \rk\db\builder::OPERATOR_LIKE
				|| $operator == \rk\db\builder::OPERATOR_ILIKE) {
					$clause .= ' OR ';
				} else {
					$clause .= ' AND ';
				}
			}
			$clause .= $oneClause;
			$values[] = $oneValue;
		}		
		
		return array($clause, $values);
	}
	
	
}