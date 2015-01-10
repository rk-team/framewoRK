<?php

namespace rk\db;

use \rk\db\builder as builder;

abstract class filter {
	
	protected
		$modelName,
		$attributeName,
		$defaultOperator,
		$allowedOperators = array(),
		$fieldIdentifier,
		$params = array();
	
	public function __construct($fieldIdentifier, array $params) {
		$this->fieldIdentifier = $fieldIdentifier;
		$this->params = $params;
		
		if(empty($params['modelName'])) {
			throw new \rk\exception('missing modelName');
		}
		$this->modelName = $params['modelName'];
		if(empty($params['attributeName'])) {
			throw new \rk\exception('missing attributeName');
		}
		$this->attributeName = $params['attributeName'];
	}
	
	public function getParam($name) {
		if(array_key_exists($name, $this->params)) {
			return $this->params[$name];
		}
		
		return null;
	}
	
	
	public function getFieldIdentifier() {
		return $this->fieldIdentifier;
	}
		
	public function getModelName() {
		return $this->modelName;
	}
		
	public function getAttributeName() {
		return $this->attributeName;
	}	
	
	protected function getCriteriaIdentifier($tableName, \rk\db\criteria $criteria) {
		if($tableName instanceof \rk\db\table) {
			$tableName = $tableName->getName();
		}
		
		if(strpos($criteria->getName(), '.') !== false) {
			return $criteria->getName();
		}
		
		return $tableName . '.' . $criteria->getName();
	}
	
	
	public function createWherePart(\rk\db\table $table, \rk\db\criteria $criteria) {
		$operator = $this->getOperator($criteria);
		if(!empty($this->getParam('referenceName'))) {
			$ref = $table->getReference($this->getParam('referenceName'));
			if(!empty($ref) && $ref->hasMany()) {
				// filter is based on a hasMany reference, so we want a exists/not exists clause to be built
				$onHasManyRef = true;
				$originalOperator = $operator;
								
				
				// if criteria is "field != value", we want : "not exists(select ... from ... where field = value...)"
				// so we have to "invert" the operator
				switch($this->getOperator($criteria)) {
					case builder::OPERATOR_NOTEQUAL:
						$operator = builder::OPERATOR_EQUAL;
					break;
					case builder::OPERATOR_NOTLIKE:
						$operator = builder::OPERATOR_LIKE;
					break;
					case builder::OPERATOR_NOTILIKE:
						$operator = builder::OPERATOR_ILIKE;
					break;
				}
			}
		}
		
		
		list($clause, $value) = $this->getWherePart($operator, $table, $criteria);
		
		if(!empty($onHasManyRef)) {
			if($originalOperator == builder::OPERATOR_NOTEQUAL || $originalOperator == builder::OPERATOR_NOTLIKE || $originalOperator == builder::OPERATOR_NOTILIKE) {
				$exists = ' NOT EXISTS ';
			} else {
				$exists = ' EXISTS ';
			}
			
			$referencedTableName = $ref->getReferencedModel()->getTableName();
			
			$clause = $exists . ' (SELECT ' . $ref->getReferencingField() . ' 
			FROM ' . $referencedTableName . ' 
			WHERE ' . $clause . ' 
				AND ' . $referencedTableName . '.' .  $ref->getReferencedField() . ' = ' . $table->getName() . '.' . $ref->getReferencingField() . ')';
		}
		
		return array($clause, $value);
	}
	
	public function getWherePart($operator, \rk\db\table $table, \rk\db\criteria $criteria) {
		$value = $criteria->getValue();
		$clause = '';
					
		switch($operator) {
			case builder::OPERATOR_EQUAL:
				if(is_array($value)) {
					$operator = ' IN ';
					$clause = ' ' . $this->getCriteriaIdentifier($table, $criteria) . ' ' . $operator . ' (';
					$inClause = '';
					foreach($value as $oneValue) {
						if(!empty($inClause)) {
							$inClause .= ', ';
						}
						$inClause .= '$$';
					}
					$clause .= $inClause . ')';
				} elseif ($value === null) {
					$value = array();
					$clause = ' ' . $this->getCriteriaIdentifier($table, $criteria) . ' IS NULL ';
				} else {
					$operator = '=';
				}
			break;
			case builder::OPERATOR_NOTEQUAL:
				if(is_array($value)) {
					$operator = ' NOT IN ';
					$clause = ' ' . $this->getCriteriaIdentifier($table, $criteria) . ' ' . $operator . ' (';
					$inClause = '';
					foreach($value as $oneValue) {
						if(!empty($inClause)) {
							$inClause .= ', ';
						}
						$inClause .= '$$';
					}
					$clause .= $inClause . ')';
				} elseif ($value === null) {
					$value = array();
					$clause = ' ' . $this->getCriteriaIdentifier($table, $criteria) . ' IS NOT NULL ';
				} else {
					$operator = '!=';
				}
			break;
			case builder::OPERATOR_LIKE:
				$operator = 'LIKE';
			break;
			case builder::OPERATOR_NOTLIKE:
				$operator = 'NOT LIKE';
			break;
			case builder::OPERATOR_ILIKE:
				$operator = 'ILIKE';
			break;
			case builder::OPERATOR_NOTILIKE:
				$operator = 'NOT ILIKE';
			break;
			case builder::OPERATOR_LOWER:
				$operator = '<';
			break;
			case builder::OPERATOR_GREATER:
				$operator = '>';
			break;
			case builder::OPERATOR_LOWEREQUAL:
				$operator = '<=';
			break;
			case builder::OPERATOR_GREATEREQUAL:
				$operator = '>=';
			break;
			default: 
				throw new \rk\exception('invalid operator');
		}

		if(empty($clause)) {
			// standard clause if none was defined in the switch
			$clause = ' ' . $this->getCriteriaIdentifier($table, $criteria) . ' ' . $operator . ' $$ ';
		}			
		
		return array($clause, $value);
	}
	
	
	
	public function checkOperator(\rk\db\criteria $criteria) {
		$returnCandidate = '';
		$operator = $criteria->getOperator();
		if(!empty($operator)) {
			$returnCandidate = $criteria->getOperator();
		} else {
			$returnCandidate = $this->defaultOperator;
		}
		
		
		if(!in_array($returnCandidate, $this->allowedOperators)) {
			throw new \rk\exception('invalid operator', array('ope' => $returnCandidate, 'allowed' => $this->allowedOperators));
		}
		return true;
	}
	
	protected function getOperator(\rk\db\criteria $criteria) {
		$returnCandidate = '';
		if(!is_null($criteria->getOperator())) { 
			if(!in_array($criteria->getOperator(), $this->allowedOperators)) {
				throw new \rk\exception('invalid operator');
			} else {
				$returnCandidate = $criteria->getOperator();
			}
		} else {
			$returnCandidate = $this->defaultOperator;
		}
		
		if(empty($returnCandidate)) {
			throw new \rk\exception('no operator');
		}
		
		return $returnCandidate;
	}
}