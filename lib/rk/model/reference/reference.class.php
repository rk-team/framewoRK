<?php

namespace rk\model;

/**
 * Represents a Database Reference (Foreign Key)
 */
class reference {
	
	protected 
		$referencingTableAlias = '',// alias of the table from which to build the left join. To be used when adding a reference on a referenced table
									// default : name of the origin table
		$referencingField,			// name of the field from which to retrieve foreign data (ex: "author_id")
	
		$fields = array(), 			// fields to retrieve in the foreign table
		$referencedModelName,		// model class name for the foreign data	(ex: "\rk\model\user")
		$referencedField = '',		// name of the field of the foreign table to use to build the left join condition (ex: "id")
		$referencedTableAlias = '',	// alias of the foreign table in the SQL query  (ex: "author")
		
		$parentReferenceName = null,
		
		$condition = '',			// SQL condition to use in the left join
		$extraCondition = null,		// \rk\db\criteriaSet to be added to the left join's base condition
		
		$hasMany = false,			// several values have to be stored for this reference
		$hydrateBy = null,			// name of a field to use for result hydration (optionnal)
		$name = '';					// reference name
		
	private 
		$referencedModel,			// internal. \rk\model for the foreign data	(ex: instanceof \rk\model\user)
		$referencedTable = null;	// internal. \rk\table for the foreign data (ex: instance of \rk\table\user)
	

	public function __construct ($name, $referencingField, $referencedModel, array $params = array()) {
		
		$this->name = $name;
		
		$this->referencingField = $referencingField;
		
		$referencedModel = str_replace('.', '\\', $referencedModel);	// replace "." by "\" for DB with schema (like postgre)
		if(strpos($referencedModel, '\user\model\\') === false) {
			$referencedModel = '\user\model\\' . $referencedModel;
		}
		if (!class_exists($referencedModel)) {
			throw new \rk\exception('invalid params', array('referencedModel' => $referencedModel));
		}
		$this->referencedModelName = $referencedModel;

		if (!empty($params['fields'])) {
			if(!is_array($params['fields'])) {
				$params['fields'] = array($params['fields']);
			}
			$this->fields = $params['fields'];
		}
		
		if (!empty($params['referencedField'])) {
			$this->referencedField = $params['referencedField'];
		}
		
		if (!empty($params['referencedTableAlias'])) {
			$this->referencedTableAlias = $params['referencedTableAlias'];
		}
		
		if (!empty($params['referencingTableAlias'])) {
			$this->referencingTableAlias = $params['referencingTableAlias'];
		}
		
		if (!empty($params['parentReferenceName'])) {
			$this->parentReferenceName = $params['parentReferenceName'];
		}
		
		if (!empty($params['hasMany'])) {
			$this->hasMany = $params['hasMany'];
		}
		
		if (!empty($params['condition'])) {
			$this->condition = $params['condition'];
		}
		if (!empty($params['extraCondition'])) {
			$this->setExtraCondition($params['extraCondition']);
		}
		if (!empty($params['hydrateBy'])) {
			$this->hydrateBy = $params['hydrateBy'];
		}
	}
		
	public function getReferencingField() {
		return $this->referencingField;
	}
	
	/**
	 * @return \rk\model
	 */
	public function getReferencedModel() {
		return \rk\model\manager::get($this->referencedModelName);
	}
	
	public function getReferencedModelName() {
		return $this->referencedModelName;
	}
	
	public function getParentReferenceName() {
		return $this->parentReferenceName;
	}
	
	public function getCondition() {
		return $this->condition;
	}
	public function setExtraCondition(\rk\db\criteriaSet $extraCondition) {
		$this->extraCondition = $extraCondition;
	}
	public function getExtraCondition() {
		return $this->extraCondition;
	}
	
	public function getHydrateBy() {
		return $this->hydrateBy;
	}
	
	public function getSelectFields() {
		$res = $this->fields;
		//If no fields specified, we get all model attributes
		if (empty($res)) {
			$res = array();
			
			foreach ($this->getReferencedModel()->getAttributes() as $oneAttribute) {
				$res[] = $oneAttribute->getName(); 
			}
		} 
		//Else we have to be sure that the pk is selected (as it is used to dispatchResults)
		else {
			$pk = $this->getReferencedModel()->getPk();
			if (!in_array($pk, $this->fields)) {
				$res[] = $pk;
			}
		}
		return $res;
	}
	
	public function setFields($fields) {
		if(!is_array($fields)) {
			$fields = array($fields);
		}
		$this->fields = $fields;
	}
	public function getFields() {
		$res = $this->fields;
		//If no fields specified, we get all model attributes
		if (empty($res)) {
			$res = array();
			
			foreach ($this->getReferencedModel()->getAttributes() as $oneAttribute) {
				$res[] = $oneAttribute->getName(); 
			}
		} 
		return $res;
	}
	
	// return a field to be used for display purpose (like in select's options)
	public function getDisplayField() {
		$fields = $this->getFields();
		$nbFields = count($fields);
		if($nbFields == 1) {
			// if only one field, we use it
			return $fields[0];
		}
		
		$pk = $this->getReferencedModel()->getPK();
		if(in_array($pk, $fields)) {
			// else we use the PK
			return $pk;
		}
		
		// finally, we use the first field
		return $fields[0];
		
	}
	
	public function getReferencedTable() {
		return $this->getReferencedModel()->getTableName();
	}
	
	public function getReferencedField() {
		$res = $this->referencedField;
		if (empty($res)) {
			$res = $this->getReferencedModel()->getPK();
		}
		return $res;
	}	
	
	public function getReferencedTableAlias() {
		$res = $this->referencedTableAlias;
		if (empty($res)) {
			$res = $this->getReferencedTable();
			$res = str_replace('.', '_', $res);
		}
		return $res;
	}
	
	public function getReferencingTableAlias() {
		if(!empty($this->referencingTableAlias)) {
			return $this->referencingTableAlias;
		}
		return false;
	}
	
	public function setReferencingTableAlias($alias) {
		$this->referencingTableAlias = $alias;
	}
	
	public function hasMany() {
		return $this->hasMany;
	}
	
	public function getName() {
		return $this->name;
	}
}