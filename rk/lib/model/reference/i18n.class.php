<?php

namespace rk\model\reference;

/**
 * Represents an i18n Database Reference
 */
class i18n extends \rk\model\reference {
	
	
	public function getLanguageField() {
		return \rk\manager::getConfigParam('db.' . $this->getReferencedModel()->getDbConnectorName() . '.i18n.language_field');
	}
	
	
	public function getDisplayField() {
		$fields = $this->getFields();
		
		foreach($fields as $one) {
			// we try to get the first text field that is not language or the PK 
			if($one != $this->getLanguageField() && $one != $this->getReferencedModel()->getPK()) {
				$attr = $this->getReferencedModel()->getAttribute($one);
				if(!empty($attr) && $attr->getType() == 'text') {
					return $one;
				}
			}
		}
		
		return parent::getDisplayField();
	}
}