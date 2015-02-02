<?php

namespace user\model;

class produit extends \user\model\_base\produit {

	public function init() {
		parent::init();
		
		$this->getReference('produit_ibfk_1')->setFields('nom');
	}
}
