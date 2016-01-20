<?php 

namespace user;

class user extends \rk\user {
	
	public function login($login, $password) {
		return false;
	}
	
	
	public function getId() {
		if(!empty($this->data['id'])) {
			return $this->data['id'];
		}
		
		return false;
	}
}