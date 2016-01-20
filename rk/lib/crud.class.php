<?php

namespace rk;


class crud {

	/**
	 * @desc save pager instance
	 * @var \rk\pager
	 */
	protected $pager = null;
	
	/**
	 * @desc save form instance
	 * @var \rk\form
	 */
	protected $form = null;
	
	protected $requestParams = array();		// save request parameters
	
	protected $destination = '';			// save action button destination
	
	protected $forcedCriterias = array();		// save potentiel destination added criterias
	
	protected $modelName = '';				// save model name
	
	protected $objectName = '';				// object handle name
	 
	protected $params = '';					// save constrcut params
	
	
	public function __construct($modelName, array $requestParams = array(), array $params = array()) {
				
		if (!class_exists($modelName)) {
			throw new \rk\exception('invalid model name', array('modelName' => $modelName));
		}
		if (empty($params['destination'])) {
			throw new \rk\exception('missing destination');
		}
		
		$this->modelName = $modelName;
		
		$this->params = $params;
		$this->requestParams = $requestParams;
		$this->destination = $params['destination'];
		
		$this->objectName = \rk\helper::getClassFromNamespace($this->modelName, 1);
		
		if (empty($this->requestParams['crudAction'])) {
			$this->requestParams['crudAction'] = 'pager';
		}
		
		$this->executeCrudAction();
	}
	
	public function getModel() {
		return \rk\model\manager::get($this->modelName);
	}
	
	protected function executeCrudAction() {
		switch ($this->requestParams['crudAction']) {
				
			case 'edit':
			case 'add':
				$this->setForm();
				break;
		
			case 'delete':
				$this->doDelete();
				break;
		
			case 'pager':
				$this->setPager();
				break;
					
			default:
				throw new \rk\exception('invalid crudAction', array('crudMode' => $this->requestParams['crudAction']));
				break;
		}
	}
	
	public function hasBeenSubmitted() {
		$res = false;
		
		if (!empty($this->pager)) {
			$res = $this->pager->hasBeenSorted();
		} else if (!empty($this->form)) {
			$res = $this->form->hasBeenSubmitted();
		} else if ($this->requestParams['crudAction'] == 'delete') {
			$res = true;
		}
		return $res;
	}
	
	public function makeSortable($orderField) {
		if (!empty($this->pager)) {
			$this->pager->makeSortable($orderField);
		}
	}
	
	public function addForcedCriteria($name, $value) {
		$this->forcedCriterias[$name] = $value;
		if (!empty($this->pager)) {
			$this->pager->addForcedCriteria($name, $value);
		}
		else if (!empty($this->form)) {
			$widget = $this->form->getWidget($name);
			if (!empty($widget)) {				
				$widget->setValue($value);
			}
		}
	}
	
	public function setPagerTemplate($name) {
		if (!empty($this->pager)) {
			$this->pager->setTemplate($name);
		}
	}
	public function setFormTemplate($name) {
		if (!empty($this->form)) {
			$this->form->setTemplate($name);
		}
	}
	
	public function addExtraButton($name, array $params) {
		if (!empty($this->pager)) {
			$this->pager->addExtraButton($name, $params);
		}
	}
	
	public function addActionButton($params) {
		if (!empty($this->pager)) {
			$this->pager->addActionButton($params);
		}
	}
	
	public function getOutput() {		
		$out = '';
		
		switch ($this->requestParams['crudAction']) {
			case 'delete':
			break;
			case 'edit':
			case 'add':
				$out = $this->form->getOutput();
			break;
			case 'pager':
				$this->addCrudButtons();
				$out = $this->pager->getOutput();
			break;
			
			default:
				throw new \rk\exception('invalid crudAction', array('crudMode' => $this->requestParams['crudAction']));
			break;
		}
		
		return $out;
	}
	
	protected function setForm() {
		
		$object = array();		
		if ($this->requestParams['crudAction'] == 'edit') {
			$pk = $this->getModel()->getPK();
			$object = \rk\db\table::on($this->getTableName())->getOne(array($pk => $this->requestParams[$pk]));
		}
		
		$this->form = $this->getForm($object);
		$this->form->handleSubmit($this->requestParams);
	}
	
	protected function setPager() {
		
		$pagerParams = array();
		if (!empty($this->params['pagerParams'])) {
			$pagerParams = $this->params['pagerParams'];
		}
		$pagerParams['destination'] = $this->destination;
				
		$this->pager = $this->getPager($this->requestParams, $pagerParams);
	}
	
	protected function doDelete() {
		
		$pk = $this->getModel()->getPK();
		$object = \rk\db\table::on($this->getTableName())->getOne(array($pk => $this->requestParams[$pk]));
		$object->delete();
	}
	
	
	protected function getForm ($values = array()) {
		$formName = str_replace('\user\model', '\user\form', $this->modelName);
		return new $formName($values);
	}
	protected function getTableName () {
		$tableName = str_replace('\user\model\\', '', $this->modelName);
		return $tableName;
	}
	
	protected function getPager ($requestParams, $params) {
		$pagerClass = str_replace('\user\model', '\user\pager', $this->modelName);
		return new $pagerClass($requestParams, $params);
	}
	
	protected function getActionTarget($action) {
		$params = $this->forcedCriterias;
		$params['crudAction'] = $action;

		return urlFor($this->destination, $params);
	}
	protected function addCrudButtons() {
		
		if (!empty($this->params['buttons'])) {
			foreach($this->params['buttons'] as $buttonName => $oneButton) {
				if(!in_array($buttonName, array('add', 'edit', 'delete'))) {
					if($oneButton['type'] == 'action') {
						$oneButton['name'] = $buttonName;
						$this->pager->addActionButton($oneButton);
					} elseif($oneButton['type'] == 'extra') {
						$this->pager->addExtraButton($buttonName, $oneButton);
					}
				}
			}
		}
		
		$labelPrefixe = $this->pager->getI18nKeyPrefixe() . '.';
		//Add buttons
		$buttonParams = array(
			'target' 	=> $this->getActionTarget('add'),
			'label' 	=> i18n('pager.add'),
			'windowTitle'	=> i18n('pager.add'),
			'class'			=> 'button rkModale',
		);
		if (!empty($this->params['buttons']['add'])) {
			$buttonParams = array_merge($buttonParams, $this->params['buttons']['add']);
		}
		$this->pager->addExtraButton('add', $buttonParams);
						
		//Edit Buttons
		$buttonParams = array(
			'name'			=> 'edit',
			'target'		=> $this->getActionTarget('edit'),
			'windowTitle'	=> i18n('pager.edit'),
			'class'			=> 'icon edit rkModale'
		);
		if (!empty($this->params['buttons']['edit'])) {
			$buttonParams = array_merge($buttonParams, $this->params['buttons']['edit']);
		}
		$this->pager->addActionButton($buttonParams);
		
		//Delete Buttons
		$buttonParams = array(
			'name'		=> 'delete',
			'target'	=> $this->getActionTarget('delete'),
		);
		if (!empty($this->params['buttons']['delete'])) {
			$buttonParams = array_merge($buttonParams, $this->params['buttons']['delete']);
		}
		$this->pager->addActionButton($buttonParams);
		

	}
}
