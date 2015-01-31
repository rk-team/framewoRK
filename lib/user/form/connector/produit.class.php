<?php

namespace user\form;

class produit extends \rk\form\i18n {

	protected function init() {
	}
	
	public static function configureFilters(\rk\form $form) {
		parent::configureFilters($form);
	}
		
	public static function configureAdvancedFilters(\rk\form $form) {
		parent::configureAdvancedFilters($form);
	}
}
