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
	
	
	
	public function save() {
		parent::save();
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