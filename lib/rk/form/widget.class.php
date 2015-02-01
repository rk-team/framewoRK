<?php

namespace rk\form;

abstract class widget {

	protected
		$id,
		$baseName,			// attribut "name" du widget "nu" (ex: login)
		$name,				// attribut "name" complet du widget dans un form (ex: form[login]) 
		$label,
		$required = false,
		$showRequiredMark = true,	// wether to display "*" when required or not
		$disabled = false,
		$value,
		$isValid = true,
		$errors = array(),
		$requiredParams = array(),
		$params = array(),
		$placeholder = '';
	
	
	public function __construct($name, array $params = array()) {
		$this->baseName = $name;
		$this->setName($name);
		
		$this->label = $this->name;
		if(array_key_exists('label', $params)) {
			$this->label = $params['label'];
		}
		
		if(!empty($this->requiredParams)) {
			foreach($this->requiredParams as $oneReqParam) {
				if(!array_key_exists($oneReqParam, $params)) {
					throw new \rk\exception('missing required param ' . $oneReqParam);
				}
			}
		}
		
		$this->setParams($params);
		$this->id = \rk\manager::getUniqueId();
	}
	
	public function setParams(array $params) {
		foreach($params as $key => $value) {
			$this->setParam($key, $value);
		}
	}
	
	public function setParam($name, $value) {
		$this->params[$name] = $value;
		if($name == 'label') {
			$this->label = $value;
		} elseif($name == 'disabled') {
			$this->disabled = $value;
		} elseif($name == 'required') {
			$this->required = $value;
		} elseif($name == 'value') {
			$this->setValue($value);
		} elseif($name == 'placeholder') {
			$this->placeholder = $value;
		} elseif($name == 'showRequiredMark') {
			$this->showRequiredMark = $value;
		}
	}
	
	public function getParam($paramName) {
		if(!array_key_exists($paramName, $this->params)) {
			return false;
		}
		
		return $this->params[$paramName];
	}
	
	public function getParams() {
		return $this->params;
	}
	
	/**
	 * @desc return all params of widget, even if not in $this->params (like $this->value, $this->label, ...)
	 */
	public function getAllParams() {
		$params = $this->params;
		
		$params['label'] = $this->label;
		$params['disabled'] = $this->disabled;
		$params['required'] = $this->required;
		$params['placeholder'] = $this->placeholder;
		$params['showRequiredMark'] = $this->showRequiredMark;
		$params['value'] = $this->value;
		
		return $params;
	}
	
	public function getBaseName() {
		return $this->baseName;
	}
	
	public function isRequired() {
		return $this->required;
	}
	
	
	/**
	 * @desc returns the value of a widget, as it will be stored in DB
	 */
	public function getValue() {
		return $this->value;
	}
	
	/**
	 * @desc returns the value of a widget, as it has to be displayed
	 */
	public function getDisplayValue() {
		return $this->value;
	}
	
	public function setValue($value) {
		$this->value = $value;
	}
	
	public function getName() {
		return $this->name;
	}
	public function setName($name) {
		$this->name = $name;
	}
	
	/**
	 * @desc formatte un valeur retournée par un form (boolean, date, ...)
	 */	
	public function getFormattedValue() {
		$value = null;
		if (!empty($this->value)) {
			$value = $this->value;
		}
		return $value;
	}
		
	public function getOutput() {
		return $this->getLabelOutput() . ' ' . $this->getWidgetOutput() . ' ' . $this->getErrorOutput();
	}
	
	public function getErrorOutput() {
		$return = '';
		foreach($this->errors as $oneError) {
			$return .= '<span class="error">' . i18n($oneError, array(), array('htmlentities' => true)) . '</span>';
		}
		return $return;
	}
	public function getErrors() {
		return $this->errors;
	}
	
	public function getLabel() {
		return $this->label;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getLabelOutput() {
		$return = '';
				
		if(!empty($this->label)) {
			$return = '<label for="' . $this->id . '">' . i18n($this->label, array(), array('htmlentities' => true));
			if(!empty($this->required) && $this->showRequiredMark) {
				$return .= ' *';
			}
			$return .= '</label>';
		}
		
		return $return;
	}
		
	public function getClassAttribute($extraClasses = '') {
		$classes = $this->getClass($extraClasses);
		if(!empty($classes)) {
			return ' class="' . $classes . '" ';
		}
		
		return '';
	}
	
	public function getClass($extraClasses = '') {
		if(!array_key_exists('inputClass', $this->params)) {
			$inputClass = '';
		} else {
			$inputClass = $this->params['inputClass'];
		}
		
		if(!empty($extraClasses)) {
			$inputClass .= ' ' . $extraClasses;
		}
		
		return $inputClass;
	}
		
	public function getDisabledAttribute() {
		if(!empty($this->disabled)) {
			return ' disabled="disabled" ';
		}
		
		return '';
	}
	
	public function getValueAttribute() {
		if(!empty($this->getValue())) {
			return ' value="' . $this->getValue() . '" ';
		}
		
		return '';
	}
	
	public function getPlaceholderAttribute() {
		if(!empty($this->placeholder)) {
			return ' placeholder="' . $this->placeholder . '" ';
		}
		
		return '';
	}
	
	public function __toString() {
		return $this->getOutput();
	}
	
	public function checkValidity($value) {
		if($this->required) {
			if($value == '') {
				$this->addError('form.error.required');
				return false;
			}
		}
		
		return true;
	}
	
	public function validate($value) {
		$this->setValue($value);
		
		$this->isValid = $this->checkValidity($value);

		return $this->isValid();
	}
	
	public function addError($msg) {
		$this->errors[] = $msg;
	}
	public function removeErrors() {
		$this->errors = array();
	}
	
	public function hasError() {
		if(!empty($this->errors)) {
			return true;
		}
		
		return false;
	}
	
	
	public function isValid() {
		return $this->isValid;
	}

	
	public static function buildLabelForAttribute(\rk\model\attribute $attribute, \rk\model $model = null) {
		$return = '';

		if(!empty($model)) {
			$return .= \rk\i18n::getORMKey($model->getDBConnectorName(), $model->getTableName(), $attribute->getName());
		} else {
			$return .= $attribute->getName();
		}
		
		
		return $return;
	}
	
	
	public function getParamsForTpl() {
		$tplParams = array(
			'id' 				=> $this->id,
			'name' 				=> $this->getName(),
			'valueAttribute' 	=> $this->getValueAttribute(),
			'value' 			=> $this->getValue(),
			'classAttribute' 	=> $this->getClassAttribute(),
			'class' 			=> $this->getClass(),
			'disabledAttribute' => $this->getDisabledAttribute(),			
			'disabled'			=> $this->disabled,
				
			'placeholderAttribute' => $this->getPlaceHolderAttribute(),			
			'placeholder'		=> $this->placeholder,
		);
		
		return $tplParams;
	}
	
	
	public function getWidgetOutput() {
		$tplParams = $this->getParamsForTpl();
		
		if(empty($this->templateName)) {
			$tplName = \rk\helper::getClassFromNamespace($this) . '.php';
		} else {
			$tplName = $this->templateName;
		}
		
		$tplFile = \rk\manager::getFormWidgetTemplatePath($tplName);
		$return = \rk\helper\output::getOutput($tplFile, $tplParams);
		
		return $return;
	}
	
}