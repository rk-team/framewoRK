<?php

namespace rk\db\builder;
	
class pgsql extends \rk\db\builder {
	
	
	protected function _buildSelect_SelectPartPKOnly(\rk\db\table $table, \rk\db\criteriaSet $criteriaSet = null, array $params = array()) {
		$select = ' DISTINCT ' . $table->getName() . '.' . $table->getModel()->getPK() . ' as  ' . $table->getModel()->getPK();
		
		if(!empty($params['orderColumn']) && !empty($params['orderSort'])) {
			$fieldName = $this->getFullFieldName($table, $params['orderColumn']);
			$select .= ', ' . $fieldName;
		}
		
		return $select  . "\n";
	}
	
	
	public function buildLimit(\rk\db\table $table, array $params = array()) {
		$return = '';
		
		if(!empty($params['limit'])) {
			if(!array_key_exists('offset', $params)) {
				$offset = 0;
			} else {
				$offset = $params['offset'];
			}
			
			$return = ' LIMIT ' . $params['limit'] . ' OFFSET ' . $offset;
		}
		
		return $return;
	}
	
	public function buildInsert(\rk\db\table $table, array $values) {
		$binds = array();
		$query = 'INSERT INTO ' . $table->getName() . ' (';
		
		
		foreach($values as $key => $oneValue) {
			// pg take default nextVal for primary if primary isn't mentionned
			if ($key != $table->getModel()->getPK()) {
				$query .= $key . ', ';
			}
		}
		
		$query = substr($query, 0, -2);	// remove extra ,
		
		$query .= ') VALUES (';
		
		
		foreach($values as $key => $oneValue) {
			if ($key != $table->getModel()->getPK()) {
				$query .= '$$, ';
				$binds[] = $oneValue;
			}
		}
		
		$query = substr($query, 0, -2); 	// remove extra ,
		$query .= ') RETURNING ' . $table->getModel()->getPK() . ' AS pk ';
		
		list($query, $binds) = $this->handleBinds($query, $binds);
		
		return array($query, $binds);
	}
	
	public function formatValuesForBuilder($value, $type) {
		$res = $value;
		
		if ($type == 'boolean') {
			$res = 'TRUE';
			if (empty($value)) {
				$res = 'FALSE';
			}
		}
		
		return $res;
	}
	
	public function formatValuesFromBuilder($value, $type) {
		if($type == 'date') {
			try {
				$value = \rk\date::createFromDB($value, false);
			} catch(\rk\exception\invalidFormat $e) {
				$value = null;
			}
			
		} elseif($type == 'datetime') {
			try {
				$value = \rk\date::createFromDB($value);
			} catch(\rk\exception\invalidFormat $e) {
				$value = null;
			}
		}
		
		return $value;
	}
}