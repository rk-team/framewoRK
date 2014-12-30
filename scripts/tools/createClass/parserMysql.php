<?php

namespace rk\scripts\tools\createClass;

class parserMysql extends parser {
	
	public function getTables () {
		$res = array();
		$tables = $this->connector->query('show tables');
	
		foreach ($tables as $oneTable) {
			$index = array_keys($oneTable);
			$res[] = $oneTable[$index[0]];
		}

		return $res;
	}
	
	public function getFieldsDesc ($oneTable) {

		$currentObject = array();
		$currentObject['table_name'] = $oneTable;
		$currentObject['class_name'] = $oneTable;
		
		$currentObject['column'] = array();
		$currentObject['references'] = array();
		
		$tableColumns = $this->connector->query('
			select c.*, cu.REFERENCED_TABLE_SCHEMA, cu.REFERENCED_TABLE_NAME, cu.REFERENCED_COLUMN_NAME, cu.CONSTRAINT_NAME
				from information_schema.columns c
				left join information_schema.KEY_COLUMN_USAGE cu on cu.CONSTRAINT_SCHEMA = c.TABLE_SCHEMA and cu.TABLE_NAME = c.TABLE_NAME and cu.column_name = c.column_name
				where c.table_schema=\'' . $this->connector->getDatabaseName() . '\'
				and c.table_name=\''. $currentObject['table_name'] . '\''
		);
		
		foreach ($tableColumns as $key => $oneTableColumns) {
		
			$currentObject['column'][$key] = array();
		
			$currentObject['column'][$key]['name'] = $oneTableColumns['COLUMN_NAME'];
			if ($oneTableColumns['COLUMN_KEY'] == 'PRI') {
				$currentObject['column'][$key]['primary'] = true;
			}
		
			if ($oneTableColumns['IS_NULLABLE'] == 'NO') {
				$currentObject['column'][$key]['is_nullable'] = false;
			} else {
				$currentObject['column'][$key]['is_nullable'] = true;
			}
			$currentObject['column'][$key]['char_max_length'] = $oneTableColumns['CHARACTER_MAXIMUM_LENGTH'];
			$currentObject['column'][$key]['num_max_length'] = $oneTableColumns['NUMERIC_PRECISION'];
		
			$currentObject['column'][$key]['type'] = $oneTableColumns['DATA_TYPE'];
		
			switch ($oneTableColumns['DATA_TYPE']) {
				case 'decimal':
					$currentObject['column'][$key]['type'] = 'float';
					break;
				case 'bigint':
				case 'int':
					$currentObject['column'][$key]['type'] = 'integer';
					break;
				case 'boolean':
				case 'tinyint':
					$currentObject['column'][$key]['type'] = 'boolean';
					break;
				case 'longblob':
				case 'mediumblob':
				case 'blob':
				case 'text':
					$currentObject['column'][$key]['type'] = 'richtext';
					break;
				case 'varchar':
					$currentObject['column'][$key]['type'] = 'text';
					break;
				case 'date':
					$currentObject['column'][$key]['type'] = 'date';
					break;
				case 'datetime':
					$currentObject['column'][$key]['type'] = 'datetime';
					break;
				case 'enum':
					$currentObject['column'][$key]['type'] = 'enum';
					$enumValues = str_replace(array('enum(', ')'), '', $oneTableColumns['COLUMN_TYPE']);
					$enumValues = explode(',', $enumValues);
					$nbEnumValues = count($enumValues);
					for($i = 0; $i < $nbEnumValues; $i++) {
						$enumValues[$i] = substr($enumValues[$i], 1, -1);
					}
					$currentObject['column'][$key]['enum_values'] = $enumValues;
					break;
				default :
					echo 'unknown data type : "' . $oneTableColumns['DATA_TYPE'] . "\"\n";
					exit(1);
			}
		
			if(!empty($oneTableColumns['REFERENCED_TABLE_SCHEMA'])) {
				$currentObject['references'][$oneTableColumns['CONSTRAINT_NAME']] = array(
						'field'				=> $oneTableColumns['COLUMN_NAME'],
						'referenced_table'	=> $oneTableColumns['REFERENCED_TABLE_NAME'],
						'referenced_field'	=> $oneTableColumns['REFERENCED_COLUMN_NAME']
				);
			}
		}
		
		return $currentObject;
	}
}