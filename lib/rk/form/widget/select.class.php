<?php

namespace rk\form\widget;

class select extends \rk\form\widget {
	
	protected $showEmptyOption = false;
	
	public function getParamsForTpl() {
		$tplParams = parent::getParamsForTpl();
		
		$this->setOptions();
		$tplParams['options'] = $this->params['options'];

		return $tplParams;
	}
	
	public function __construct($name, array $params = array()) {
		parent::__construct($name, $params);
		if(!empty($params['showEmptyOption'])) {
			$this->setShowEmptyOption($params['showEmptyOption']);
		}
	}

	public function setShowEmptyOption($value) {
		$this->showEmptyOption = $value;
	}
	
	public function setOptions() {
		
		$options = $this->getParam('options');
		if(empty($options)) {
			$options = array();
			
			$reference = $this->getParam('reference');
			if(!empty($reference)) {
				$field = $reference->getDisplayField();
				if(!$reference->hasMany()) {
					$table = $reference->getReferencedModel()->getTable();
					
					$options = $table->getSelectOptions(array('fieldName' => $field));
				}
			}
		}
		
		if(!empty($this->showEmptyOption)) {
			$newOptions = array(
				'' => ''
			);
			foreach($options as $key => $value) {
				$newOptions[$key] = $value;
			}
			$options = $newOptions;
		}

		$this->setParam('options', $options);
	}
	
	public function getDisplayValue() {
		$reference = $this->getParam('reference');
		if(!empty($reference)) {
			$field = $reference->getDisplayField();
			
			if(!$reference->hasMany()) {
				$table = $reference->getReferencedModel()->getTable();
				
				$options = $table->getSelectOptions(array('fieldName' => $field), array($reference->getReferencedModel()->getPK() => $this->value));
				if(!empty($options) && !empty($options[$this->value])) {
					return $options[$this->value];
				}
			}
		}
		return $this->value;
	}


}