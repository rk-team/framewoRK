<?php

namespace rk\form;

class advancedFilters extends \rk\form\filters {
	
	protected $templateName = 'advancedFilters.php';
	
	protected $configureMethod = 'configureAdvancedFilters';
	
	
	
	public function addPreset($label, $criterias) {
		if(empty($this->params['presets'])) {
			$this->params['presets'] = array();
		}
		$critSet = new \rk\db\criteriaSet($criterias);
		$this->params['presets'][$label] = $critSet->getJSONFormatted();
	}
	
	
	
	
	protected function getJSONScriptParams() {
		$this->JSParams['oAdvancedCriterias'] = $this->getJSONCriterias();
		if(!empty($this->params['presets'])) {
			$this->JSParams['oPresets'] = $this->params['presets'];
		}
		return parent::getJSONScriptParams();
	}
	
	public function getJSONCriterias() {
		return $this->criteriaSet->getJSONFormatted();
	}
	
	private function _parseCriterias(&$criteriaSet, $criteriasToParse) {
		if(array_key_exists('c', $criteriasToParse)) {
			foreach($criteriasToParse['c'] as $oneCrit) {
				if(!empty($oneCrit['c'])) {
					$newSet = new \rk\db\criteriaSet(array(), $oneCrit['o']);
					$this->_parseCriterias($newSet, $oneCrit['c']);
					$criteriaSet->add($newSet);
				} else {
					$criteriaSet->add(new \rk\db\criteria($oneCrit['f'], $oneCrit['v'], $oneCrit['o']));
				}
			}
		} else {
			foreach($criteriasToParse as $oneCrit) {
// 				$widget = $this->getWidget($oneCrit['f']);
				$criteriaSet->add(new \rk\db\criteria($oneCrit['f'], $oneCrit['v'], $oneCrit['o']));
			}
		}
	}
	
	
	protected function setOperatorsForWidget($type, &$widgetParams) {
		if(empty($widgetParams['operator'])) {
			if($type == 'text' || $type == 'richtext') {
				$widgetParams['operator'] = array(
					\rk\db\builder::OPERATOR_ILIKE,
					\rk\db\builder::OPERATOR_NOTILIKE,
				);
			} else {
				$widgetParams['operator'] = array(
					\rk\db\builder::OPERATOR_EQUAL,
					\rk\db\builder::OPERATOR_NOTEQUAL,
				);
			}
		}
	}
	

	public function handleCriterias(array $formValues = array(), array $options = array()) {
		
		if(!empty($formValues['_rkFormId'])) {
			if(!empty($formValues['_rkFormId'])) {
				// retrieves the id of the form before it was submitted (ensure we do not generate a new id each time when the form is ajaxified)
				$this->id = $formValues['_rkFormId'];
			}
		}

		if(!empty($formValues['criterias'])) {
			$requestCriterias = json_decode($formValues['criterias'], true);
			$this->criteriaSet = new \rk\db\criteriaSet(array(), $requestCriterias['o']);
			$this->_parseCriterias($this->criteriaSet, $requestCriterias);
		} else {
			if(!empty($options['defaults'])) {
				$this->criteriaSet->add($options['defaults']);
			}
		}
		
		return $this->isValid;
	}
	
	
	protected function computeWidgetName($widgetName) {
		return $widgetName;
	}
}