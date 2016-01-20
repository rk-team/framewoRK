<?php

namespace rk\db;

class manager {
	
	protected static
		$connectors = array();
	
	/**
	 * @return \rk\db\connector
	 */
	public static function get($connectorName = 'default') {
		if(empty(self::$connectors[$connectorName])) {
			$configParams = \rk\manager::getConfigParam('db.' . $connectorName);
			
			$configParams['name'] = $connectorName;
		
			$connectorClassName = '';
			switch($configParams['type']) {
				case 'mysql':
					$connectorClassName = '\rk\db\connector\mysql';
					break;
				case 'pgsql':
					$connectorClassName = '\rk\db\connector\pgsql';
					break;
				default:
					throw new \rk\exception('unknown dbConnector type ' . $configParams['type']);
			}
			$connector = new $connectorClassName($configParams);
			$connector->connect();
			
			self::$connectors[$connectorName] = $connector;
		}
		
		return self::$connectors[$connectorName];
	}
}