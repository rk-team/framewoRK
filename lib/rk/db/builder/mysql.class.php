<?php

namespace rk\db\builder;
	
class mysql extends \rk\db\builder {
	
	public function formatValuesForBuilder($value, $type) {
		if($type == 'date' || $type == 'datetime') {
			$value = \rk\date::get($value);
			if(!is_null($value)) {
				$value = $value->dbFormat();
			}
		}
		return $value;
	}
	
	public function formatValuesFromBuilder($value, $type) {
		if($type == 'date') {
			try {
				$value = \rk\date::createFromDB($value, false);
			} catch(\rk\exception\invalidFormat $e) {
				$value = null;
			}
		} elseif($type == 'datetime') {
			try {
				$value = \rk\date::createFromDB($value);
			} catch(\rk\exception\invalidFormat $e) {
				$value = null;
			}
		}
		
		return $value;
	}

}