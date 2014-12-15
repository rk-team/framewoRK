<?php

namespace rk\pager;


class column {
	
	protected $name;			// name of the field in pager's query
	
	protected $table;			// table name of the field
		
	protected $label;
	
	protected $identifier;		// index of the column in row retrieved from DB
	
	protected $sortable = true;	// weither the column is SQL sortable
	
	protected $formatterParams = array();
	
	protected $formatter = null;
	
	/**
	 * @var mixed : OPTIONNAL function to be called to format the data in the pager
	 * 
	 * can be either :
	 * 	- an anonymous function
	 * 		ex: 
	 *	 		'formatter' => function($row) {
	 * 				return count($row['comments']);
	 * 			}
	 *  - or a callback (http://php.net/call-user-func) 
	 *   	ex:
	 *   		'formatter'	=> array($this, 'nbCommentsFormatter')
	 *   	in which case the pager must have a nbCommentsFormatter public function
	 * 
	 */
	
	public function __construct(array $params) {
		if(empty($params['name'])) {
			throw new \rk\exception('missing name');
		}
		if(empty($params['label'])) {
			throw new \rk\exception('missing label');
		}
		if(empty($params['table'])) {
			throw new \rk\exception('missing table');
		}
		if(empty($params['identifier'])) {
			$params['identifier'] = $params['name'];
		}

		$this->name = $params['name'];
		$this->table = $params['table'];
		$this->label = $params['label'];		
		$this->identifier = $params['identifier'];
		
		if(!empty($params['formatter'])) {
			$this->setFormatter($params['formatter']);
		}
		if(!empty($params['formatterParams'])) {
			$this->formatterParams = $params['formatterParams'];
		}

		if(array_key_exists('sortable', $params)) {
			$this->setSortable($params['sortable']);
		}
	}
	
	public function setSortable($sortable) {
		$this->sortable = $sortable;
	}
	public function isSortable() {
		return !empty($this->sortable);
	}
	
	
	public function setFormatter($formatter) {
		$this->formatter = $formatter;
	}
	public function getLabel() {
		return $this->label;
	}
	public function getName() {
		return $this->name;
	}
	public function getTable() {
		return $this->table;
	}

	
	public function getOutput($data) {
		$formatterParams = array();
		if (!empty($this->formatterParams)) {
			$formatterParams = $this->formatterParams;
		}
		
// 		$highlightsForCol = array();
// 		if(!empty($searchHighlights[$this->identifier])) {
// 			$highlightsForCol = $searchHighlights[$this->identifier];			
// 		}
		
// 		$formatterParams['highlightsForCol'] = $highlightsForCol;
		
		if(!empty($this->formatter)) {
			$formatter = $this->formatter;
			if($formatter instanceof \Closure) {
				return $formatter($data, $formatterParams);
			} else {
				return call_user_func($formatter, $data, $formatterParams);
			}			
		}
		
		if(!empty($data[$this->identifier])) {
			return $data[$this->identifier];
		}
		
		return '';
	}
	
// 	public static function handleSearchHightlights($data, array $highlights = array()) {
// 		$return = $data;
// 		foreach($highlights as $one) {
// 			if($one['operator'] == \rk\db\builder::OPERATOR_EQUAL) {
// 				if($data == $one['value']) {
// 					$return = '<span class="highlight">' . $data . '</span>';
// 				}
// 			} elseif($one['operator'] == \rk\db\builder::OPERATOR_LIKE) {
// 				$return = str_replace($one['value'], '<span class="highlight">' . $one['value'] . '</span>', $data);
// 			} elseif($one['operator'] == \rk\db\builder::OPERATOR_ILIKE) {
// 				$return = str_ireplace($one['value'], '<span class="highlight">' . $one['value'] . '</span>', $data);
// 			} else {
// 				throw new \rk\exception('unhandled operator ' . $one['operator']);
// 			}
// 		}
		
// 		return $return;
// 	}
	
	public function getSortableLinkURL($URL, $sortOrder) {
		return htmlentities(urlFor($URL, array('orderSort' => $sortOrder, 'orderColumn' => $this->getName())));
	}
	public function getSortableLinkClass($URL, $sortOrder) {
		$params = \rk\helper\url::getParamsFromURL($URL);
		
		$class = 'sort' . ucfirst($sortOrder);
		
		if(!empty($params['orderColumn']) && !empty($params['orderSort'])) {
			if($params['orderColumn'] == $this->getName() && strtoupper($params['orderSort']) == strtoupper($sortOrder)) {
				$class .= ' active';
			}
		}
		
		return $class;
		
	}
}