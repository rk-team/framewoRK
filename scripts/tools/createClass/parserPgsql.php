<?php

namespace rk\scripts\tools\createClass;

class parserPgsql extends parser {
	
	public function getTables () {
		$res = array();
		
		$schemas = $this->connector->query('
			SELECT schema_name
				FROM information_schema.schemata
		');
		
		$schemasNames = array('public');
		foreach ($schemas as $oneShemas) {
			$schemasNames[] = $oneShemas['schema_name'];
		}
		
		foreach ($schemasNames as $oneSchemaName) {
			$tablesTmp = $this->connector->query('
				SELECT * 
					FROM pg_catalog.pg_tables where schemaname = \'' . $oneSchemaName . '\'
			');
			foreach ($tablesTmp as $oneTableTmp) {
				$res[] = $oneSchemaName . '.' . $oneTableTmp['tablename'];
			}
		}
				
		return $res;
	}
	
	public function getFieldsDesc ($oneTable) {
		$currentObject = array();
		
		$explode = explode('.', $oneTable);
		$schemaName = $explode[0];
		$tableName = $explode[1];
		
		//Retrieve column information
		$columns = $this->connector->query('
			SELECT  c.*	
				FROM information_schema.columns c				
				WHERE c.table_name = \'' . $tableName . '\' AND c.table_schema = \'' . $schemaName . '\'
		');
		
		//Get table primary key name
		//Query failed if no primary on table
		$primary = '';		
		$primaryConstraints = $this->connector->query('
			SELECT
			    tc.constraint_name, tc.table_name, kcu.column_name,
			    ccu.table_name AS foreign_table_name,
			    ccu.column_name AS foreign_column_name
			FROM
			    information_schema.table_constraints AS tc
			    JOIN information_schema.key_column_usage AS kcu
			      ON tc.constraint_name = kcu.constraint_name
			    JOIN information_schema.constraint_column_usage AS ccu
			      ON ccu.constraint_name = tc.constraint_name
			WHERE constraint_type = \'PRIMARY KEY\' AND tc.table_name=\'' . $tableName . '\' AND tc.constraint_schema = \'' . $schemaName . '\';
		');
		if (!empty($primaryConstraints)) {
			$primary = $primaryConstraints[0]['column_name'];
		}
		
		//Retrieve foreign key list
		$constraints = $this->connector->query('
			SELECT
			    tc.constraint_name, tc.table_name, kcu.column_name,
			    ccu.table_name AS foreign_table_name,
			    ccu.column_name AS foreign_column_name,
				ccu.table_schema AS foreign_schema_name
			FROM
			    information_schema.table_constraints AS tc
			    JOIN information_schema.key_column_usage AS kcu
			      ON tc.constraint_name = kcu.constraint_name AND kcu.constraint_schema = \'' . $schemaName . '\' 
			    JOIN information_schema.constraint_column_usage AS ccu
			      ON ccu.constraint_name = tc.constraint_name  AND ccu.constraint_schema = \'' . $schemaName . '\' 
			WHERE constraint_type = \'FOREIGN KEY\' AND tc.table_name=\'' . $tableName . '\' AND tc.constraint_schema = \'' . $schemaName . '\';
		');
		
		//Colmuns description initialization
		$currentObject = array();
		$currentObject['table_name'] = $oneTable;
		$currentObject['schema_name'] = $schemaName;
		$currentObject['class_name'] = $tableName;		
		
		$currentObject['column'] = array();
		$currentObject['references'] = array();
										
		foreach ($columns as $oneColumn) {
			
			$key = $oneColumn['column_name'];			
			$currentObject['column'][$key] = array();
		
			$currentObject['column'][$key]['name'] = $oneColumn['column_name'];
			
			if ($oneColumn['column_name'] == $primary) {
				$currentObject['column'][$key]['primary'] =  true;
			}

			if ($oneColumn['is_nullable'] == 'NO') {
				$currentObject['column'][$key]['is_nullable'] = false;
			} else {
				$currentObject['column'][$key]['is_nullable'] = true;
			}
			
			$currentObject['column'][$key]['char_max_length'] = $oneColumn['character_maximum_length'];
			$currentObject['column'][$key]['num_max_length'] = $oneColumn['numeric_precision'];
		
			$currentObject['column'][$key]['type'] = $oneColumn['data_type'];
			switch ($oneColumn['data_type']) {
				case 'integer':
					$currentObject['column'][$key]['type'] = 'integer';
					break;
				case 'boolean':
					$currentObject['column'][$key]['type'] = 'boolean';
					break;
				case 'blob':
					$currentObject['column'][$key]['type'] = 'richtext';
					break;
				case 'text':
				case 'character varying':
					$currentObject['column'][$key]['type'] = 'text';
					break;
				case 'date':
					$currentObject['column'][$key]['type'] = 'datetime';
					break;

				case 'USER-DEFINED':
					
					//On verifie si on a un enum
					$this->connector->beginTransaction();
					
					$this->connector->query('
						SET search_path TO \'' . $oneColumn['udt_schema'] . '\'
					');
					$enumValues = $this->connector->query('
						select enum_range(NULL::' . $oneColumn['udt_name'] . ')
					');
					
					$this->connector->commit();

					$currentObject['column'][$key]['type'] = 'enum';
					$currentObject['column'][$key]['enum_values'] = array();
					
					$enumValues = str_replace(array('{', '}'), '', $enumValues[0]['enum_range']);
					$enumValues = explode(',', $enumValues);
					foreach ($enumValues as $oneValues) {
						$currentObject['column'][$key]['enum_values'][] = $oneValues;
					}

					break;
					
				default :
					echo 'unknown data type : "' . $oneColumn['data_type'] . "\"\n";
					exit(1);

					break;

			}
			
			foreach ($constraints as $oneConstr) {
				
				$currentObject['references'][$oneConstr['constraint_name']] = array(
					'field'				=> $oneConstr['column_name'],
					'referenced_table'	=> $oneConstr['foreign_schema_name'] . '.' . $oneConstr['foreign_table_name'],
					'referenced_field'	=> $oneConstr['foreign_column_name']
				);
			}
		}
		
		return $currentObject;
	}
}