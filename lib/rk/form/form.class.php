<?php

namespace rk;

class form {

	protected
		$hasBeenSubmitted = false,
		$noEdit = false,		// weither to display widgets (if false) or only their values (if true)
		$isValid = true, 
		$id,
		$className = 'form',
		$destination,
		$method = 'POST',
		$basedOnModel = null,		// contient le nom du modèle si c'est un "auto form" basé sur un modèle
		$templateName = 'table.php',	// name of the template 
		$templatePath,				// full path vers le template
		$name, 						// identifiant "human readable" du form, utilisé comme préfixe pour le nom de tous ses widgets
		$widgets = array(),			// liste de tous les widgets disponibles pour ce form
		$JSParams = array(),		// associative array of params to be given to JS widget
		$disabled = false,
		$subForms = array(),
		$isSubForm = false,
		$successMessage,
		$originalValues = array(),	// values given in constructor
		$validatedValues = array(),	// values retrieved from web request and valdiated by widgets
		$errors = array(),
		$params = array(),
		$object,
		$submitName = 'form.submit_name'; //Default value for submit button name
				
	public function __construct($values = array(), $params = array()) {
		
		$modelName = get_class($this);
		if (strpos($modelName, 'user\form') !== false) {
			$modelName = str_replace('user\form', 'user\model', $modelName);
			if (class_exists($modelName)) {
				$this->basedOnModel = $modelName;
			}
		}		
		
		$this->id = manager::getUniqueId();
		
		// default template is the "table" one
		$this->setTemplate($this->templateName);		
		
		// default name is based on class name
		$this->name = str_replace(array('user\form\\', '\\'), array('', '-'), get_class($this));
				
		// default URL is current request URL
		$this->destination = \rk\manager::getRequestURL();
		
		$this->setParams($params);
		
		if(!empty($this->basedOnModel)) {
			$this->initWidgets($values);
		}		
		
		$this->originalValues = $values;
		$this->setValues($values);
		
		$this->init();		
	}

	/**
	 * @desc function called by construct to initialize the form
	 */
	protected function init() {}
	
	/**
	 * @return \rk\model
	 */
	public function getModel() {
		if(!empty($this->basedOnModel)) {
			return \rk\model\manager::get($this->basedOnModel);
		}
		return false;
	}
	
	public function isNew() {
		if(!empty($this->basedOnModel)) {
			$PK = $this->getModel()->getPK();
			if(empty($this->originalValues[$PK])) {
				return true;
			}
		}
		
		return false;
	}
	
	public function setParams(array $params) {
		foreach($params as $key => $value) {
			$this->setParam($key, $value);
		}
	}
	
	public function setParam($name, $value) {
		switch($name) {
			case 'template':
				$this->setTemplate($value);
			break;
			
			case 'name':
				$this->name = $value;
				$this->updateWidgetsNames();
			break;
			
			case 'className':
				$this->className = $value;
			break;
			
			case 'destination':
				$this->destination = urlFor($value);
			break;
			
			case 'method':
				$this->method = $value;
			break;
			
			case 'disabled':
				$this->disabled = $value;
			break;
			
			case 'submitName':
				$this->submitName = $value;
			break;
			
			default:
				$this->params[$name] = $value;
			break;
		}
	}
	
	public function hideRequiredMarks() {
		foreach($this->widgets as &$oneWidget) {
			$oneWidget->setParam('showRequiredMark', false);
		}
	}
	
	protected function markAsSubForm($name) {
		$this->isSubForm = true;
		$this->setTemplate('subForm.table.php');
		$this->setParam('name', $this->name . '_' . $name);
	}
	
	public function markAsNoEdit() {
		$this->setTemplate('tableNoEdit.php');
		$this->hideRequiredMarks();
		$this->noEdit = true;
		
		if(method_exists($this, 'noEditCustomisation')) {
			// if a noEditCustomisation method was defined on the form, we call it
			$this->noEditCustomisation();
		}
	}
	
	public function addSubForm($name, \rk\form $form) {
		$form->markAsSubForm($name);
		$this->subForms[$name] = $form;
	}
	public function getSubForms() {
		return $this->subForms;
	}
	public function getSubForm($name) {
		if(!empty($this->subForms[$name])) {
			return $this->subForms[$name];
		}
		
		return false;
	}
	
	protected function computeOptionsForWidget(\rk\model\attribute $oneAttribute, \rk\model $model = null) {
		if(is_null($model)) {
			$model = $this->getModel();
		}
		$fieldName = $oneAttribute->getName();
		
		$label = \rk\form\widget::buildLabelForAttribute($oneAttribute, $model);

		$widgetParams = array(
			'required' => !$oneAttribute->isNullable(),
			'label'		=> $label
		);

		$type = $oneAttribute->getType();
		
		if($oneAttribute->getName() == $this->getModel()->getPK()) {
			$type = 'hidden';
			$widgetParams['required'] = false;
		}
		
		$references = $this->getModel()->getReferencesForAttribute($oneAttribute);
		foreach($references as $oneRef) {
			if(!$oneRef->hasMany()) {
				$widgetParams['label'] = \rk\i18n::getORMKey($this->getModel()->getDbConnectorName(), $this->getModel()->getTableName(), $oneAttribute->getName());
				$widgetParams['reference'] = $oneRef;
				$type = 'select';
			}
		}
		
		$behaviours = $this->getModel()->getBehavioursForAttribute($oneAttribute);
		if(!empty($behaviours)) {
			$type = 'ignore';
		}

		if($type == 'enum') {
			$type = 'select';
			$widgetParams['options'] = $oneAttribute->getParam('enumValues');			
		}
		
		return array($fieldName, $type, $widgetParams);
	}
	
	/**
	 * Used to init standard forms (designed to create/update objects)
	 * @throws \rk\exception
	 */
	public function initWidgets($values) {
		foreach($this->getModel()->getAttributes() as $oneAttribute) {
			$this->initWidget($oneAttribute);
		}
	}
	
	public function initWidget(\rk\model\attribute $attribute, \rk\model $model = null) {
		list($fieldName, $type, $widgetParams) = $this->computeOptionsForWidget($attribute, $model);
			
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
			
			case 'text':
				$this->addWidgets(array(new \rk\form\widget\text($fieldName, $widgetParams)));
			break;

			case 'richtext':
				$this->addWidgets(array(new \rk\form\widget\textarea($fieldName, $widgetParams)));
			break;
			
			case 'boolean':
				$this->addWidgets(array(new \rk\form\widget\checkbox($fieldName, $widgetParams)));
			break;
			
			case 'select':
				$this->addWidgets(array(new \rk\form\widget\select($fieldName, $widgetParams)));
			break;
			
			default:
				throw new \rk\exception('unknown attribute type ' . $type);
		}
	}
	
	/**
	 * 
	 * @param string $template : path relatif depuis ressources/formTemplates
	 * @throws \rk\exception
	 */
	public function setTemplate($template) {
		$this->templatePath = \rk\manager::getFormTemplatePath($template);
		if(!file_exists($this->templatePath)) {
			throw new \rk\exception('cant find form template', array('templatePath' => $this->templatePath));
		}
	}
	
	public function addError($error) {
		$this->errors[] = $error;
	}
	public function setSuccessMessage($message = 'form.success') {
		$this->successMessage = $message;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getWidgets() {
		return $this->widgets;
	}

	/**
	 * @param string $widgetName
	 * @return \rk\form\widget
	 */
	public function getWidget($widgetName) {
		if(!empty($this->widgets[$widgetName])) {
			return $this->widgets[$widgetName];
		}
		return false;
	}
	
	public function getValues() {
		$return = array();
		foreach($this->widgets as $name => $widget) {
			$return[$name] = $widget->getValue();
		}
		
		return $return;
	}
	public function getValue($name) {
		if(empty($this->widgets[$name])) {
			throw new \rk\exception('unknown widget ' . $name);
		}
		
		return $this->widgets[$name]->getValue();
	}
	
	
	public function resetValues() {
		foreach($this->widgets as $key => $value) {
			if(!empty($this->originalValues[$key])) {
				$this->widgets[$key]->setValue($this->originalValues[$key]);
			} else {
				$this->widgets[$key]->setValue(null);
			}
		}
	}
	
	public function setValues($values) {
		foreach($values as $key => $value) {
			if(!empty($this->widgets[$key])) {
				$this->widgets[$key]->setValue($value);
			}
		}
	}
	
	public function setValue($widgetName, $value) {
		$this->widgets[$widgetName]->setValue($value);
		
// 		var_dump('setValue', $this->getValues());
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function setDestination($destination) {
		$this->destination = $destination;
	}
	public function getDestination() {
		return $this->destination;
	}
	
	public function getMethod() {
		return $this->method;
	}

	
	public function setDefaultParam($key, $value) {
		if(!array_key_exists($key, $this->params)) {
			$this->params[$key] = $value;
		}
	}
	public function setDefaultParams($defaults) {
		foreach($defaults as $key => $value) {
			$this->setDefaultParam($key, $value);
		}
	}
	
	public function validate(array $params) {		
		$this->isValid = true;
		
		// validate each widgets
		foreach($this->widgets as $oneWidget) {
			$widgetValue = null;
			if(array_key_exists($oneWidget->getBaseName(), $params)) {
				$widgetValue = $params[$oneWidget->getBaseName()];
			}
		
			$validWidget = $oneWidget->validate($widgetValue);
			if(!$validWidget) {
				$this->isValid = false;
			} else {
				$this->validatedValues[$oneWidget->getBaseName()] = $oneWidget->getFormattedValue();
			}
		}
		return $this->isValid;
	}
	
	/**
	 * @return \rk\object
	 */
	public function getObject() {
		return $this->object;
	}

	public function save() {
		// save object in DB if based on model
		if(!empty($this->basedOnModel)) {
			$DBError = false;
			$this->object = $this->getModel()->getObject($this->validatedValues);
			
			try {
				$this->object->save();
			} catch(\rk\exception\db\uniqueViolation $e) {
				$widgetName = $e->getMessage();
				if($widgetName === 'PRIMARY') {
					$widgetName = $this->getModel()->getPK();
				}
				$this->widgets[$widgetName]->addError('form.error.already_exists');
				$this->removeUploadedFiles();
				$DBError = true;
			} catch(\rk\exception\db\FKViolation $e) {
				if(!empty($this->widgets[$e->getMessage()])) {
					$this->widgets[$e->getMessage()]->addError('form.error.invalid_value');
				} else {
					$this->addError(i18n('form.error.required_field', array('field' => $e->getMessage())));
				}
				$this->removeUploadedFiles();
				$DBError = true;
			}
			if(empty($DBError)) {
				$this->setSuccessMessage();
				return true;
			}
		}
		
		return false;
	}
	
	public function handleSubmit(array $formValues = array(), array $options = array()) {
		
		if(!empty($formValues[$this->name])) {
			if(!empty($formValues['_rkFormId'])) {
				// retrieves the id of the form before it was submitted (ensure we do not generate a new id each time when the form is ajaxified)
				$this->id = $formValues['_rkFormId'];
			}
			
			$this->hasBeenSubmitted = true;

					
// 			if(method_exists($this, 'preValidate')) {
// 				$this->preValidate($formValues[$this->name]);
// 			}
			
			$this->isValid = $this->validate($formValues[$this->name]);
			
			if(!empty($this->subForms)) {
				foreach($this->subForms as $oneSubForm) {
					if(!$oneSubForm->handleSubmit($formValues)) {
						$this->isValid = false;
					}
				}
			}
			
			if($this->isValid && !$this->isSubForm) {	// subForm do not call handleSave directly. 
				// Only the main form calls handlSave, both for him and his subforms, and only if everything is valid
				if(!empty($this->basedOnModel)) {
					
					$conn = \rk\db\manager::get($this->getModel()->getDbConnectorName());
					
					try {
						$conn->beginTransaction();
						
						$this->handleSave();
						
						if(!empty($this->subForms)) {
							foreach($this->subForms as $subFormName => $oneSubForm) {
								$oneSubForm->handleSave();
							}
						}
						$conn->commit();
					} catch(\Exception $e) {
						$conn->rollBack();
						throw $e;
					}
				}
			}
		}
		
		return $this->isValid;
	}
	
	public function updateValidatedValue($name, $value) {
		$this->validatedValues[$name] = $value;
	}
	
	protected function handleSave() {

		if(empty($this->validatedValues[$this->getModel()->getPK()])) {
			$isInsert = true;
		}
		
		$save = $this->save();
		if(!$save) {
			// erreur SQL lors du save
			$this->isValid = false;
		} elseif(!empty($isInsert)) {
			$this->resetValues();
		}
		
		if($this->isValid()) {
			// if subForms's models have references to the main form's model, we update the subForm's validatedValues
			if(!empty($this->subForms)) {
				foreach($this->subForms as $oneSub) {
					$subFormValues = array();
					$subModel = $oneSub->getModel();
					if($subModel) {
						$refs = $subModel->getReferences();
						foreach($refs as $oneRef) {
							if($oneRef->getReferencedModel() == $this->getModel()) {
								$field = $oneRef->getReferencedField();
								$obj = $this->getObject();
								$subFormValues[$oneRef->getReferencingField()] = $obj[$field];
								$oneSub->updateValidatedValue($oneRef->getReferencingField(), $obj[$field]);
							}
						}
					}
				}
			}
		}
	}
	
	
	/**
	 * @desc remove file uploaded through the form
	 * @note should only be called if a save() has failed
	 */
	protected function removeUploadedFiles() {
		foreach($this->widgets as $oneWidget) {
			if($oneWidget instanceof \rk\form\widget\file) {
				$oneWidget->removeUploadedFile();
			}
		}
	}

	protected function updateWidgetsNames() {
		foreach($this->widgets as $baseName => $widget) {
			$widgetName = $this->name . '[' . $widget->getBaseName() . ']';
			$this->widgets[$baseName]->setName($widgetName);
		}
	}

	
	public function addWidgets($widgets) {
		if(!is_array($widgets)) {
			$widgets = array($widgets);
		}
		
		foreach($widgets as $oneWidget) {
			if(!$oneWidget instanceof \rk\form\widget) {
				throw new \rk\exception\system('widget is not \rk\form\widget');
			}
			$widgetName = $this->computeWidgetName($oneWidget->getName());
			$oneWidget->setName($widgetName);
			$this->widgets[$oneWidget->getBaseName()] = $oneWidget;
		}
	}
	
	protected function computeWidgetName($widgetName) {
		return $this->name . '[' . $widgetName . ']';
	}
	
	public function changeWidgetType($name, $newType, $newParams = array()) {
		if(!empty($this->widgets[$name])) {
			$currentParams = $this->widgets[$name]->getAllParams();
			$params = array_merge($currentParams, $newParams);
			
			if(class_exists($newType)) {
				$className = $newType;
			} else {
				$className = '\rk\form\widget\\' . $newType;
				if(!class_exists($className)) {
					throw new \rk\exception('type not found ' . $newType);
				}
			}
			
			$newWidget = new $className($name, $params);
			
			$widgetName = $this->computeWidgetName($newWidget->getName());
			$newWidget->setName($widgetName);
			$this->widgets[$newWidget->getBaseName()] = $newWidget;
		}
	}
	
	
	public function orderWidgets (array $order) {
		$orderedWidgets = array();
		
		foreach($order as $oneName) {
			if(empty($this->widgets[$oneName])) {
				throw new \rk\exception('unknown widget ' . $oneName);
			}
			$orderedWidgets[$oneName] = $this->widgets[$oneName];
		}
		
		$this->widgets = $orderedWidgets;
		
// 		var_dump($this->widgets);
	}
	
	
	public function retrieveErrorsFromHiddenWidgets() {
		foreach($this->getWidgets() as $oneWidget) {
			if($oneWidget instanceof \rk\form\widget\hidden) {
				$widgetErrors = $oneWidget->getErrors();
				foreach($widgetErrors as $oneError) {
					$this->errors[] = i18n($oneWidget->getLabel()) . ' : ' . i18n($oneError);
				}
			}
		}
	}
	
	
	public function getOutput() {
		
		$this->retrieveErrorsFromHiddenWidgets();
		
		$hasFileWidget = false;
		foreach($this->widgets as $one) {
			if($one instanceof \rk\form\widget\file) {
				$hasFileWidget = true;
			}
			if($this->disabled) {
				$one->setParam('disabled', true);
			}
		}
		
		$subFormsOutput = '';
		foreach($this->subForms as $oneSubForm) {
			$subFormsOutput .= $oneSubForm->getOutput();
		}
		
		$JSParams = array();
		if(empty($this->params['noAjax'])) {
			$JSParams = $this->getJSONScriptParams();
		}
		
		$tplParams = array(
			'hasFiles'		=> $hasFileWidget,
			'destination'	=> $this->getDestination(),
			'widgets'		=> $this->getWidgets(),
			'subFormsOutput'	=> $subFormsOutput,
			'method'		=> $this->getMethod(),
			'formId'		=> $this->getId(),
			'className'		=> $this->className,
			'successMessage'	=> $this->successMessage,
			'errors'		=> $this->errors,
			'params'		=> $this->params,
			'JSONParams'	=> $JSParams,
			'submitName'	=> $this->submitName,
			'dataType'		=> $this->name
		);
		
		$return = \rk\helper\output::getOutput($this->templatePath, $tplParams);
				
		return $return;
	}

	
	protected function getJSONScriptParams() {
		
		$this->JSParams['sId'] = $this->id;
		if($this->isValid && $this->hasBeenSubmitted) {
			$this->JSParams['bSuccess'] = true;
		}
		
		if(!empty($this->params['updateContainer'])) {
			$this->JSParams['sUpdateContainer'] = $this->params['updateContainer'];
		}
				
		return json_encode($this->JSParams);
	}
	
	public function hasBeenSubmitted() {
		return $this->hasBeenSubmitted;
	}
	
	public function isValid() {
		return $this->isValid;
	}
	
	public function __toString() {
		return $this->getOutput();
	}
	
	
	public static function configureFilters(\rk\form $form) {
	}
	
	public static function configureAdvancedFilters(\rk\form $form) {
		self::configureFilters($form);
	}
	
}