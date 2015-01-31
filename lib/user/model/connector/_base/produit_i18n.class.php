<?php

namespace user\model\_base;

abstract class produit_i18n extends \rk\model {
	
	protected $dbConnectorName = 'default';
	protected $tableName = 'produit_i18n';
	
	public function init() {
		$this->setAttributes(array(
			new \rk\model\attribute('id', 'integer', array('primary' => true)),
			new \rk\model\attribute('produit_id', 'integer'),
			new \rk\model\attribute('nom', 'text', array('maxLength' => 250)),
			new \rk\model\attribute('langue', 'text', array('maxLength' => 10)),
		));
		
		$this->addReferences(array(
			new \rk\model\reference('produit_i18n_ibfk_1', 'produit_id', 'produit', array(
				'connector'			=> 'default',
				'referencedField'	=> 'id',
			)),
		));
				
	}
}
