<?php

namespace rk\model;

class attribute {
	
	private static $authorizedTypes = array(
		'text', 
		'richtext', 
		'integer', 
		'date',
		'datetime',
		'boolean',
		'enum'
	);

	protected
		$name,
		$type,
// 		$behaviour,
		$params = array(),
// 		$reference = null,
		$nullable;
	
	public function __construct($name, $type, array $params = array()) {
		$this->name = $name;
		
		if(!in_array($type, self::$authorizedTypes)) {
			throw new \rk\exception('invalid type for attribute ' . $type);
		}
		
		$this->type = $type;
		
		foreach($params as $key => $value) {
			$this->setParam($key, $value);
		}
		$this->params = $params;
	}
	
	protected function setParam($name, $value) {
		switch($name) {
			case 'nullable' :
				$this->nullable = $value;
			break;
			
// 			case 'behaviour' :
// 				$class = '\rk\model\attribute\behaviour\\' . $value;
// 				$this->behaviour = new $class();
// 			break;
		}
	}
	
	public function getParam($name) {
		if(array_key_exists($name, $this->params)) {
			return $this->params[$name];
		}
		
		return null;
	}
	
	public function getName() {
		return $this->name;
	}
	public function getType() {
		return $this->type;
	}
	public function isNullable() {
		return $this->nullable;
	}
	public function isPrimary() {
		if(!empty($this->getParam('primary'))) {
			return true;
		}
		return false;
	}
	
	
	
	
// 	public function getBehaviour() {
// 		return $this->behaviour;
// 	}
	
	
// 	public function addReference(\rk\model\reference $reference)  {
// 		if(!empty($this->reference)) {
// 			throw new \rk\exception('multiple references on single attribute not allowed');
// 		}
// 		$this->reference = $reference;
// 	}
	
// 	public function getReference() {
// 		return $this->reference;
// 	}
// 	public function autoBuildReference() {
// 		return $this->getParam('autoBuildReference');
// 	}
	
}