<?php

namespace rk\db;

abstract class table {
	
	protected
		$dbConnectorName = 'default',
		$name,
		$filters = array(),
		$references = array();
	
	protected $limitForSelect = 100;
	
	protected $modelName = null;
	
	public function __construct() {
		
		$modelName = get_class($this);
		$this->modelName = str_replace('user\table', 'user\model', $modelName);
		
		$this->name = $this->getModel()->getTableName();
		$this->dbConnectorName = $this->getModel()->getDbConnectorName();
			
		$filters = array();
		$attributes = $this->getModel()->getAttributes();
		foreach ($attributes as $oneAttributes) {
			$name = $oneAttributes->getName();
			$this->addFilter($oneAttributes->getType(), $name, array('modelName' => $this->modelName, 'attributeName' => $name));
		}
						
		//initialize with model reference
		foreach($this->getModel()->getReferences() as $oneRef) {
			$this->addReference($oneRef);
		}
	}

	/**
	 * 
	 * @param string $tableName
	 * @return \rk\db\table
	 */
	public static function on($tableName) {
		$className = '\user\table\\' . $tableName;
		return new $className();
	}
	
	protected function getCriterias($criteriaSet = array()) {
		
		if(!$criteriaSet instanceof \rk\db\criteriaSet) {
			if(!is_array($criteriaSet)) {
				throw new \rk\exception('criteriaSet is neither a \rk\db\criteriaSet nor an array');
			}
			$criteriaSet = new \rk\db\criteriaSet($criteriaSet);
		}
		
		return $criteriaSet;
	}
	
	public function _get($criteriaSet = array(), array $params = array(), $toArray = true) {
		
		if(empty($this->modelName)) {
			throw new \rk\exception('cant use magic get methods if table is not based on model');
		}
		
		if(!empty($params['orderColumn']) && empty($params['orderSort'])) {
			$params['orderSort'] = 'ASC';
		}		
		
		if(!empty($params['orderColumn']) && !empty($params['extraSelects']) && !empty($params['extraSelects'][$params['orderColumn']])) {
			// the order by column is an extra select, we change the orderColum given to the builder
			$params['orderColumn'] = ' (' . $params['extraSelects'][$params['orderColumn']] . ') ';
		}
		
		$criteriaSet = $this->getCriterias($criteriaSet);
		
		$builder = $this->getBuilder();
				
		list($query, $bindParams) = $builder->buildSelect($this, $criteriaSet, $params);
		
		$connector = $this->getConnector();
		
		$res = $connector->query($query, $bindParams);

		if(empty($params['countOnly']) && empty($params['PKOnly'])) {
			$extraSelects = array();
			if(!empty($params['extraSelects'])) {
				$extraSelects = $params['extraSelects'];
			}
			$this->dispatchResults($res, $extraSelects);
		}
		
		if(!$toArray) {
			$res = $this->formatObjects($res);
		}
		
		return $res;
	}
	
	public function getReferences() {
		return $this->references;
	}
	public function getReference($refName) {
		if(!empty($this->references[$refName])) {
			return $this->references[$refName];
		}
		return false;
	}
	public function addReference(\rk\model\reference $reference) {
		
		if(!empty($reference->getParentReferenceName())) {
			// search parent reference
			$parentRef = $this->getReference($reference->getParentReferenceName());
			if(empty($parentRef)) {
				throw new \rk\exception('parent reference not found');
			}
			$reference->setReferencingTableAlias($parentRef->getReferencedTableAlias());
		}
		
		$this->references[$reference->getName()] = $reference;
		
		// create filters for referenced model
		$model = $reference->getReferencedModel();
		foreach($model->getAttributes() as $attrName => $attr) {
			
			$filterName = $model->getTableName() . '.' . $attrName;

			$filterParams = array(
				'modelName'		=> get_class($model), 
				'attributeName'	=> $attrName,
				'referenceName'	=> $reference->getName(),
			);

			$type = $attr->getType();
			
			$this->addFilter($type, $filterName, $filterParams);
		}
	}
	public function removeReference($refName) {
		
		if (!empty($this->references[$refName])) {
			
			// create filters for referenced model
			$model = $this->references[$refName]->getReferencedModel();
			foreach($model->getAttributes() as $attrName => $attr) {				
				$filterName = $model->getTableName() . '.' . $attrName;
				$this->removeFilter($filterName);
			}
			
			unset($this->references[$refName]);
		} else {
			throw new \rk\exception();
		}
	}
	
	protected function dispatchResults(&$res, array $extraSelects = array()) {
		$time = microtime(true);
		\rk\webLogger::add(array('dispatchResults' => 'start'));
		
		$return = array();
		
		$builder = $this->getBuilder();
		$modelAttributes = $this->getModel()->getAttributes();
		$PK = $this->getModel()->getPK();
			
		//Foreach Res
		foreach ($res as $oneResKey => $oneRes) {
			$refData = array();
			
			$PKForRes = $oneRes[$PK];
				
			// add values for base model
			foreach($modelAttributes as $oneAttr) {
				if (array_key_exists($oneAttr->getName(), $oneRes)) {
					$return[$PKForRes][$oneAttr->getName()] = $builder->formatValuesFromBuilder($oneRes[$oneAttr->getName()], $oneAttr->getType());
				}
			}

			// add values for extra selects
			foreach($extraSelects as $identifier => $oneExtraSelect) {
				$return[$PKForRes][$identifier] = $oneRes[$identifier];
			}
						
			// add values for references
			foreach($this->references as $oneReference) {
				
				$aliasName = '';
				$fieldName = $aliasName . $oneReference->getReferencedField();
				
				if ($oneReference->getReferencedTable() != $this->name) {
					$aliasName = $oneReference->getReferencedTableAlias();
				}
				
				// check if referenced pk is null, res will not be save if true
				$fieldName = $oneReference->getReferencedField();
				if(!empty($aliasName)) {
					$fieldName = $aliasName . '_' . $oneReference->getReferencedField();
				}
								
				if (is_null($oneRes[$fieldName])) {
					continue;
				}
				
				// for references with several fields, we save them in refData to get an array with ALL the values for that reference
				foreach($oneReference->getSelectFields() as $oneField) {
					$fieldName = $oneField;
					if(!empty($aliasName)) {
						$fieldName = $aliasName . '_' . $oneField;
					}

					if(array_key_exists($fieldName, $oneRes)) {
						$refData[$aliasName][$oneField] = $oneRes[$fieldName];
					}
				}
			}
			// then we use $refData to add references objects to our return
			foreach($this->references as $oneReference) {
				$hydrateBy = $oneReference->getHydrateBy();
				
				$aliasName = '';
				if ($oneReference->getReferencedTable() != $this->name) {
					$aliasName = $oneReference->getReferencedTableAlias();
				}

				if(!empty($refData[$aliasName])) {
					
					if(!empty($oneReference->getReferencingTableAlias()) && $oneReference->getReferencingTableAlias() != $this->name) {
						// reference does not point to current table

						if(!empty($oneReference->getParentReferenceName())) {
							$target = &$return[$PKForRes];

							// we have to loop on parent reference until we get one that points to current table
							$parentReferences = array();							
							$parentRef = false;
							do {
								if(empty($parentRef)) {
									$parentRef = $this->getReference($oneReference->getParentReferenceName());
								} else {
									$parentRef = $this->getReference($parentRef->getParentReferenceName());
								}
								$parentReferences[] = $parentRef;
							} while(!empty($parentRef->getReferencingTableAlias()) && $parentRef->getReferencingTableAlias() != $this->name);
							
							$parentReferences = array_reverse($parentReferences);	// reverse the order in order to have references starting FROM current table
							$nbParents = count($parentReferences);
							for($i = 0; $i < $nbParents; $i++) {
								$parent = $parentReferences[$i]->getReferencedTableAlias();
								if(!$parent) {
									$parent = $this->name;
								}
								$target = &$target[$parent];
							}
							$target = &$target[$oneReference->getReferencedTableAlias()];
						} else {
							// we add it directly to our return array
							$target = &$return[$PKForRes][$oneReference->getReferencedTableAlias()];
						}
					} else {
						$target = &$return[$PKForRes][$aliasName];
					}
					
					if($oneReference->hasMany()) {
						if(!empty($hydrateBy)) {
							$target[$refData[$aliasName][$hydrateBy]] = $refData[$aliasName];
						} else {
							$target[] = $refData[$aliasName];
						}
					} else {
						$target = $refData[$aliasName];
					}
					unset($target);
				}
			}
		}
		
		$res = array_values($return);	// reset keys
		
		\rk\webLogger::add(array('dispatchResults' => 'end', 'selfDuration' => microtime(true) - $time));
	}
	
	
	private function _formatObjects(&$obj) {
		
		foreach($this->references as $oneReference) {
			$aliasName = $oneReference->getReferencedTableAlias();
			$data = $obj[$aliasName];
			if(!empty($data)) {
				$modelName = $oneReference->getReferencedModelName();
				$model = \rk\model\manager::get($modelName);
				if($oneReference->hasMany()) {
					foreach($data as $key => $oneRow) {
						$data[$key] = $model->getObject($oneRow);
						$this->_formatObjects($data[$key]);
					}
					$obj[$aliasName] = $data;
				} else {
					$obj[$aliasName] = $model->getObject($obj[$aliasName]);
					$this->_formatObjects($obj[$aliasName]);
				}
			}
		}
	}
	
	protected function formatObjects($res) {
		$return = array();
		
		foreach($res as $oneRes) {
			$obj = $this->getModel()->getObject($oneRes);		
			$return[] = $obj;
			
			$this->_formatObjects($obj);
		}

		return $return;
	}
	
	/**
	 *
	 * @param string $pkValue :  
	 * @throws \rk\exception
	 */
	public function getByPk($pkValue, $toArray = true) {
		$pkName = $this->getModel()->getPK();
		
		$res = $this->_get(array($pkName => $pkValue), array(), $toArray);
		if (!empty($res[0])) {
			$res = $res[0];
		}
		return $res;
	}
		
	/**
	 * 
	 * @param array $criterias : criterias to be added as the where clause (optionnal)
	 * @param array $params : "order by" and "limit" to be added to the query (optionnal)  
	 * @throws \rk\exception
	 */
	public function get($criteriaSet = array(), array $params = array()) {

		$return = array();
		
		$res = $this->_get($criteriaSet, $params, false);
			
		return $res;
	}
	
	
	
	public function getForPager($criterias = array(), array $params = array()) {
		$ids = $this->getPKs($criterias, $params);
		$criterias = new \rk\db\criteriaSet(new \rk\db\criteria($this->getModel()->getPK(), $ids));
		if(!empty($ids)) {
			$params['limit'] = false;
			return $this->_get($criterias, $params, false);
		}
		return array();
	}
	
	public function getPKs($criteriaSet = array(), array $params = array()) {
		
		$params['PKOnly'] = true;
		
		$res = $this->_get($criteriaSet, $params);
		
		$return = array();
		foreach($res as $oneRes) {
			$return[] = $oneRes[$this->getModel()->getPK()];
		}
			
		return $return;
	}
	
	
	
	public function getCount($criteriaSet = array(), array $params = array()) {
	
		$return = array();
	
		$params['countOnly'] = true;
		
		$res = $this->_get($criteriaSet, $params);

		return $res[0]['count'];
	}
	
	
	public function getByArray($criteriaSet = array(), array $params = array()) {
		
		$return = $this->_get($criteriaSet, $params);
		
		return $return;
	}
	
	public function getOne($criteriaSet = array(), array $params = array()) {
		
		$res = $this->_get($criteriaSet, $params, false);
	
		$return = null;
		if(!empty($res[0])) {
			$return = $res[0];
		}
	
		return $return;
	}
	
	public function getOneByArray($criteriaSet = array(), array $params = array()) {
		
		$res = $this->_get($criteriaSet, $params);
	
		$return = null;
		if(!empty($res[0])) {
			$return = $res[0];

		}
	
		return $return;
	}
	
	public function getSelectOptions(array $params = array(), array $criterias = array()) {
		if(empty($params['fieldName'])) {
			throw new \rk\exception('no fieldName given');
		}
		if(empty($params['orderColumn'])) {
			$params['orderColumn'] = $params['fieldName'];
		}
		if(empty($params['orderSort'])) {
			$params['orderSort'] = 'asc';
		}
		
		$count = $this->getCount($criterias, $params);
		if($count > $this->limitForSelect) {
			$params['limit'] = $this->limitForSelect;
		}
		
		$res = $this->_get($criterias, $params);
	
		$return = array();
		foreach($res as $oneRes) {
			// transform raw array into an object
			$return[$oneRes[$this->getModel()->getPK()]] = $oneRes[$params['fieldName']];
		}
	
		return $return;
	}
	
	public function insert(array $values) {
		if(empty($this->modelName)) {
			throw new \rk\exception('cant use insert if table is not based on model');
		}
		
		$builder = $this->getBuilder();
		
		$attr = $this->getModel()->getAttributes();
		foreach ($attr as $oneAttr) {
			if (array_key_exists($oneAttr->getName(), $values)) {
				$values[$oneAttr->getName()] = $builder->formatValuesForBuilder($values[$oneAttr->getName()], $oneAttr->getType());
			}
		}
		
		list($query, $bindParams) = $builder->buildInsert($this, $values);
		
		$connector = $this->getConnector();
		
		$res = $connector->query($query, $bindParams);
				
		return $res;
	}
	
	public function update(array $values) {
		if(empty($this->modelName)) {
			throw new \rk\exception('cant use update if table is not based on model');
		}
		
		$builder = $this->getBuilder();
		
		$attr = $this->getModel()->getAttributes();
		foreach ($attr as $oneAttr) {
			if (array_key_exists($oneAttr->getName(), $values)) {
				$values[$oneAttr->getName()] = $builder->formatValuesForBuilder($values[$oneAttr->getName()], $oneAttr->getType());
			}
		}
		
		list($query, $bindParams) = $builder->buildUpdate($this, $values);
		
		$connector = $this->getConnector();
		
		$res = $connector->query($query, $bindParams);
		
		return $res;
	}
	
	public function delete(array $params = array()) {
		$builder = $this->getBuilder();
		
		$criteriaSet = $this->getCriterias($params);
		
		list($query, $bindParams) = $builder->buildDelete($this, $criteriaSet);
		
		$connector = $this->getConnector();
		
		$res = $connector->query($query, $bindParams);
		
		return $res;
	}
	
	public function getConnector() {
		return \rk\db\manager::get($this->dbConnectorName);
	}
	
	public function getBuilder() {
		$connector = $this->getConnector();
		
		switch($connector->getType()) {
			case 'mysql':
				$builder = new \rk\db\builder\mysql();
			break;
			case 'pgsql':
				$builder = new \rk\db\builder\pgsql();
			break;
				
			default:
				throw new \rk\exception('unknown builder type ' . $connector->getType());
		}
		
		return $builder;
	}
	
	public function getName() {
		return $this->name;
	}
	
	/**
	 * @return \rk\model
	 */
	public function getModel() {
		if(empty($this->modelName)) {
			throw new \rk\exception('cant use getModel if table is not based on model');
		}
		return \rk\model\manager::get($this->modelName);
	}
	
	
	
	
// 	public function addFilters(array $filters) {
// 		foreach($filters as $filterName => $oneFilterParams) {
// 			$this->addFilter($filterName, $oneFilterParams);
// 		}
// 	}
	
	public function addFilter($type, $fieldIdentifier, array $filterParams = array()) {
// 		if(empty($filterParams['type']) || empty($filterParams['fieldIdentifier'])) {
// 			throw new \rk\exception('invalid filterParams');
// 		}
// 		if(empty($filterParams['params'])) {
// 			$filterParams['params'] = array();
// 		}
		
		// try to get on overloaded version of the filter for the connector type
		$connectorType = $this->getConnector()->getType();
		$filterClass = '\rk\db\filter\\' . $connectorType . '\\' . $type;
		
		// otherwise, we take the default filter
		if(!class_exists($filterClass)) {
			$filterClass = '\rk\db\filter\\' . $type;
		}
		
		$filter = new $filterClass($fieldIdentifier, $filterParams);
		
		$this->filters[$fieldIdentifier] = $filter;
	}
	
	public function removeFilter($filterName) {
		if (!empty($this->filters[$filterName])) {
			unset($this->filters[$filterName]);
		} else {
			throw new \rk\exception('invalid filter name');
		}
	}
	
	public function hasFilter($name) {
		return !empty($this->filters[$name]);
	}
	public function getFilter($name) {
		if(empty($this->filters[$name])) {
			throw new \rk\exception('unknown filter ' . $name);
		}
		return $this->filters[$name];
	}
	public function getFilters() {
		return $this->filters;
	}
}