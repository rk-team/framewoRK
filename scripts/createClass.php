<?php

namespace rk\scripts\tools;

//Load tools Classes
include('scripts/tools/common.php');
include('scripts/tools/createClass/parser.php');
include('scripts/tools/createClass/parserMysql.php');
include('scripts/tools/createClass/parserPgsql.php');

//Load rkFmwk Classes
include('lib/rk/autoloader.class.php');
\rk\autoloader::init();

use rk\scripts\tools;

class createClass {

	public static $allowedTypes = array('model', 'form', 'pager', 'table', 'object'),
				  $cmdHelp = "help : ./rkTools createClass <--all || --db=dbName [--table=tableName1,tableName2] [--schema=schemaName1,schemaName2]>\n",
				  $tplPath = 'scripts/tools/createClass/templates/';
	
	public  $db,
			$tablesToHandle,
			$schemasToHandle,
			$configDB,
			$checkedFiles = array(),
			$all = false;
	
	public function init ($argc, $argv) {
		//Check createClass params
		$this->checkParams($argc, $argv);
				
		$this->configDB = \rk\manager::getConfigParam('db');
		
		if($this->db == 'all') {
			$this->all = true;
			$this->db = array_keys($this->configDB);
		} elseif(!is_array($this->db)) {
			$this->db = array($this->db);
		}
	}
	
	public function execute() {

		foreach($this->db as $oneDB) {
		
			if (!array_key_exists($oneDB, $this->configDB)) {
				echo 'unknown db name ' . $oneDB . "\n";
				exit(1);
			}
		
			$connector = \rk\db\manager::get($oneDB);
			if ($connector->getType() == 'mysql') {
				$parser = new tools\createClass\parserMysql($connector);
			}
			else if ($connector->getType() == 'pgsql') {
				$parser = new tools\createClass\parserPgsql($connector);
			}
			else {
				echo "unknown SGBD parser\n";
				exit(1);
			}
			echo tools\common::$PRINT_CYAN . "\n" .'handle data base "' . $oneDB . '" : ' . "\n" . tools\common::$PRINT_STD;
					
			$tables = $parser->getTables();
			
			$tablesByName = array();
			foreach ($tables as $oneTable) {
				//On traite le cas de PG
				$explodeTable = explode('.', $oneTable);
				if (count($explodeTable) > 1) {
					if(!empty($this->tablesToHandle) && !in_array($explodeTable[1], $this->tablesToHandle)) {
						continue;
					}
					if(!empty($this->schemasToHandle) && !in_array($explodeTable[0], $this->schemasToHandle)) {
						continue;
					}
				} else {
					// we only created tables given in params if any given
					if(!empty($this->tablesToHandle) && !in_array($oneTable, $this->tablesToHandle)) {
						continue;
					}
				}
								
				$currentObject = $parser->getFieldsDesc($oneTable);
				
				$tablesByName[$currentObject['table_name']] = $currentObject;
			}
			
			foreach ($tablesByName as $tableName => $currentObject) {
				
				$tableHasI18n = false;		// weither the table has an i18n reference
				
				$pk = '';
				
				echo tools\common::$PRINT_BOLD . 'handle table ' .  $currentObject['table_name'] . " : \n" . tools\common::$PRINT_STD;
				
				//////////////
				//BASE MODEL//
				//////////////
				$model = file_get_contents(self::$tplPath . 'base/model.tpl');
				$model = str_replace('@className@',  $currentObject['class_name'], $model);
				
				$model = str_replace('@dbConnectorName@',  $oneDB, $model);
				$model = str_replace('@tableName@',  $currentObject['table_name'], $model);
		
				$replaceBy = self::getNameSpace($oneDB, 'model', $currentObject, true);
				$model = str_replace('@namespace@',  $replaceBy, $model);
					
				$indent = self::findIndent('@attributeList@', $model);
		
				$attrList = '';
				foreach ($currentObject['column'] as $oneColumn) {
					$attrList .= 'new \rk\model\attribute(\'' . $oneColumn['name'] . '\', \'' . $oneColumn['type'] . '\'';
		
					$options = '';
					if (!empty($oneColumn['is_nullable'])) {
						$options .= '\'nullable\' => true';
					}
						
					if (!empty($oneColumn['enum_values'])) {
						if (!empty($options)) {
							$options .= ', ';
						}
								
						$options .= '\'enumValues\' => array(';
						foreach($oneColumn['enum_values'] as $oneValue) {
							$options .= '\'' . $oneValue . '\' => \'' . $oneValue . '\', ';
						}
						
						//On retire la dernière virgule
						if (!empty($oneColumn['enum_values'])) {
							$options = substr($options, 0, -2);
						}
						
						$options .= ')';
					}
		
					if (!empty($oneColumn['char_max_length'])) {
						if (!empty($options)) {
							$options .= ', ';
						}
		
						$options .= '\'maxLength\' => ' . $oneColumn['char_max_length'];
					}
		
					if (!empty($oneColumn['primary'])) {
						if (!empty($options)) {
							$options .= ', ';
						}
		
						$options .= '\'primary\' => true';
						$pk = $oneColumn['name'];
					}
		
					if (!empty($options)) {
						$attrList .= ', array(' . $options . ')';
					}
					$attrList .= '),' . "\n";
		
					$attrList .= $indent;
				}
		
				$attrList = substr($attrList, 0, strlen($attrList) - strlen($indent) - 1);
				$model = str_replace('@attributeList@', $attrList, $model);
				
				//Looking for model behaviours
				$behavioursList = '';
				$indent = self::findIndent('@behavioursList@', $model);
				
				if (!empty($this->configDB[$oneDB]['behaviours'])) {
									
					foreach ($this->configDB[$oneDB]['behaviours'] as $oneBehaviourClass => $oneBehaviour) {
							
						if (!empty($oneBehaviour['requires_field']) || !empty($oneBehaviour['requires_suffix'])) {
							
							$hasColumn = false;
							if (!empty($oneBehaviour['requires_field'])) {							
								//Check if table has field
								foreach ($currentObject['column'] as $oneColumn) {
									if ($oneColumn['name'] == $oneBehaviour['requires_field']) {
										$hasColumn = true;
										break;
									}
								}
							} else if (!empty($oneBehaviour['requires_suffix'])) {
								if (!empty($tablesByName[$tableName . $oneBehaviour['requires_suffix']])) {
									$hasColumn = true;
								}
							}
														
							//If true add behaviours
							if ($hasColumn) {
								$behavioursList .= '\'' . $oneBehaviourClass . '\' => new \rk\model\behaviour\\' . $oneBehaviourClass . '(array(';
								foreach ($oneBehaviour as $oneBehaviourParamName => $oneBehaviourParamValue) {
									$behavioursList .= '\'' . $oneBehaviourParamName . '\' => \'' . $oneBehaviourParamValue . '\', ';
								}
								//removing trainling ','
								$behavioursList = substr($behavioursList, 0, strlen($behavioursList) - 2);
								$behavioursList .= ')),' . "\n" . $indent;
							}
							
						}
						else {
							new \rk\exception('invalid config description', array('behaviour' => $oneBehaviour));
						}
						
					}
				}
				
				//removing last ",\n"
				$behavioursList = substr($behavioursList, 0, strlen($behavioursList) - strlen($indent) - 2);
												
				if(empty($behavioursList)) {
					$start = strpos($model, '@behavioursListSTART@');
					$end = strpos($model, '@behavioursListEND@');
				
					if(!empty($start) && !empty($end)) {
						$model = substr($model, 0, $start) . substr($model, ($end + 19));
					}
				} else {
					$model = str_replace('@behavioursListSTART@', '', $model);
					$model = str_replace('@behavioursListEND@', '', $model);
				
					$model = str_replace('@behavioursList@', $behavioursList, $model);
				}
		
				//Looking for reference list
				$refList = '';
				$indent = self::findIndent('@referencesList@', $model);
				if(!empty($currentObject['references'])) {
		
					foreach ($currentObject['references'] as $referenceName => $oneRefence) {
						if($oneDB != 'default') {
							$modelName = $oneDB . '\\' . $oneRefence['referenced_table'];
						} else {
							$modelName = $oneRefence['referenced_table'];
						}
						$refList .= 'new \rk\model\reference(\'' . $referenceName . '\', \'' . $oneRefence['field'] . '\', \'' . $modelName . '\', array(
				\'connector\'			=> \'' . $oneDB . '\',
				\'referencedField\'	=> \'' . $oneRefence['referenced_field'] . '\',
			)),' . "\n" . $indent;
						
					}
					
					$refList = substr($refList, 0, strlen($refList) - strlen($indent) - 2);
				}
				
				if(!empty($this->configDB[$oneDB]['i18n'])) {
					
					$i18nConfig = $this->configDB[$oneDB]['i18n'];
					
					if (empty($pk)) {
						throw new \rk\exception('invalid i18n table', array('error' => 'no primary detected'));
					}
					if(empty($i18nConfig['requires_suffix'])) {
						throw new \rk\exception('invalid i18n configuration', array('error' => 'missing requires_suffix'));
					}
					
					if (!empty($refList)) {
						$refList .= ',';
					}
					$i18nTableName = $tableName . $i18nConfig['requires_suffix'];
				 	if (!empty($tablesByName[$i18nTableName])) {
				 		
				 		$refField = null;
				 		
				 		// get the FK between the main and the i18n table to get the referenced_field
				 		foreach ($tablesByName[$i18nTableName]['references'] as $i18nRef) {
				 			if($i18nRef['referenced_table'] == $tableName) {
				 				$refField = $i18nRef['field'];
				 				break;
				 			}
				 		}
				 		
				 		if(empty($refField)) {
				 			throw new \rk\exception('cant find FK for i18n');
				 		}
				 		
				 		$tableHasI18n = true;
					 	$modelName = $oneDB . '\\' . $i18nTableName;
						$refList .= '
			new \rk\model\reference\i18n(\'i18n\', \'' . $pk . '\', \'' . $modelName . '\', array(
				\'connector\'			=> \'' . $oneDB . '\',
				\'hasMany\'			=> true,
				\'referencedField\'	=> \'' . $refField . '\',
				\'hydrateBy\'			=> \'' . $i18nConfig['language_field'] . '\',
			))';
					}
				}
					
		
				if(empty($refList)) {
					$start = strpos($model, '@referencesListSTART@');
					$end = strpos($model, '@referencesListEND@');
		
					if(!empty($start) && !empty($end)) {
						$model = substr($model, 0, $start) . substr($model, ($end + 19));
					}
				} else {
					$model = str_replace('@referencesListSTART@', '', $model);
					$model = str_replace("@referencesListEND@\n", '', $model);
		
					$model = str_replace('@referencesList@', $refList, $model);
				}		
		
				$finalFileName = $this->getFileName($oneDB, $currentObject, 'model', true);
				$this->fillBaseFileAndInform($finalFileName, $model);
				
				$this->checkedFiles[] = $finalFileName;
		
				////////////////
				//MODEL ETENDU//
				////////////////
				$finalFileName = self::getFileName($oneDB, $currentObject, 'model');
				$this->checkedFiles[] = $finalFileName;
		
				if (!file_exists($finalFileName)) {
					$model = file_get_contents(self::$tplPath . 'model.tpl');
					$model = str_replace('@className@',  $currentObject['class_name'], $model);
						
					$parentClassName = self::getParentClassName($oneDB, $currentObject, 'model');
					$model = str_replace('@parentClassName@',  $parentClassName, $model);
						
					$replaceBy = self::getNameSpace($oneDB, 'model', $currentObject);
					$model = str_replace('@namespace@',  $replaceBy, $model);
					
					self::fillExtendFileAndInform($finalFileName, $model);
				}
									
				////////////////
				//TABLE ETENDU//
				////////////////
				$finalFileName = $this->getFileName($oneDB, $currentObject, 'table');
				$this->checkedFiles[] = $finalFileName;
				
				if (!file_exists($finalFileName)) {
					$table = file_get_contents(self::$tplPath . 'table.tpl');
					$table = str_replace('@className@',  $currentObject['class_name'], $table);
						
					$parentClassName = $this->getParentClassName($oneDB, $currentObject, 'table');
					$table = str_replace('@parentClassName@',  $parentClassName, $table);
						
					$replaceBy = $this->getNameSpace($oneDB, 'table', $currentObject);
					$table = str_replace('@namespace@',  $replaceBy, $table);
						
					$this->fillExtendFileAndInform($finalFileName, $table);
				}
							
				///////////////
				//FORM ETENDU//
				///////////////
				$finalFileName = $this->getFileName($oneDB, $currentObject, 'form');
				$this->checkedFiles[] = $finalFileName;
				
				if (!file_exists($finalFileName)) {
					$form = file_get_contents(self::$tplPath . 'form.tpl');
					$form = str_replace('@className@',  $currentObject['class_name'], $form);
						
					if($tableHasI18n) {
						$extends = '\rk\form\i18n';
					} else {
						$extends = '\rk\form';
					}
					
					$form = str_replace('@extends@', $extends, $form);
					
					$replaceBy = $this->getNameSpace($oneDB, 'form', $currentObject);
					$form = str_replace('@namespace@',  $replaceBy, $form);
						
					$this->fillExtendFileAndInform($finalFileName, $form);
				}
							
				////////////////
				//PAGER ETENDU//
				////////////////
				$finalFileName = $this->getFileName($oneDB, $currentObject, 'pager');
				$this->checkedFiles[] = $finalFileName;
				
				if (!file_exists($finalFileName)) {
					$pager = file_get_contents(self::$tplPath . 'pager.tpl');
					$pager = str_replace('@className@',  $currentObject['class_name'], $pager);
						
					if($tableHasI18n) {
						$extends = '\rk\pager\i18n';
					} else {
						$extends = '\rk\pager';
					}
					
					$pager = str_replace('@extends@', $extends, $pager);
					
					$replaceBy = $this->getNameSpace($oneDB, 'pager', $currentObject);
					$pager = str_replace('@namespace@',  $replaceBy, $pager);
		
					$this->fillExtendFileAndInform($finalFileName, $pager);
				}

				/////////////////
				//OBJECT ETENDU//
				/////////////////
				$finalFileName = $this->getFileName($oneDB, $currentObject, 'object');
				$this->checkedFiles[] = $finalFileName;
				
				if (!file_exists($finalFileName)) {
					$object = file_get_contents(self::$tplPath . 'object.tpl');
					$object = str_replace('@className@',  $currentObject['class_name'], $object);
											
					$replaceBy = $this->getNameSpace($oneDB, 'object', $currentObject);
					$object = str_replace('@namespace@',  $replaceBy, $object);
		
					$this->fillExtendFileAndInform($finalFileName, $object);
				}
			}
		}
		
		if ($this->all) {
			echo tools\common::$PRINT_YELLOW . "\nfiles list no more used : " . tools\common::$PRINT_STD . "\n\n";
			$this->checkDeletableFiles('lib/user');
		}
	}
	
	/**
	 * @desc check params for createClass script
	 * @return needed params
	 */
	public function checkParams($argc, $argv) {
		
		//If no arg, print help
		if ($argc <= 1) {
			echo self::$cmdHelp;
			exit(1);
		}
		
		$acceptableParams = array('db', 'all', 'schema', 'table');
		
		//Check params
		$params = \rk\helper\cli::parseArgs($argc, $argv);
		foreach ($params as $oneParamName => $oneParam) {
			if (!in_array($oneParamName, $acceptableParams)) {
				echo "err : unknowkn param $oneParamName \n";
				echo self::$cmdHelp;
				exit(1);
			}
		}
		
		if (!isset($params['all'])) {
			if(!empty($params['db'])) {
				$this->db = $params['db'];
				if(!empty($params['table'])) {
					if (!is_array($params['table'])) {
						$params['table'] = array($params['table']);
					}
					$this->tablesToHandle = $params['table'];
				} else {
					$this->tablesToHandle = array();
				}
				if(!empty($params['schema'])) {
					if (!is_array($params['schema'])) {
						$params['schema'] = array($params['schema']);
					}
					$this->schemasToHandle = $params['schema'];
				} else {
					$this->schemasToHandle = array();
				}
			} else {
				echo "err : if '--all' not set db has to be filled\n";
				echo self::$cmdHelp;
				exit(1);
			}
		} else {
			$this->db = 'all';
			$this->tablesToHandle = array();
			$this->schemasToHandle = array();
		}
	}

	/**
	 * @desc look for pattern indentation in a template file
	 * @return string : pattern indentation
	 */
	public function findIndent ($pattern, $file) {
	
		$indent = '';
		$lines = explode("\n", $file);
		foreach ($lines as $oneLine) {
			if (($pos = strpos($oneLine, $pattern)) != false) {
				$indent = substr($oneLine, 0, $pos);
			}
		}
		return $indent;
	}
	
		
	/**
	 * @desc fill the base file $fileName with $content and inform user
	 */
	public function fillBaseFileAndInform ($fileName, $content) {
	
		$modify = true;
		
		$infoPrefixe = "\t";
		if (file_exists($fileName)) {
			$fileContent = file_get_contents($fileName);
			if ($fileContent != $content) {
				$infoPrefixe .= tools\common::$PRINT_YELLOW . 'fichier base écrasé : ' . "\t";
			} else {
				$infoPrefixe .= tools\common::$PRINT_GREEN . 'fichier base non modifié : ' . "\t";
				$modify = false;
			}
		} else {
			$infoPrefixe .= tools\common::$PRINT_YELLOW . 'fichier base créé : ' . "\t";
		}
	
		if ($modify) {
			$dir = dirname($fileName);
			if(!is_dir($dir)) {
				\rk\helper\fileSystem::mkdir($dir);
			}
			file_put_contents($fileName, $content);
		}
	
		echo $infoPrefixe . $fileName . tools\common::$PRINT_STD . "\n";
	}
	
	/**
	 * @desc fill the extended file $fileName with $content and inform user
	 */
	function fillExtendFileAndInform($fileName, $content) {
	
		$dir = dirname($fileName);
		if(!is_dir($dir)) {
			\rk\helper\fileSystem::mkdir($dir);
		}
		file_put_contents($fileName, $content);
		
		echo "\t" . common::$PRINT_YELLOW . 'fichier étendue créé : ' . $fileName . "\n";
	}
	
	/**
	 * @desc Return the namespace with the object description
	 * @param string $dbName : name of the database for the current object
	 * @param string $type : type of object
	 * @param bool $base : is it a base class or an extended one
	 * @return string : namespace
	 */
	function getNameSpace ($dbName, $type, $currentObject, $base = false) {
				
		if(!in_array($type, self::$allowedTypes)) {
			throw new \rk\exception('invalid type ' . $type);
		}
	
		$replaceBy = 'user\\' . $type;
		if($base) {
			$replaceBy .= '\\_base';
		}
	
		if($dbName != 'default') {
			$replaceBy .= '\\' . $dbName;
		}
		
		if (!empty($currentObject['schema_name'])) {
			$replaceBy .= '\\' . $currentObject['schema_name'];
		}
		
		return $replaceBy;
	}
	
	/**
	 * @desc Return the file name for the object description
	 * @param string $dbName : name of the database for the current object
	 * @param string $tableName : name of the table for the current object
	 * @param string $type : type of object
	 * @param bool $base : is it a base class or an extended one
	 * @return string : file name
	 */
	function getFileName($dbName, $obj, $type, $base = false) {
	
		if(!in_array($type, self::$allowedTypes)) {
			throw new \rk\exception('invalid type');
		}
	
		$finalFileName = 'lib/user/' . $type . '/connector/';
		if($base) {
			$finalFileName .= '_base/';
		}
	
		if($dbName != 'default') {
			$finalFileName .= $dbName . '/';
		}
		
		if (!empty($obj['schema_name'])) {
			$finalFileName .= $obj['schema_name'] . '/';
		}
	
		$finalFileName .= str_replace('\\', '/', $obj['class_name']) . '.class.php';
	
		return $finalFileName;
	}
	
	/**
	 * @desc Return the parent class name for a extended object
	 * @param string $dbName : name of the database for the current object
	 * @param string $tableName : name of the table for the current object
	 * @param string $type : type of object
	 * @return string : parent class name
	 */
	function getParentClassName($dbName, $obj, $type) {
		
		if(!in_array($type, self::$allowedTypes)) {
			throw new \rk\exception('invalid type');
		}
	
		$parentClassName = '\user\\' . $type . '\_base\\';
		if($dbName != 'default') {
			$parentClassName .= $dbName . '\\';
		}
		
		if (!empty($obj['schema_name'])) {
			$parentClassName .= $obj['schema_name'] . '\\';
		}
		
		$parentClassName .= $obj['class_name'];
	
		return $parentClassName;
	}
	
	/**
	 * @desc return the class Name of the current
	 * @param array : $currentObject : obj desc
	 * @param string : $oneDB : current db
	 * @return string : the class Name of the current
	 */
	function getFullClassName($currentObject, $oneDB) {
		
		if($oneDB == 'default') {
			$res = '';
		} else {
			$res = $oneDB . '\\';
		}

		if (!empty($currentObject['schema_name'])) {
			$res .= $currentObject['schema_name'] . '\\';
		}
		
		$res .= $currentObject['class_name'];
		
		return $res;
	}
	
	/**
	 * @desc check if files can be deleted, and inform user
	 */
	function checkDeletableFiles($path) {
		
		$files = \rk\helper\fileSystem::scandir($path, array(
			'recursive' => true,
			'minDepth' => 2
		));
				
		foreach ($files as $oneFile) {
			if (!is_dir($oneFile) && !in_array($oneFile, $this->checkedFiles)) {
				echo tools\common::$PRINT_YELLOW . "\t rm " . "\"$oneFile\"" . tools\common::$PRINT_STD . "\n";
			}
		}
	}
}

//Check script launcher
tools\common::checkLauncher();

//Launch db parsing
$createClass = new createClass();
$createClass->init($argc, $argv);
$createClass->execute();
