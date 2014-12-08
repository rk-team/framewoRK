<?php

namespace rk\db;

abstract class connector {
	
	protected
		$name,			// name of the connector in config
		$handle,		// connection handle
		$type;			// type (postgre, mysql, ...)
	
	abstract public function __construct(array $configParams);
	
	abstract public function connect();
	
	abstract public function query($query, array $binds = array());
	
	public function getType() {
		return $this->type;
	}
	
	abstract public function beginTransaction();
	abstract public function commit();
	abstract public function rollBack();
}