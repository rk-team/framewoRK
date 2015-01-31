<?php

namespace user\model\_base;

abstract class produit extends \rk\model {
	
	protected $dbConnectorName = 'default';
	protected $tableName = 'produit';
	
	public function init() {
		$this->setAttributes(array(
			new \rk\model\attribute('id', 'integer', array('primary' => true)),
			new \rk\model\attribute('type_id', 'integer'),
		));
		
		$this->addReferences(array(
			new \rk\model\reference('produit_ibfk_1', 'type_id', 'produit_type', array(
				'connector'			=> 'default',
				'referencedField'	=> 'id',
			)),
			new \rk\model\reference\i18n('i18n', 'id', 'produit_i18n', array(
				'connector'			=> 'default',
				'hasMany'			=> true,
				'referencedField'	=> 'produit_id',
				'hydrateBy'			=> 'langue',
			))
		));
				
	}
}
