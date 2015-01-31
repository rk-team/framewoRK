<?php

namespace user\front\modules\produit;

class index extends \rk\app\action {
	
	function execute() {
		$this->getCrud('produit', $this->params);
	}
	
}



class i18n extends \rk\app\action {
	
	function execute() {
		$this->getCrud('produit_i18n', $this->params);
	}
	
}