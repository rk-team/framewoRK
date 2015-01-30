<?php

namespace rk\db;

abstract class builder {
	
	const
		OPERATOR_EQUAL 		= 'equal',
		OPERATOR_NOTEQUAL 	= 'notequal',
		OPERATOR_LIKE 		= 'like',
		OPERATOR_NOTLIKE 	= 'notlike',
		OPERATOR_ILIKE 		= 'ilike',
		OPERATOR_NOTILIKE 	= 'notilike',
		OPERATOR_GREATER	= 'greater',
		OPERATOR_LOWER 		= 'lower',
		OPERATOR_GREATEREQUAL = 'greaterEqual',
		OPERATOR_LOWEREQUAL   = 'lowerEqual';
	
	
	abstract public function formatValuesForBuilder($value, $type);
	abstract public function formatValuesFromBuilder($value, $type);
	
	/**
	 * used to automatically build a SELECT on $table
	 * @param \rk\db\table $table
	 * @param \rk\db\criteriaSet $criteriaSet
	 * @param array $params
	 */
	public function buildSelect(\rk\db\table $table, \rk\db\criteriaSet $criteriaSet = null, array $params = array()) {
		
		$bindParams = array();
		
		$query = $this->_buildSelect_SelectPart($table, $criteriaSet, $params);
		
		$query .= $this->_buildSelect_FromPart($table, $criteriaSet, $params);
		
		list($leftJoinQuery, $bindParams) = $this->_buildSelect_LeftJoinPart($table, $criteriaSet, $params);
		$query .= $leftJoinQuery;
		
		list($where, $whereBindParams) = $this->_buildSelect_WherePart($table, $criteriaSet);
		
		$bindParams = array_merge($bindParams, $whereBindParams);
		
		$query .= $where;
		$query .= "\n";
		
		if(empty($params['countOnly'])) {
			$query .= $this->buildOrderBy($table, $params);
			$query .= $this->buildLimit($table, $params);
		}
		
		
		list($query, $bindParams) = $this->handleBinds($query, $bindParams);
		

		return array($query, $bindParams);
	}
	
	protected function _buildSelect_SelectPart(\rk\db\table $table, \rk\db\criteriaSet $criteriaSet = null, array $params = array()) {
		$query = "\n" . 'SELECT' . "\n";
		
		$fieldsSelect = '';
		$references = array();
		
		if(!empty($params['countOnly'])) {
			$fieldsSelect = "\t" . $this->_buildSelect_SelectPartCountOnly($table, $criteriaSet, $params);
		} elseif(!empty($params['PKOnly'])) {
			$fieldsSelect = "\t" . $this->_buildSelect_SelectPartPKOnly($table, $criteriaSet, $params);
		} else {
			$fieldsSelect .= $this->_buildSelect_SelectPartFromModel($table, $criteriaSet, $params);
			
			$fieldsSelect .= $this->_buildSelect_SelectPartFromLeftJoin($table, $criteriaSet, $params);
			
			$fieldsSelect .= $this->_buildSelect_SelectPartFromExtraSelects($table, $criteriaSet, $params);
		}
				
		$query .= $fieldsSelect;
		
		$query .= "\n";
		
		return $query;
	}
	
	protected function _buildSelect_SelectPartCountOnly(\rk\db\table $table, \rk\db\criteriaSet $criteriaSet = null, array $params = array()) {
		return ' COUNT(DISTINCT ' . $table->getName() . '.' . $table->getModel()->getPK() . ') as count ' . "\n";
	}
	
	protected function _buildSelect_SelectPartPKOnly(\rk\db\table $table, \rk\db\criteriaSet $criteriaSet = null, array $params = array()) {
		return ' DISTINCT ' . $table->getName() . '.' . $table->getModel()->getPK() . ' as  ' . $table->getModel()->getPK() . "\n";
	}
	
	protected function _buildSelect_SelectPartFromModel(\rk\db\table $table, \rk\db\criteriaSet $criteriaSet = null, array $params = array()) {
		$fieldsSelect = '';
		
		$attributes = $table->getModel()->getAttributes();
		
		foreach($attributes as $one) {
			if(!empty($fieldsSelect)) {
				$fieldsSelect .= ', ' . "\n";
			}
			
			$fieldName = $this->getFullFieldName($table, $one->getName()); 
			$fieldsSelect .= "\t" . $fieldName . ' AS ' . $one->getName() . '';
		}
		
		return $fieldsSelect;
	}
	
	protected function _buildSelect_SelectPartFromLeftJoin(\rk\db\table $table, \rk\db\criteriaSet $criteriaSet = null, array $params = array()) {
		$fieldsSelect = '';
		
		$references = $table->getReferences(); 
		foreach($references as $oneReference) {
			$fields = $oneReference->getSelectFields();
			foreach ($fields as $oneField) {
				if(!empty($fieldsSelect)) {
					$fieldsSelect .= ', ' . "\n";
				}
				
				$fieldsSelect .= "\t" . $oneReference->getReferencedTableAlias() . '.' . $oneField . ' AS ' . $oneReference->getReferencedTableAlias() . '_' . $oneField;
			}
		}
		
		if(!empty($fieldsSelect)) {
			$fieldsSelect = ', ' . "\n\n" . $fieldsSelect;
		}
		
		return $fieldsSelect;
	}
	
	protected function _buildSelect_SelectPartFromExtraSelects(\rk\db\table $table, \rk\db\criteriaSet $criteriaSet = null, array $params = array()) {
		$fieldsSelect = '';
		
		if(!empty($params['extraSelects'])) {
			foreach($params['extraSelects'] as $identifier => $oneExtraSelect) {
				if(!empty($fieldsSelect)) {
					$fieldsSelect .= ',';
				}
				$fieldsSelect .= "\t" . ' (' . $oneExtraSelect . ') AS ' . $identifier . "\n";
			}
		}
		
		if(!empty($fieldsSelect)) {
			$fieldsSelect = ', ' . "\n\n" . $fieldsSelect;
		}
		
		return $fieldsSelect;
	}
	
	
	
	protected function _buildSelect_FromPart(\rk\db\table $table, \rk\db\criteriaSet $criteriaSet = null, array $params = array()) {
		return ' FROM ' . $table->getName() . "\n";
	}
	
	protected function _buildSelect_LeftJoinPart(\rk\db\table $table, \rk\db\criteriaSet $criteriaSet = null, array $params = array()) {
		$query = '';
		$bindParams = array();
		
		$references = $table->getReferences(); 
		
		foreach($references as $oneReference) {
							
			if(!empty($oneReference->getCondition())) {
				$condition = $oneReference->getCondition();
			} 
			elseif (!empty($oneReference->getReferencingField()) 
					&& !empty($oneReference->getReferencedField()) 
					&& !empty($oneReference->getReferencedTable())) {
				
				if(!empty($oneReference->getReferencingTableAlias())) {
					$referencing = $oneReference->getReferencingTableAlias() . '.' . $oneReference->getReferencingField();
				} else {
					$referencing = $table->getName() . '.' . $oneReference->getReferencingField();
				}
				$condition = $referencing . ' = ' . $oneReference->getReferencedTableAlias() . '.' . $oneReference->getReferencedField();
				
				if(!empty($oneReference->getExtraCondition())) {
					$extraWhere = '';
					$level = 0;
					$this->_handleCriteriaSet($extraWhere, $bindParams, $level, $table, $oneReference->getExtraCondition());
					$condition .= ' AND ' . $extraWhere;
				}
			} 
			else {	
				throw new \rk\exception('invalid left join params : no condition', array('params' => $oneLeftJoin));
			}
			
			$query .= "\t" . ' LEFT JOIN ' . $oneReference->getReferencedTable() . ' AS ' . $oneReference->getReferencedTableAlias() . ' ON ' . $condition . "\n";
		}
		
		$query .= "\n";
		
		return array($query, $bindParams);
	}
	
	public function buildLimit(\rk\db\table $table, array $params = array()) {
		$return = '';
	
		if(!empty($params['limit'])) {
			if(!array_key_exists('offset', $params)) {
				$offset = 0;
			} else {
				$offset = $params['offset'];
			}
				
			$return = ' LIMIT ' . $offset . ', ' . $params['limit'] . ' ';
		}
	
		return $return;
	}
	
	public function buildOrderBy(\rk\db\table $table, array $params = array()) {
		$return = '';
		if(!empty($params['orderColumn']) && !empty($params['orderSort'])) {
			$fieldName = $this->getFullFieldName($table, $params['orderColumn']);
			$return = ' ORDER BY ' . $fieldName . ' ' . $params['orderSort'] . ' ';
		}
	
		return $return;
	}
	
	public function _buildSelect_WherePart(\rk\db\table $table, \rk\db\criteriaSet $criteriaSet = null) {
		$bindParams = array();
		$where = '';
		if(!empty($criteriaSet)) {
			
			$level = 0;
			$this->_handleCriteriaSet($where, $bindParams, $level, $table, $criteriaSet);
				
			if(!empty($where)) {
				$where = ' WHERE (' . $where . ')';
			}
		}
	
		return array($where, $bindParams);
	}
	
	protected function _handleCriteriaSet(&$where, &$bindParams, $level, $table, $criteriaSet) {
		$nbSubsSet = 0;
		$nbCriterias = 0;
		
		$whereForCurrentCriteriaSet = '';
		$criterias = $criteriaSet->getCriterias();
		
		if (is_array($criterias) && empty($criterias)) {
			return;
		}
		
		if($level > 0) {
			$whereForCurrentCriteriaSet .= "\n" . $this->_addTab($level) . '(' . "\n";
		}
		foreach($criterias as $oneCriteria) {
// 			var_dump($oneCriteria, $nbCriterias, $nbSubsSet);
			if($oneCriteria instanceof \rk\db\criteria) {
				$nbCriterias++;
				if(!$table->hasFilter($oneCriteria->getName())) {
					throw new \rk\exception('unknown filter ' . $oneCriteria->getName());
				}
				$filter = $table->getFilter($oneCriteria->getName());
				$filter->checkOperator($oneCriteria);
				list($criteriaWhere, $binds) = $filter->createWherePart($table, $oneCriteria);
				
				if($nbCriterias > 1 || $nbSubsSet > 0) {
					$whereForCurrentCriteriaSet .= $this->_addTab($level + 1);
// 					echo '<br />1 : ajout ' . $criteriaSet->getOperator();
					$whereForCurrentCriteriaSet .= $criteriaSet->getOperator() . "\t";
				} else {
					$whereForCurrentCriteriaSet .= $this->_addTab($level + 2);
				}
				$whereForCurrentCriteriaSet .= ' ' . $criteriaWhere . ' ' . "\n";
				
				if(!is_array($binds)) {
					$binds = array($binds);
				}
				foreach($binds as $oneBind) {
					$bindParams[] = $oneBind;
				}
			} else {
				$nbSubsSet++;
				if($nbSubsSet > 1 || $nbCriterias > 0) {
// 					echo '<br />2 : ajout ' . $criteriaSet->getOperator();
					$whereForCurrentCriteriaSet .= $this->_addTab($level + 1) . $criteriaSet->getOperator() . ' ';
				}
				$this->_handleCriteriaSet($whereForCurrentCriteriaSet, $bindParams, $level + 1, $table, $oneCriteria);
			}
		}
		if($level > 0) {
			$whereForCurrentCriteriaSet .= $this->_addTab($level) . ')' . "\n";
		}
		$where .= $whereForCurrentCriteriaSet;
	}
	
	protected function _addTab($level) {
		$return = '';
		for($i = 0; $i <= $level; $i++) {
			$return .= "\t";
		}
		return $return;
	}
	
	/**
	 * used to convert "$$" placeholders in $whereClause into named binds that will be understood by SQL
	 * NOTE : this should only be called on automatically constructed queries. If you build you SQL yourself, there's no need to call it
	 * @param string $whereClause
	 * @param array $bindParams
	 * @return array
	 * 			with 0 => modified whereClause string
	 * 			 and 1 => modified bindParams array
	 * ex :
	 * 	with $whereClause = ' where field = $$ and other_field = $$'
	 * 	 and $bindParams = array(1, 2);
	 *	the handleBinds method hast to return something like
	 *			array(
	 *				'where field = :param1 and other_field = :param2',
	 *				array('param1' => 1, 'param2' => 2)
	 *			)
	 *
	 */
	public function handleBinds($query, $bindParams) {
		$split = preg_split('/\$\$/', $query);
		$nbFoundParams = count($split) - 1;
	
		if(count($bindParams) != $nbFoundParams) {
			throw new \rk\exception('invalid binds count. found ' . $nbFoundParams . ' param in query, ' . count($bindParams) . ' binds given', array('query' => $query, 'binds' => $bindParams));
		}
	
		$return = '';
		$namedBinds = array();
		for($i = 0; $i < $nbFoundParams; $i++) {
			$paramName = ':param' . ($i + 1);
			$return .= $split[$i] . $paramName;
			$namedBinds[$paramName] = $bindParams[$i];
		}
		$return .= $split[$nbFoundParams];
	
		return array($return, $namedBinds);
	}
	
	/**
	 * used to automatically build an INSERT on $table
	 * @param \rk\db\table $table
	 * @param array $values
	 */
	public function buildInsert(\rk\db\table $table, array $values) {
		$binds = array();
		$query = 'INSERT INTO ' . $table->getName() . ' (';
	
	
		foreach($values as $key => $oneValue) {
			$query .= $key . ', ';
		}
	
		$query = substr($query, 0, -2);	// remove extra ,
	
		$query .= ') VALUES (';
	
	
		foreach($values as $key => $oneValue) {
			$query .= '$$, ';
			$binds[] = $oneValue;
		}
	
		$query = substr($query, 0, -2); 	// remove extra ,
		$query .= ')';
	
		list($query, $binds) = $this->handleBinds($query, $binds);
	
		return array($query, $binds);
	}
	
	/**
	 * used to automatically build an UPDATE on $table
	 * @param \rk\db\table $table
	 * @param array $values
	 */
	public function buildUpdate(\rk\db\table $table, array $values) {
		$binds = array();
		$query = 'UPDATE ' . $table->getName() . ' SET ';
	
		foreach($values as $key => $oneValue) {
			if($key != $table->getModel()->getPK()) {
				$query .= $key . ' =  $$, ';
				$binds[] = $oneValue;
			}
		}
	
		$query = substr($query, 0, -2);	// remove extra ,
	
		$query .= ' WHERE ' . $table->getModel()->getPk() . ' = $$';
		$binds[] = $values[$table->getModel()->getPk()];
	
		list($query, $binds) = $this->handleBinds($query, $binds);
	
		return array($query, $binds);
	}
	
	/**
	 * used to automatically build a DELETE on $table
	 * @param \rk\db\table $table
	 * @param \rk\db\criteriaSet $criteriaSet
	 */
	public function buildDelete(\rk\db\table $table, \rk\db\criteriaSet $criteriaSet = null) {
	
		$query = 'DELETE FROM ' . $table->getName();
		list($where, $binds) = $this->_buildSelect_WherePart($table, $criteriaSet);
		$query .= $where;
	
		list($query, $binds) = $this->handleBinds($query, $binds);
		
		return array($query, $binds);
	}
	
	/**
	 * return full field name with table name, if table name not already in fieldName
	 * @param \rk\db\table $table
	 * @param unknown $name
	 * @return Ambigous <string, unknown>
	 */
	protected function getFullFieldName (\rk\db\table $table, $name) {
		$res = $name;
		$attributes = $table->getModel()->getAttributes();
	
		if (array_key_exists($name, $attributes) && (strpos($name, $table->getName()) === false)) {
			$res = $table->getName() . '.' . $name;
		}
	
		return $res;
	}
	
}
