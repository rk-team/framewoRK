<?php

namespace user\front\modules\produit_type;

class index extends \rk\app\action {
	
	function execute() {
		$this->getCrud('produit_type', $this->params);
	}
	
}



class i18n extends \rk\app\action {
	
	function execute() {
		$this->getCrud('produit_type_i18n', $this->params);
	}
	
}