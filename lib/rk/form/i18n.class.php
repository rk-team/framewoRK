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
				if(!empty($i18nObjects[$one])) {
					$i18nForm = $i18nObjects[$one]->getForm();
				} else {
					$i18nForm = $i18nModel->getObject()->getForm();
				}

				
				$languageField = $i18nRef->getLanguageField();
				$i18nForm->changeWidgetType($languageField, 'select');
				$i18nForm->getWidget($languageField)->setParam('options', array($one => $one));
				
				// hide the FK
				$refs = $i18nModel->getReferences();
				foreach($refs as $oneRef) {
					$i18nForm->getWidget($oneRef->getReferencingField())->setParam('required', false);
					$i18nForm->changeWidgetType($oneRef->getReferencingField(), 'hidden');
				}
				
				$this->addSubForm('i18n_' . $one, $i18nForm);
			}
			
			
		}
	}

	public static function configureAdvancedFilters(\rk\form $form) {

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
	
}