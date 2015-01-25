<?php 

namespace user;

class user extends \rk\user {
	
	public function login($login, $password) {
		/*
		 * Implement your own login method here.
		 * This is just a very basic example
		 * 
		$res = \rk\db\table::on('user')->getOneByArray(array('name'	=> $login, 'password' => $password));
		
		if(!empty($res)) {
			$this->data = $res;
			$this->userName = $res['name'];
			$this->authentified = true;
			
			if($this->data['id'] == 1) {
				$this->groups[] = 'ADMIN';
			}
			
			return true;
		}
		*/		
		return false;
	}
	
	
	public function getId() {
		/*
		 * Update this as well
		 * 
		if(!empty($this->data['id'])) {
			return $this->data['id'];
		}
		 */
		
		return false;
	}
}