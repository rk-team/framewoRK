<?php

namespace rk\form;

class filters extends \rk\form {
	
	/**
	 * @var \rk\db\criteriaSet
	 */
	protected $criteriasSet;
	
	/**
	 * @var \rk\db\table
	 */
	protected $table;

	protected $templateName = 'filters.php';
	
	protected $configureMethod = 'configureFilters';
	
	public function __construct($values = array(), $params = array()) {
		if(empty($params['table'])) {
			throw new \rk\exception('param table is required');
		}
		$this->table = $params['table'];
		$this->basedOnModel = get_class($this->table->getModel());
		
		if(empty($params['destination'])) {
			throw new \rk\exception('no destination given');
		}
		
		$this->id = \rk\manager::getUniqueId();
		$this->setTemplate($this->templateName);
		$this->name = str_replace(array('user\form\\', '\\'), array('', '-'), get_class($this));

		$this->criteriaSet = new \rk\db\criteriaSet();
		
		$this->setParams($params);

		$this->initFilters();
		
		$formClass = str_replace('user\model', 'user\form', $this->basedOnModel);
		$method = $this->configureMethod;
		if(method_exists($formClass, $method)) {
			$formClass::$method($this);
		}
	}
	
	/**
	 * Uses the table filters to build a filter set
	 */
	public function initFilters() {
		foreach($this->table->getFilters() as $oneFilter) {
			$createFilter = true;
			
// 			$modelName = $oneFilter->getModelName();
// 			if(!empty($modelName)) {
// 				$model = \rk\model\manager::get($modelName);
				
// 				if($model->getPK() == $oneFilter->getAttributeName()) {	// no filter for PKs
// 					$createFilter = false;
// 				}
// 			}
			
			if($createFilter) {
				list($fieldName, $type, $widgetParams) = $this->computeOptionsForTableFilter($oneFilter);
	
				switch($type) {
					case 'ignore':
					break;
					
					case 'hidden':
						$this->addWidgets(array(new \rk\form\widget\hidden($fieldName, $widgetParams)));
					break;
					
					case 'integer':
						$this->addWidgets(array(new \rk\form\widget\integer($fieldName, $widgetParams)));
					break;
											
					case 'datetime':
					case 'date':
						$this->addWidgets(array(new \rk\form\widget\date($fieldName, $widgetParams)));
					break;
					
					case 'textCombo':
					case 'richtext':
					case 'text':
						$this->addWidgets(array(new \rk\form\widget\text($fieldName, $widgetParams)));
					break;
	
					case 'boolean':
						$this->addWidgets(array(new \rk\form\widget\checkbox($fieldName, $widgetParams)));
					break;
					
					case 'enum':
					case 'select':
						$this->addWidgets(array(new \rk\form\widget\select($fieldName, $widgetParams)));
					break;
					
					default:
						throw new \rk\exception('unknown attribute type ' . $type);
				}
			}
		}
	}
	
	public function init() {}
	
	
	public function getCriteriaSet() {
		return $this->criteriaSet;
	}
	

	protected function computeOptionsForTableFilter(\rk\db\filter $filter) {
		
		$fieldName = $filter->getFieldIdentifier();
		
		if($this->getModel()->getAttribute($fieldName)) {
			// filter corresponds to a model attribute : we use the attribute to get options for the widget
			list($fieldName, $type, $widgetParams) = parent::computeOptionsForWidget($this->getModel()->getAttribute($fieldName));
			$widgetParams['required'] = false;
			if($type == 'select') {
				$widgetParams['showEmptyOption'] = true;
			}
		} else {
			// filter corresponds to a table filter : we use it as is
			$widgetParams = array(
				'required'	=> false,
				'label'		=> \rk\i18n::getORMKey($this->getModel()->getDbConnectorName(), $fieldName),
			);
			$type = \rk\helper::getClassFromNamespace($filter);
			
			$modelName = $filter->getModelName();
			$attributeName = $filter->getAttributeName();
			if(!empty($modelName) && !empty($attributeName)) {
				$model = \rk\model\manager::get($modelName);
				
				// no filter for PK of references
				$pk = $model->getPK();
				if($attributeName == $pk) {
					$type = 'ignore';
				}
				
				// no filter for fields with behaviours
				$behaviours = $model->getBehavioursForAttribute($attributeName);
				if(!empty($behaviours)) {
					$type = 'ignore';
				}
			}
		}
		
		$this->setOperatorsForWidget($type, $widgetParams);
		
		// var_dump($type, $widgetParams);
		return array($fieldName, $type, $widgetParams);
	}
	
	protected function setOperatorsForWidget($type, &$widgetParams) {
	
		if(empty($widgetParams['operator'])) {
			if($type == 'text' || $type == 'richtext') {
				$widgetParams['operator'] = \rk\db\builder::OPERATOR_ILIKE;
			} else {
				$widgetParams['operator'] = \rk\db\builder::OPERATOR_EQUAL;
			}
		}
	}
	
	public function validate(array $params) {
		$isValid = parent::validate($params);
		
		// for filters, we do want a value to appear if it is null or '' 
		foreach($this->validatedValues as $key => $value) {
			if($value === '' || $value === null) {
				unset($this->validatedValues[$key]);
			}
		}
		
		return $isValid;
	}
	
	
	
	public function handleCriterias(array $formValues = array(), array $options = array()) {
		
		if(!empty($formValues[$this->name])) {
			if(!empty($formValues['_rkFormId'])) {
				// retrieves the id of the form before it was submitted (ensure we do not generate a new id each time when the form is ajaxified)
				$this->id = $formValues['_rkFormId'];
			}

			$this->validate($formValues[$this->name]);
			foreach($this->validatedValues as $key => $value) {
				$widget = $this->getWidget($key);
				$operator = $widget->getParam('operator');
				if($operator == \rk\db\builder::OPERATOR_ILIKE) {
					$value = '%' . $value . '%';
				}				
				$this->criteriaSet->add($key, $value, $operator);
			}
		} else {
			if(!empty($options['defaults'])) {
				$this->criteriaSet->add($options['defaults']);
				
				foreach($options['defaults'] as $key => $value) {
					$widget = $this->getWidget($key);
					if(!empty($widget) && !is_array($value) && !is_object($value)) {
						$widget->setValue($value);
					}
				}
			}
		}
		return $this->isValid;
	}

}