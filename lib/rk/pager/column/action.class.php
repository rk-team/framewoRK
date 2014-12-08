<?php

namespace rk\pager\column;


class action extends \rk\pager\column {
	
	protected $name = 'actions';
	
	protected $label = 'pager.column.actions';
		
	protected $buttons = array();
	
	protected $identifier = 'actions';
	
	protected $sortable = false;
	
	public function __construct() {
	}
	
	public function addButton(array $params) {
		if(empty($params['name'])) {
			throw new \rk\exception('missing name');
		}
		if(empty($params['target'])) {
			throw new \rk\exception('missing target');
		}
		
		$this->buttons[$params['name']] = $params;
	} 
	
	public function getOutput($data) {
		$return = '';

		foreach($this->buttons as $oneButton) {
			$urlParams = array();
			
			if(!empty($oneButton['targetParams'])) {
				foreach($oneButton['targetParams'] as $URLParamName => $dataFieldName) {
					if(!is_string($URLParamName)) {
						// this param has no string index : $dataFieldName is also used as the URL identifier (EX : only 'id' was given in this targetParam) 
						$urlParams[$dataFieldName] = $data[$dataFieldName];
					} else {
						// this param has a string index (EX : 'name' => 'my_field_name' was given in this targetParam)
						$urlParams[$URLParamName] = $data[$dataFieldName];
					}
				}
			}
			
			$url = urlFor($oneButton['target'], $urlParams);
			
			$class = '';
			if(!empty($oneButton['class'])) {
				$class = 'class="' . $oneButton['class'] . '" ';
			}
			
			$dataAttrs = '';
			if(!empty($oneButton['data-attrs'])) {
				foreach($oneButton['data-attrs'] as $name => $value) {
					$dataAttrs .= 'data-' . $name . '="' . str_replace('"', '\"', $value) . '"';
				}
			}
			
			$return .= '<a ' . $class . 'href="' . $url . '" ' . $dataAttrs . ' title="' . i18n('pager.' . $oneButton['name'], array(), array('htmlentities' => true)) . '">';
			if(!empty($oneButton['label'])) {
				$return .= i18n($oneButton['label']);
			}
			$return .= '</a> ';
		}
		
		return $return;
	}
}