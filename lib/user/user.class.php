<?php 

namespace user;

class user extends \rk\user {
	
	public function login($login, $password) {
		/**
		 * 
		 * Customize this code to implement your login function
		 * 
		 */
// 		$res = \rk\db\table::on('user')->getOneByArray(array(
// 			'login'		=> $login, 
// 			'password'	=> $password
// 		));
		
// 		if(!empty($res)) {
// 			$this->data = $res;
// 			$this->userName = $res['login'];
// 			$this->authentified = true;
			
// 			if(!empty($this->data['admin'])) {
// 				$this->groups[] = 'ADMIN';
// 			}
			
// 			return true;
// 		}
		return false;
	}
	
	
	public function getId() {
		if(!empty($this->data['id'])) {
			return $this->data['id'];
		}
		
		return false;
	}
}