<?php

namespace rk\db\connector;

class mysql extends \rk\db\connector\pdo {
	
	
	public function connect() {
		parent::connect();
		$this->handle->exec('SET NAMES ' . $this->charset);
	}
}