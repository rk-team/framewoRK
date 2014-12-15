<?php

namespace rk\scripts\tools\createClass;

abstract class parser {
	
	public $connector = null;
	
	public function __construct ($connector) {
		$this->connector = $connector;
	}
	
	public abstract function getTables ();
	public abstract function getFieldsDesc ($oneTable);
}