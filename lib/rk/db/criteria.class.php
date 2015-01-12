<?php

namespace rk\db;

class criteria {
	
	protected
		$name,
		$value,
		$operator = \rk\db\builder::OPERATOR_EQUAL,
		$params;
	
	public function __construct($name, $value, $operator = null, array $params = array()) {
		if($value instanceof \rk\date) {
			$value = $value->dbFormat();
		}
		if (is_object($value)) {
			throw new \rk\exception('invalid param', array('value' => $value));
		}
		
		$this->name = $name;
		$this->value = $value;
		
		if(!empty($operator)) {
			$this->operator = $operator;
		}
		$this->params = $params;
	}

	public function getName() {
		return $this->name;
	}

	public function getValue() {
		return $this->value;
	}

	public function getOperator() {
		return $this->operator;
	}

	public function getParams() {
		return $this->params;
	}
}