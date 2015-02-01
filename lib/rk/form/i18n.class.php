<?php

namespace rk\form;

class i18n extends \rk\form {

	public function initWidgets($values) {
		parent::initWidgets($values);

		if($this->getModel()->hasReference('i18n')) {
			$i18nRef = $this->getModel()->getReference('i18n');
			$i18nModel = $i18nRef->getReferencedModel();
			
			$i18nObjects = array();
			if(!empty($values[$i18nRef->getReferencedTableAlias()])) {
				foreach($values[$i18nRef->getReferencedTableAlias()] as $one) {
					$i18nObjects[$one[$i18nRef->getLanguageField()]] = $one;
				}
			}

			$languages = \rk\manager::getConfigParam('project.languages');
			foreach($languages as $one) {
				$params = array(
					'basedOnModel'	=> $i18nRef->getReferencedModelName(),
					'name'			=> 'i18n_' . $one,
					'required'		=> false,
				);
				if(!empty($i18nObjects[$one])) {
					$i18nForm = new \rk\form\subForm($i18nObjects[$one], $params);
				} else {
					$i18nForm = new \rk\form\subForm(array(), $params);
				}

				
				$languageField = $i18nRef->getLanguageField();
				$i18nForm->getWidget($languageField)->setParam('value', $one);
				$i18nForm->changeWidgetType($languageField, 'hidden');
				
				$i18nForm->setParam('formTitle', $one);
				
				// hide the FK
				$refs = $i18nModel->getReferences();
				foreach($refs as $oneRef) {
					$i18nForm->getWidget($oneRef->getReferencingField())->setParam('required', false);
					$i18nForm->changeWidgetType($oneRef->getReferencingField(), 'hidden');
				}
				
				$this->addSubForm($i18nForm);
			}
			
			
		}
	}
	
	
	public function handleSubmit(array $formValues = array(), array $options = array()) {
		
		if(!empty($formValues[$this->name])) {
			if(!empty($formValues['_rkFormId'])) {
				// retrieves the id of the form before it was submitted (ensure we do not generate a new id each time when the form is ajaxified)
				$this->id = $formValues['_rkFormId'];
			}
			
			$this->hasBeenSubmitted = true;

			$atLeastOneTranslationValid = false;
			
			$this->isValid = $this->validate($formValues[$this->name]);
			
			if(!empty($this->subForms)) {
				foreach($this->subForms as $oneSubForm) {
					$oneSubForm->handleSubmit($formValues);
					if($oneSubForm->saveNeeded()) {
						if(!$oneSubForm->isValid()) {
							$this->isValid = false;
						} else {
							$atLeastOneTranslationValid = true;
						}
					} else {
						$oneSubForm->removeWidgetsError();
					}
				}
			}
			
			if(!$atLeastOneTranslationValid) {
				$this->addError(i18n('form.error.no_translation_given_for_i18n'));
				$this->isValid = false;
				return $this->isValid;
			}
			
			if($this->isValid && !$this instanceof \rk\form\subForm) {	// subForm do not call handleSave directly. 
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
						
						$this->setValues($this->getObject());
						
						$conn->commit();
					} catch(\Exception $e) {
						$conn->rollBack();
						throw $e;
					}
				}
			}
		}
		
		if($this->isValid) {
			$this->init();
		}
		
		return $this->isValid;
	}
	
	
	
	public static function setLanguageSelect(\rk\form $form) {
		
		$languages = \rk\manager::getConfigParam('project.languages');
		$options = array('' => '');
		foreach($languages as $oneLang) {
			$options[$oneLang] = $oneLang;
		}
		
		$ref = $form->getModel()->getReference('i18n');
		$widgetName = $ref->getReferencedTableAlias() . '.' . $ref->getLanguageField();
		
		$form->changeWidgetType($widgetName, 'select');
		$form->getWidget($widgetName)->setParam('options', $options);
	}
	
	public static function configureFilters(\rk\form $form) {
		self::setLanguageSelect($form);
	}

	public static function configureAdvancedFilters(\rk\form $form) {
		self::setLanguageSelect($form);
	}
	
}