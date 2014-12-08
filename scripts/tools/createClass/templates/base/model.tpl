<?php

namespace @namespace@;

abstract class @className@ extends \rk\model {
	
	protected $dbConnectorName = '@dbConnectorName@';
	protected $tableName = '@tableName@';
	
	public function init() {
		$this->setAttributes(array(
			@attributeList@
		));
		@referencesListSTART@
		$this->addReferences(array(
			@referencesList@
		));
		@referencesListEND@
		@behavioursListSTART@
		$this->addBehaviours(array(
			@behavioursList@
		));
		@behavioursListEND@
	}
}
