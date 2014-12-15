<?php

namespace rk;


abstract class pager {

	protected $modelName = null;
	
	protected $data = null;				// data retrieved from DB and to be displayed
	
	protected $extraButtons = array();	// extra buttons to be displayed with the pager
	
	protected $templatePath;			// path of the display template used for the pager
	
	protected $columns;					// array of columns used in the pager
	
	protected $requestParams = array();		// params from request (may contain filters, nbResultsPerPage, page, ...)
	
	protected $formFilters;				// form containing filters
	
	protected $params = array();		// various params (destination, page, ...)
	
	protected $paramsForURL = array();	// params to be added to the pager full URl
	
	protected $nbItemsPerPage = 10;		// number of items to be displayed on each page
	
	protected $currentPage = 1;			// current page
	
	protected $nbPaginationLinks = 5;	// number of pagination links to display
	
	protected $destination;				// URL of the pager
	
	protected $tableRetrieveMethod = 'getForPager';	// method used on the table to get the data
	
	protected $tableConfigureMethod = 'configureForPager';	// method used on the table to configure filters
	
	protected $DOMId;					// id of the pager in the DOM
	
	protected $JSParams = array();		// params to be given to the JS Widget
	
	protected $sortableField = '';		// wether the pager is sortable or not (full page displayed with sortable action added), contains the field used for order
										
	protected $displayFilter = true;	// wether the filters are displayed or not
	
 	protected $displayPagination = true; // wether the pagination are displayed or not
 	
 	protected $forcedCriterias = array();	// user forced criteria
 	
 	protected $nbMatches = 0;
 	
 	protected $nbPages = 1;
 	
 	protected $emptyMessage = 'pager.no_data';
 	
	public function __construct(array $requestParams = array(), array $params = array()) {
		
		if(!empty($requestParams['nbItemsPerPage'])) {
			$params['nbItemsPerPage'] = $requestParams['nbItemsPerPage'];
		}
		if(!empty($requestParams['page'])) {
			$params['page'] = $requestParams['page'];
		}
		$this->setParams($params);
		
		if(empty($this->DOMId)) {
			$sId = get_class($this);
			$sId = str_replace('user\pager\\', '', $sId);
			$this->DOMId = str_replace('\\', '-', $sId);
		}
		
		// template
		if(!empty($params['template'])) {
			$template = $params['template'];
		} else {
			$template = 'table.php';
		}
		$this->setTemplate($template);
		
		$this->requestParams = $requestParams;
		
		$this->init();
	}
	
	protected function init() {
		$this->useTable($this->getTable());
	}
	
	protected function getModelName() {
		if(!empty($this->modelName)) {
			return $this->modelName;
		}
		
		$modelName = get_class($this);
		$this->modelName = str_replace('user\pager', 'user\model', $modelName);
		
		return $this->modelName;
	}
	
	/**
	 * @return \rk\model
	 */
	public function getModel() {
		return \rk\model\manager::get($this->getModelName());
	}
	
	/**
	 * @return \rk\db\table
	 */
	public function getTable() {
		return $this->getModel()->getTable();
	}
	
	protected function setParams(array $params) {
		foreach($params as $name => $value) {
			$this->setParam($name, $value);
		}
	}
	
	protected  function setParam($name, $value) {
		$this->params[$name] = $value;
		switch($name) {
			case 'destination':
				$this->destination = $value;
			break;
			
			case 'nbItemsPerPage':
				$this->nbItemsPerPage = $value;
			break;
			
			case 'emptyMessage':
				$this->emptyMessage = $value;
			break;
			
			case 'data':
				$this->data = $value;
			break;
			
			case 'displayPagination':
				$this->displayPagination = $value;
			break;
			
			case 'displayFilter':
				$this->displayFilter = $value;
			break;
			
			case 'DOMId':
				$this->DOMId = $value;
			break;
		}
	}
	
	/**
	 * @desc returns the URL of the page with ALL parameters, excluding criterias
	 */
	public function getBaseURL() {
		$params = array();
		if ($this->displayPagination) {
			$params['nbItemsPerPage'] = $this->nbItemsPerPage;
		}	
		foreach ($this->forcedCriterias as $oneKey => $oneCriteria) {
			$params[$oneKey] = $oneCriteria;
		}
		
		return \rk\helper\url::addParamsToURL($this->destination, $params);
	}
	
	
	/**
	 * @desc returns the URL of the page with ALL parameters, including criterias
	 */
	public function getFullURL() {
		$params = array(
			'nbItemsPerPage'	=> $this->nbItemsPerPage,
		);
		$params = array_merge($params, $this->requestParams);
		
		if(!empty($this->JSParams['oAdvancedCriterias'])) {
			$params['criterias'] = $this->JSParams['oAdvancedCriterias'];
		}
		
		return \rk\helper\url::addParamsToURL($this->destination, $params);
	}
	
	
	
	protected function initColumnsFromModelAttributes(\rk\model $model) {
		// add column for each attribute of the model
		foreach($model->getAttributes() as $oneAttr) {
			if($oneAttr->getName() != $model->getPK()) {
				$params = array(
					'name' 			=> $oneAttr->getName(),
					'label'			=> $this->getI18nKeyPrefixe($model) . '.' . $oneAttr->getName(),
					'table'			=> $model->getTableName(), 
				);
	
				$this->setColumn($params);
			}
		}
	}
	
	protected function initColumnsFromModelReferences(\rk\model $model) {
		// add column for each references of the model
		foreach($model->getReferences() as $refName => $oneRef) {
			if(!$oneRef->hasMany()) {
				$this->initColumnForOneToOneReference($oneRef);
			}
		}
	}
	
	protected function initColumnForOneToOneReference(\rk\model\reference $ref) {
		$params = array(
			'name' 			=> $ref->getReferencingField(),
			'label'			=> \rk\i18n::getORMKey($this->getModel()->getDbConnectorName(), $this->getModel()->getTableName(), $ref->getReferencingField()),
			'table'			=> $ref->getReferencedTable(), 
		);
		
		$params['formatterParams'] = array(
			'table'	=> $ref->getReferencedTableAlias(), 
			'field'	=> $ref->getDisplayField(),
		);
		$params['formatter'] = function($row, $params) {
			return $row[$params['table']][$params['field']];
		};
		$this->setColumn($params);
	}
	
	protected function initColumns() {
		
		$this->initColumnsFromModelAttributes($this->getModel());
		
		$this->initColumnsFromModelReferences($this->getModel());
		
		
// 		// add column for each attribute of the model
// 		foreach($this->getModel()->getAttributes() as $oneAttr) {
// 			if($oneAttr->getName() != $this->getModel()->getPK()) {
// 				$params = array(
// 					'name' 			=> $oneAttr->getName(),
// 					'label'			=> $this->getI18nKeyPrefixe() . '.' . $oneAttr->getName(),
// 					'table'			=> $this->getModel()->getTableName(), 
// 				);
	
// 				$this->setColumn($params);
// 			}
// 		}
		
		// add columns for references
// 		$references = $this->getModel()->getReferences();
// 		foreach($references as $refName => $oneRef) {
// 			$fields = $oneRef->getFields();
			
// 			if($refName == 'i18n') {
// 				$params = array(
// 					'name' 			=> 'nb_i18n',
// 					'label'			=> 'nb_i18n',
// 					'table'			=> $oneRef->getReferencedTable(), 
// 				);
				
// 				$params['formatterParams'] = array( 
// 					'table'			=> $oneRef->getReferencedTable(),
// 				);
// 				$params['formatter'] = function($row, $params) {
// // 					var_dump($params);die();
// 					return count($row[$params['table']]);
// 				};
// 				$this->setColumn($params);
				
// 				$params = array(
// 					'name' 			=> 'existing_i18n',
// 					'label'			=> 'existing_i18n',
// 					'table'			=> $oneRef->getReferencedTable(), 
// 				);
				
// 				$params['formatterParams'] = array( 
// 					'table'			=> $oneRef->getReferencedTable(),
// 					'languageField'	=> \rk\manager::getConfigParam('db.' . $this->getModel()->getDbConnectorName() . '.i18n.language_field')
// 				);
// 				$params['formatter'] = function($row, $params) {
// 					$list = '';
					
// 					if(!empty($row[$params['table']])) {
// 						foreach($row[$params['table']] as $oneI18nRow) {
// 							$list .= ' ' . $oneI18nRow[$params['languageField']];
// 						}
// 					}
// 					return $list;
// 				};
// 				$this->setColumn($params);
				
				
				
				
				
				
				
				
				
				
// 			} elseif(!$oneRef->hasMany()) {
// 				// reference is one->one, and we only have 1 field : we replace the column from base model by this new column
// 				$params = array(
// 					'name' 			=> $oneRef->getReferencingField(),
// 					'label'			=> $this->getModel()->getDbConnectorName() . '.' . $oneRef->getReferencedTable() . '.' . $oneRef->getDisplayField(),
// 					'table'			=> $oneRef->getReferencedTable(), 
// 				);
				
// 				$params['formatterParams'] = array(
// 					'table'	=> $oneRef->getReferencedTableAlias(), 
// 					'field'	=> $oneRef->getDisplayField(),
// 				);
// 				$params['formatter'] = function($row, $params) {
// 					return $row[$params['table']][$params['field']];
// 				};
// 				$this->setColumn($params);
// 			}
// 		}
		
	}
	
	protected function initFilters(\rk\db\table $table) {
		if(empty($this->params['formFiltersClass'])) {
			$className = '\rk\form\filters';
		} else {
			$className = $this->params['formFiltersClass'];
		}
		$this->formFilters = new $className(array(), array('table' => $table, 'destination' => $this->getBaseURL()));
		$this->formFilters->setParam('updateContainer', $this->DOMId);
	}
	
	protected function setColumn($params) {
		if(empty($params['table'])) {
			$params['table'] = $this->getModel()->getTableName();
		}
		
		$col = new \rk\pager\column($params);
		$this->columns[$col->getName()] = $col;
	}

	protected function checkSecurityParams($params) {
		
		if(!empty($params['requiresAuth']) && !\rk\manager::isUserAuth()) {
			// action requires user to be authentified
			return false;
		} elseif(!empty($params['requiresGroup'])) {
			// action requires user to be part of given group(s)
			$groups = $params['requiresGroup'];
			if(!is_array($groups)) {
				$groups = array($groups);
			}
			if(!\rk\manager::getUser()->hasGroup($groups)) {
				return false;
			}
		}
		return true;
	}
	
	public function addActionButton($params) {
		
		if(!array_key_exists('name', $params)) {
			throw new \rk\exception('missing name');
		}
		if(!array_key_exists('target', $params)) {
			throw new \rk\exception('missing target');
		}
		
		if(empty($this->columns['actions'])) {
			$this->columns['actions'] = new \rk\pager\column\action();
		}
		
		if($params['name'] == 'delete') {
			// set default values for delete action button
			if(!array_key_exists('class', $params)) {
				$params['class'] = 'rkConfirm icon ' . $params['name'];
			}
			if(!array_key_exists('data-attrs', $params) || !array_key_exists('rkWindowTitle', $params['data-attrs'])) {
				$params['data-attrs']['rkWindowTitle'] = i18n('pager.delete_confirm', array(), array('htmlentities' => true));
			}
		}
		
		// by default we use "rkModale icon <name>" as our CSS class
		if(!array_key_exists('class', $params)) {
			$params['class'] = 'rkModale icon ' . $params['name'];
		}
		
		// by default we use the PK as the param to be given to URLs
		if(!array_key_exists('targetParams', $params)) {
			$params['targetParams'] = $this->getActionButtonURLParams();
		}
		
		if(!empty($params['windowTitle'])) {
			$params['data-attrs']['rkWindowTitle'] = $params['windowTitle'];
		}
		
		//security check
		if ($this->checkSecurityParams($params)) {
			$this->columns['actions']->addButton($params);
		}
	}
	
	protected function getActionButtonURLParams() {
		return array($this->getModel()->getPK());
	}
	
	protected function orderColumns(array $orderedColumnNames) {
		$newColumns = array();
// 		var_dump($this->columns);
		foreach($orderedColumnNames as $oneColumnName) {
			if(empty($this->columns[$oneColumnName])) {
				throw new \rk\exception('unknown column', array($oneColumnName));
			}
			$newColumns[$oneColumnName] = $this->columns[$oneColumnName];
		}
		$this->columns = $newColumns;
	}
	
	/**
	 * 
	 * @param unknown_type $name
	 * @return \rk\pager\column
	 */
	public function getColumn($name) {
		if(!empty($this->columns[$name])) {
			return $this->columns[$name];
		}
		
		return false;
	}
	
	public function setTemplate($template) {
		$this->templatePath = \rk\manager::getPagerTemplatePath($template);
		if(!file_exists($this->templatePath)) {
			throw new \rk\exception('cant find pager template', array('templatePath' => $this->templatePath));
		}
	}
	
	public function addExtraButton($name, array $params) {
		if(!array_key_exists('label', $params)) {
			throw new \rk\exception('missing label for extraButton');
		}
		if(!array_key_exists('target', $params)) {
			throw new \rk\exception('missing target for extraButton');
		}
		if(!array_key_exists('class', $params)) {
			$params['class'] = 'rkModale button ' . $name;
		}
		
		//security check
		if ($this->checkSecurityParams($params)) {
			$this->extraButtons[$name] = $params;
		}
	}
	
	public function hasExtraButton($name) {
		if(!empty($this->extraButtons[$name])) {
			return true;
		}
		return false;
	}
	
	public function getOutput() {
		$this->retrieveData();
		
		$paginationLinks = array();
		if ($this->displayPagination) {
			$paginationLinks = $this->computePaginationLinks();
		}
		
		$formFilters = array();
		if ($this->displayFilter) {
			$formFilters = $this->formFilters;
		}

		$tplParams = array(
			'params'		=> $this->params,
			'currentPage'	=> $this->currentPage,
			'nbPages'		=> $this->nbPages,
			'nbMatches'		=> $this->nbMatches,
			'DOMId'			=> $this->DOMId,
			'JSParams'		=> $this->getJSScriptParams(),
			'columns'		=> $this->columns,
			'formFilters'	=> $formFilters,
			'extraButtons'	=> $this->extraButtons,
			'data'			=> $this->data,
			'fullURL'		=> $this->getFullURL(),
			'paginationLinks'	=> $paginationLinks,
			'pk'				=> $this->getModel()->getPK(),
			'emptyMessage'		=> $this->emptyMessage
		);
		
		$return = \rk\helper\output::getOutput($this->templatePath, $tplParams);
				
		return $return;
	}
	
	public function getSearchHighlights() {
		$return = array();
		if(!empty($this->formFilters)) {
			// get search highlights from criteriaSet
			$criteriaSet = $this->formFilters->getCriteriaSet();
			$return = $criteriaSet->getSearchHighlights();
			
			// search for combo filters, to dispatch their highlights to their filters
			foreach($this->getTable()->getFilters() as $one) {
				if($one instanceof \rk\db\filter\textCombo && !empty($return[$one->getFieldIdentifier()])) {
					foreach($one->getParam('basedOn') as $oneSub) {
						$return[$oneSub->getFieldIdentifier()] = $return[$one->getFieldIdentifier()];
					}
					unset($return[$one->getFieldIdentifier()]);
				}
			}
		}
				
		return $return;
	}
	
	public function getJSScriptParams() {
		
		$this->JSParams['sId'] 					= $this->DOMId;
		$this->JSParams['sModel']				= $this->getModel()->getTableName();
		$this->JSParams['sId'] 					= $this->DOMId;
		$this->JSParams['mContainer'] 			= '#' . $this->DOMId;
		$this->JSParams['sBaseURL'] 			= $this->getBaseURL();
		$this->JSParams['bHasPagination'] 		= $this->displayPagination;		
		$this->JSParams['oSearchHighlights']	= $this->getSearchHighlights();

		if ($this->isSortable()) {
			$this->JSParams['bSortable'] = true;
		} else {
			$this->JSParams['iNbPaginationLinks'] 	= $this->nbPaginationLinks;
			$this->JSParams['iNbPages'] 			= $this->nbPages;
			$this->JSParams['iCurrentPage'] 		= $this->currentPage;			
		}
		
		return $this->JSParams;
	}
	
	
	public function useTable(\rk\db\table $table) {
		// initialize columns from table attributes
		$this->initColumns($table);
	}
	
	
	public function retrieveData() {
		
		//data can be fixed by setParam, if not we retrieve them
		if ($this->data === null) {
			
			$table = $this->getTable();
			$method = $this->tableConfigureMethod;
			if(method_exists($table, $method)) {
				$table->$method();
			}
			$this->initFilters($table);
			
			if ($this->displayPagination) {
				$this->params['limit'] = $this->nbItemsPerPage;
				if(!empty($this->params['page'])) {
					$this->params['offset'] = ($this->params['page'] - 1) * $this->params['limit'];
				}
			}
			
			$defaults = array();
			if(!empty($this->params['defaultCriterias'])) {
				$defaults = $this->params['defaultCriterias'];
			}
			

			
			if(!empty($this->formFilters)) {
				// get filter values from form
				$this->formFilters->handleCriterias($this->requestParams, array('defaults' => $defaults));
				$criteriaSet = $this->formFilters->getCriteriaSet();
				
			} else {
				$criteriaSet = new \rk\db\criteriaSet($defaults);
			}

			
			
			if($criteriaSet->hasCriterias()) {
				// we give applied criterias to the pager so that he can build URLs properly 
				$this->JSParams['oAdvancedCriterias'] = $criteriaSet->getJSONFormatted();
			}
			
			if (!empty($this->forcedCriterias)) {
				// forcedCriterias are always applied, but must not appear in the summary
				foreach ($this->forcedCriterias as $oneKey => $oneCriteria) {
					$criteriaSet->add($oneKey, $oneCriteria);
				}
			}
			
			if(!empty($this->requestParams['orderSort']) && !empty($this->requestParams['orderColumn'])) {
				// get the sort order from request
				$this->params['orderColumn'] = $this->requestParams['orderColumn'];
				$this->params['orderSort'] = $this->requestParams['orderSort'];
			}
			
			if ($this->isSortable()) {
				// if pager has a sortable JS widget, we force the sort to its column
				$this->params['orderColumn'] = $this->sortableField;
				$this->params['orderSort'] = 'asc';
			}

			$method = $this->tableRetrieveMethod;	
	
			$this->data = $table->$method($criteriaSet, $this->params);
// 			$this->initFilters($table);
// 			var_dump($this->data);
			// initialize filters with the instance of table that we used to query. this way, we can get any filter that might have been added to the table
// 			$this->formFilters->handleCriterias($this->requestParams, array('defaults' => $defaults));
			
			
			if ($this->displayPagination) {
				$this->nbMatches = $table->getCount($criteriaSet, $this->params);
			}
		} 
		//data already given via setParam
		else {
			if ($this->displayPagination) {
				$this->nbMatches = count($this->data);
			}
		}
		
		if ($this->displayPagination) {
			$this->nbPages = ceil($this->nbMatches / $this->nbItemsPerPage);
		}		
		
		if(!empty($this->params['page'])) {
			$this->currentPage = $this->params['page'];
		}
	}
	
	public function addForcedCriteria ($name, $value) {
		$this->forcedCriterias[$name] = $value;
	}
	
	protected function computePaginationLinks() {
		$links = array();

		$desiredLinks = $this->nbPaginationLinks - 1;
		if($desiredLinks > $this->nbPages) {
			$desiredLinks = $this->nbPages;
		}

		$leftLinks = $desiredLinks;
		$rightLinks = $desiredLinks;
		
		$start = $this->currentPage - $desiredLinks;
		
		if($start < 0) {
			$leftLinks += $start - 1;
			$start = 1;
		} elseif($start == 0) {
			$leftLinks -= 1;
			$start = 1;
		}
		

		$end = $this->currentPage + $desiredLinks;
		if($end >= $this->nbPages) {
			$rightLinks -= ($end - $this->nbPages);
			$end = $this->nbPages;
		}
		
		if($leftLinks + $rightLinks > $desiredLinks) {
			$tooMuch = ($leftLinks + $rightLinks) - $desiredLinks;
			while($tooMuch > 0) {
				if($rightLinks > $leftLinks) {
					$rightLinks--;
					$end--;
				} else {
					$leftLinks--;
					$start++;
				}
				$tooMuch--;
			}
		}
		
		$firstLinks = array();

		$firstLinks[] = array(
			'URL'	=> urlFor($this->getFullURL(), array('page' => 1)),
			'text'	=> '<<',
			'class'	=> 'first',
		);
		
		$targetPage = $this->currentPage - 1;
		if($targetPage < 1) {
			$targetPage = 1;
		}
		$firstLinks[] = array(
			'URL'	=> urlFor($this->getFullURL(), array('page' => $targetPage)),
			'text'	=> '<',
			'class'	=> 'previous',
		);
		
		for($i = $start; $i <= $end; $i++) {
			$links[] = array(
				'URL'		=> urlFor($this->getFullURL(), array('page' => $i)),
				'text'		=> $i,
				'active'	=> ($this->currentPage == $i)?true:false,
			);
		}

		
		$lastLinks = array();
		
		$targetPage = $this->currentPage + 1;
		if($targetPage > $this->nbPages) {
			$targetPage = $this->nbPages;
		}
		$lastLinks[] = array(
			'URL'	=> urlFor($this->getFullURL(), array('page' => $targetPage)),
			'text'	=> '>',
			'class'	=> 'next',
		);
		$lastLinks[] = array(
			'URL'	=> urlFor($this->getFullURL(), array('page' => $this->nbPages)),
			'text'	=> '>>',
			'class'	=> 'last',
		);
		
		return array(
			'left'	=> $firstLinks,
			'numbers'	=> $links,
			'right'	=> $lastLinks
		);
	}
	
	public function getI18nKeyPrefixe($model = null) {
		if(empty($model)) {
			$model = $this->getModel();
		}
		$tableName = $model->getTableName();
		$dbConnName = $model->getDBConnectorName();
		
		return \rk\i18n::getORMKey($dbConnName, $tableName);
	}
	
	public function isSortable() {
		$res = false;
		if (!empty($this->sortableField)) {
			$res = true;
		}
		return $res;
	}
	
	public function hasBeenSorted() {
		$res = false;
		if (!empty($this->requestParams['changeOrder'])) {
			$res = true;
		}
		return $res;
	}
	
	public function makeSortable($orderFieldName, $params = array()) {
		
		$this->sortableField = $orderFieldName;
		$this->displayFilter = false;
		$this->displayPagination = false;
		
		$this->setTemplate('div.php');
		
		// Add drag button
		$buttonParams = array(
			'name'	=> 'drag',
			'target' => $this->destination,
			'class'	=> 'move icon'
		);
		if (!empty($params['buttons'])) {
			$buttonParams = array_merge($buttonParams, $params['buttons']);
		}
		$this->addActionButton($buttonParams);
		
		// Apply changeOrder if needed
		if (!empty($this->requestParams['changeOrder'])) {
			foreach ($this->requestParams['changeOrder'] as $oneOrder => $onePk) {
				$pk = $this->getModel()->getPK();
				$res = $this->getTable()->update(array(
					$pk => $onePk,
					$this->sortableField => $oneOrder
				));
			}
		}
	}
}