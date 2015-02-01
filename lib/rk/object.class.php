<?php 

namespace rk;

abstract class object implements \ArrayAccess, \Iterator {
	
// 	protected static $PK;	// used to store the primary key name
	
	protected $modelName;
	
	
	protected
		$data = array(),
		$attributes = array(),
		$relatedData = array();
		
	public function __construct(array $data = array()) {
		$this->init();
		$this->setData($data);
	}
	
	protected function init() {
		$modelName = get_class($this);
		$this->modelName = str_replace('user\object', 'user\model', $modelName);
		
		$this->attributes = $this->getModel()->getAttributes();
	}
	
	/** accès en tableau **/
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			throw new \rk\Exception('invalid offset', array('offset' => $offset));
		} else {
			$this->addData($offset, $value);
		}
	}
	/** accès en tableau **/
	public function offsetExists($offset) {
		return isset($this->data[$offset]) || isset($this->relatedData[$offset]);
	}
	/** accès en tableau **/
	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}
	/** accès en tableau **/
	public function offsetGet($offset) {
		$modelAttributes = $this->getModel()->getAttributes();
	
		if(!empty($modelAttributes[$offset])) {
			return isset($this->data[$offset]) ? $this->data[$offset] : null;
		}
	
		// pas d'attribut existant : on cherche dans related
		return isset($this->relatedData[$offset]) ? $this->relatedData[$offset] : null;
	}
	
	
	
	/** Iterator **/
	public function rewind() {
		reset($this->data);
	}
	public function current() {
		$var = current($this->data);
		return $var;
	}
	public function key() {
		$var = key($this->data);
		return $var;
	}
	public function next() {
		$var = next($this->data);
		return $var;
	}
	public function valid() {
		$key = key($this->data);
		$var = ($key !== NULL && $key !== FALSE);
		return $var;
	}
	
	
	
	public function getData() {
		return $this->data;
	}
	
	
	protected function getModel() {
		return \rk\model\manager::get($this->modelName);
	}
	
	/**
	 * @return \rk\form
	 */
	public function getForm() {
		$formClass = str_replace('user\model', 'user\form', $this->modelName);
		return new $formClass($this->data);
	}
	
	
	public function getPK() {
		return $this->getModel()->getPK();
	}
	
	protected function addData($offset, $value) {
	
		if(!empty($this->attributes[$offset])) {
			$this->data[$offset] = $value;
		} else {
			$this->relatedData[$offset] = $value;
		}
	}
	
	
	public function setData(array $data) {
		foreach($data as $oneAttrName => $oneModelAttr) {
			$this->addData($oneAttrName, $oneModelAttr);
		}
	}
		
	public function save() {
		$table = $this->getModel()->getTable();
		
		$modelAttributes = $this->getModel()->getAttributes();
		
		if(!empty($this->data[$this->getPK()])) {
			$mode = 'update';
		} else {
			$mode = 'insert';
		}
		
		if(method_exists($this, 'preSave')) {
			$this->preSave();
		}
		
		$values = array();
		foreach($modelAttributes as $name => $one) {
			if(array_key_exists($name, $this->data)) {
				$values[$name] = $this->data[$name];
			}
			$behaviours = $this->getModel()->getBehavioursForAttribute($one);
			if(!empty($behaviours)) {
				foreach($behaviours as $one) {
					if(in_array($mode, $one->getTriggers())) {
						$values[$name] = $one->getValue();
					}
				}
			}
		}
			
		$pk = $this->getModel()->getPK();
		
		if($mode == 'update') {
			$table->update($values);
		} else {
			$this[$pk] = $table->insert($values);
		}
		
		if(method_exists($this, 'postSave')) {
			$this->postSave();
		}
		
		$values = $table->getByPk($this[$pk]);
		$this->setData($values);
	}
	
	
	public function delete() {
		if(empty($this->data[$this->getPK()])) {
			throw new \rk\exception('can not delete object without value for its PK');
		}
		$table = $this->getModel()->getTable();
		
		$continue = true;
		if(method_exists($this, 'preDelete')) {
			$continue = $this->preDelete();
		}
		
		if($continue) {
			$table->delete(array($this->getPK() => $this->data[$this->getPK()]));
			
			if(method_exists($this, 'postDelete')) {
				$this->postDelete();
			}
		}
	}
}