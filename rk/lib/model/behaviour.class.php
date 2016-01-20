<?php

namespace rk\model;

abstract class behaviour {
	
	private static $authorizedTriggers = array(
		'insert', 'update', 'delete'
	);
	
	protected $triggers; 	// array that might contain 'insert', 'update' and/or 'delete'
		
	protected $params;
	
	final public function __construct(array $params = array()) {
		$this->params = $params;
		
		// ensure defined triggers are authorized
		$this->checkTriggers();
	}
	
	final private function checkTriggers() {
		foreach($this->triggers as $oneTrigger) {
			if(!in_array($oneTrigger, self::$authorizedTriggers)) {
				throw new \rk\exception('invalid trigger', $oneTrigger);
			}
		}
	}
	
	public function isFormWidget() {
		return $this->formWidget;
	}
	public function getTriggers() {
		return $this->triggers;
	}
	
	public function getParams() {
		return $this->params;
	}
}