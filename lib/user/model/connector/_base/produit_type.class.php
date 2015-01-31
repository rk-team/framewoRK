<?php

namespace user\model\_base;

abstract class produit_type extends \rk\model {
	
	protected $dbConnectorName = 'default';
	protected $tableName = 'produit_type';
	
	public function init() {
		$this->setAttributes(array(
			new \rk\model\attribute('id', 'integer', array('primary' => true)),
		));
		
		$this->addReferences(array(
			
			new \rk\model\reference\i18n('i18n', 'id', 'produit_type_i18n', array(
				'connector'			=> 'default',
				'hasMany'			=> true,
				'referencedField'	=> 'produit_type_id',
				'hydrateBy'			=> 'langue',
			))
		));
				
	}
}
