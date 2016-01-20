<?php

namespace rk\db\connector;

class pdo extends \rk\db\connector {

	protected
		$type,
		$host,
		$port,
		$user,
		$password,
		$charset = 'utf8',
		$database;
	
	public function __construct(array $configParams) {
		
		if(empty($configParams['type']) || empty($configParams['host']) || empty($configParams['user'])
		|| empty($configParams['password']) || empty($configParams['database']) || empty($configParams['name'])) {
			throw new \rk\exception('invalid params for pdo connector', array('configParams' => $configParams));
		}
		
		$this->name = $configParams['name'];
		$this->type = $configParams['type'];
		$this->host = $configParams['host'];
		$this->user= $configParams['user'];
		$this->password = $configParams['password'];
		$this->database = $configParams['database'];
		
		if(!empty($configParams['port'])) {
			$this->port = $configParams['port'];
		}
		if(!empty($configParams['charset'])) {
			$this->charset = $configParams['charset'];
		}
	}
	
	public function getDatabaseName() {
		return $this->database;
	}
	
	public function connect() {
		$str = $this->type . ':host=' . $this->host . ';dbname=' . $this->database;
		if (!empty($this->port)) {
			$str .= ';port=' . $this->port;
		}
		
		try {
			$conn = new \PDO($str, $this->user, $this->password);
			$conn->exec('SET CHARACTER SET ' . $this->charset);
		} catch (\PDOException $e) {
			throw new \rk\exception($this->type . ' connexion error', array('error' => $e->getMessage()));
		}
		
		$this->handle = $conn;
	}
	
	public function handleReturn($sth, $query) {
		
		$insert = false;
		if(strpos(trim($query), 'INSERT') === 0) {
			$insert = true;
		}
		
		if($insert) {
			return $this->handle->lastInsertId();
		}
		
		return $sth->fetchAll(\PDO::FETCH_ASSOC);
	}
	
	public function query($query, array $binds = array()) {
		
		try {			
			$start = microtime(true);
			$sth = $this->handle->prepare($query);
			$res = $sth->execute($binds);
		} catch (\PDOException $e) {
			throw new \rk\exception($this->type . ' query error', array('error' => $e->getMessage(), 'query' => $query, 'binds' => $binds));
		}
		
		if(!$res) {
			$errorInfo = $sth->errorInfo();
			
			$this->logQuery($query, $binds, $start);
			\rk\webLogger::add(array('errorInfo' => $errorInfo), $this->type . '-error');
			
			if($errorInfo[1] == 1062) {
				// UNIQUE constraint violated. ex :  Duplicate entry 'tataxxxx' for key 'image'
				$field = substr($errorInfo[2], strpos($errorInfo[2], 'for key ') + 8);
				$field = str_replace('\'', '', $field);
				throw new \rk\exception\db\uniqueViolation($field);
			} elseif($errorInfo[1] == 1452) {
				// FK violation. ex :  FOREIGN KEY (`pays_id`) REFERENCES `pays` (`id`))
				$start = strpos($errorInfo[2], 'FOREIGN KEY (`');
				$field = substr($errorInfo[2], ($start + 14));
				$end = strpos($field, '`) REFERENCES');
				$field = substr($field, 0, $end);
				throw new \rk\exception\db\FKViolation($field);
			}
			throw new \rk\exception($this->type . ' query error', array('error' => $errorInfo, 'query' => $query, 'binds' => $binds));
		}
		
		$return = $this->handleReturn($sth, $query);
		
		
		$this->logQuery($query, $binds, $start);
			
		return $return;
	}
	
	protected function logQuery($query, $binds, $start) {
		$end = microtime(true);
		$duration = $end - $start;
		
		$directQuery = $query;
		$directBinds = $binds;
		arsort($directBinds);	// invert the order so that ':param10' gets replaced before ':param1'
		foreach($directBinds as $bindName => $bindValue) {
			// replace binds by their values in $directQuery
			if(is_null($bindValue)) {
				$bindValue = 'null';
			} else {
				$bindValue = '"' . $bindValue . '"';
			}
			$directQuery = str_replace($bindName, $bindValue, $directQuery);
		}
		\rk\webLogger::add(array('selfDuration' => $duration, 'connector' => $this->name, 'originalQuery' => $query, 'originalBinds' => $binds, 'query' => $directQuery), 'SQL');
	
	}
	
	
	public function beginTransaction() {
		$this->handle->beginTransaction();
	}
	
	public function commit() {
		$this->handle->commit();
	}
	
	public function rollBack() {
		$this->handle->rollBack();
	}	
}