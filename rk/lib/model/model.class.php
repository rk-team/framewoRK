<?php

namespace rk;

abstract class model {

	protected $attributes;
	protected $behaviours = array();
	protected $references = array();
	
	protected $tableName;
	protected $dbConnectorName = 'default';
	
	protected $i18nParams;
	
	private $PK;
	
	abstract protected function init();
	
	final public function __construct() {
		$backtrace = debug_backtrace();
		if($backtrace[1]['class'] != 'rk\model\manager') {
			throw new \rk\exception('use rk\model\manager::get to get model');
		}
		
		$this->init();
	}
	
	public function getRKIdForAttribute($attrName) {
		$sqlId = $this->getSQLIdForAttribute($attrName);
		
		return str_replace('.', '_', $sqlId);
	}
	
	public function getSQLIdForAttribute($attrName) {
		if(empty($this->attributes[$attrName])) {
			throw new \rk\exception('unknown attribute name', array($attrName));
		}
		$sqlId = $this->tableName . '.' . $attrName;
		
		return $sqlId;
	}
	
	
	protected function setAttributes(array $attributes) {
		foreach($attributes as $one) {
			$this->setAttribute($one);
		}
	}
	
	protected function setAttribute(\rk\model\attribute $attribute) {
		$this->attributes[$attribute->getName()] = $attribute;
	}
	public function getAttribute($attribute) {
		if(!empty($this->attributes[$attribute])) {
			return $this->attributes[$attribute];
		}
		
		return false;
	}
	
	public function getAttributes() {
		return $this->attributes;
	}
	
	public function getAttributesNames() {
		$names = array();
		foreach($this->attributes as $name => $attr) {
			$names[] = $name;
		}
		
		return $names;
	}
	
	protected function addBehaviours(array $behaviours) {
		foreach($behaviours as $name => $one) {
			$this->addBehaviour($name, $one);
		}
	}
	
	protected function addBehaviour ($name, \rk\model\behaviour $behaviour) {
		$this->behaviours[$name] = $behaviour;
	}
	
	protected function addReferences(array $references) {
		foreach($references as $name => $one) {
			$this->addReference($one);
		}
	}
	
	protected function addReference(\rk\model\reference $reference) {
		$this->references[$reference->getName()] = $reference;
	}
	
	public function hasReference($name) {
		if(!empty($this->references[$name])) {
			return true;
		}
		
		return false;
	}
	
	public function getReferences() {
		return $this->references;
	}
	
	/**
	 * @return \rk\model\reference
	 */
	public function getReference($name) {
		if($this->hasReference($name)) {
			return $this->references[$name];
		}
		return false;
	}
	
	public function getReferencesForAttribute($attrName) {
		if($attrName instanceof \rk\model\attribute) {
			$attrName = $attrName->getName();
		}
		
		$return = array();
		foreach($this->references as $one) {
			if($one->getReferencingField() == $attrName) {
				$return[] = $one;
			}
		}
			
		return $return;
	}
	
	public function getBehavioursForAttribute($attrName) {
		if($attrName instanceof \rk\model\attribute) {
			$attrName = $attrName->getName();
		}
		
		$return = array();
		foreach($this->behaviours as $one) {
			$params = $one->getParams();
			if(!empty($params['requires_field']) && $params['requires_field'] == $attrName) {
				$return[] = $one;
			}
		}
			
		return $return;
	}
	
	public function getName() {
		$className = get_class($this);
		$className = str_replace('user\model\\', '', $className);
		
		return $className;
	}
	
	/**
	 * @return \rk\db\table
	 */
	public function getTable() {
		$className = $this->getName();
		$className = '\user\table\\' . $className;
		
		return new $className(); 
	}
	
	/**
	 * @param array $data
	 * @return \rk\object
	 */
	public function getObject(array $data = array()) {
		$className = $this->getName();
		$className = '\user\object\\' . $className;
		
		return new $className($data); 
	}
	
	public function getPK() {
		if(!empty($this->PK)) {
			return $this->PK;
		}
		
		foreach($this->attributes as $key => $oneAttribute) {
			if(!empty($oneAttribute->getParam('primary'))) {
				$this->PK = $key;
				return $key;
			}
		}
		throw new \rk\exception('primary key not found');
	}
	
	public function getTableName() {
		return $this->tableName;
	}
	public function getDbConnectorName() {
		return $this->dbConnectorName;
	}

}