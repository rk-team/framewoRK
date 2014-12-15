<?php

namespace rk\db\connector;

class pgsql extends \rk\db\connector\pdo {

	public function handleReturn($sth, $query) {
		
		$insert = false;
		if(strpos(trim($query), 'INSERT') === 0) {
			$insert = true;
		}
		
		if($insert) {
			$res = $sth->fetchAll(\PDO::FETCH_ASSOC);
			return $res[0]['pk'];
		}
		
		return $sth->fetchAll(\PDO::FETCH_ASSOC);
	}
}