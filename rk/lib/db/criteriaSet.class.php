<?php

namespace rk\db;

class criteriaSet {
	
	protected
		$operator = 'and',
		$criterias = array();
	
	public function __construct($criterias = array(), $operator = null) {
		
		if(!is_null($operator)) {
			$this->operator = $operator;
		}
		if (!empty($criterias)) {
			$this->add($criterias);
		}
	}
	
	public function hasCriterias() {
		if(empty($this->criterias)) {
			return false;
		}
		
		return true;
	}
	
	public function hasCriteria($name) {
		foreach($this->criterias as $oneCrit) {
			if($oneCrit instanceof \rk\db\criteriaSet) {
				return $oneCrit->hasCriteria($name);
			} else {
				if($oneCrit->getName() == $name) {
					return true;
				}
			}
		}
		
		return false;
	}
	
	public function removeCriteria($name) {
		foreach($this->criterias as $key => $oneCrit) {
			if($oneCrit instanceof \rk\db\criteriaSet) {
				$oneCrit->removeCriteria($name);
			} else {
				if($oneCrit->getName() == $name) {
					unset($this->criterias[$key]);
				}
			}
		}
	}
	
	public function setCriterias(\rk\db\criteriaSet $criteriaSet) {
		$this->criteriaSet = $criteriaSet;
	}
	
	/**
	 * @desc Add criterias to a criteriaSet
	 * 	params can be :
	 * 		- 1 string $field, $value, optionnal string $operator
	 * 		- 2 \rk\db\criteria $criteria
	 *		- 3 \rk\db\criteriaSet $criteriaSet
	 *		- 4 array(
	 *				$fieldName => string $value,
	 *				...
	 *			)
	 *		- 5 array(
	 *				$fieldName => array($operator => $value, $operator => $value, ...),
	 *				$fieldName => array($operator => $value, ...)
	 *				...
	 *			)
	 * 
	 * @throws \rk\exception
	 */
	public function add() {
		
		$numargs = func_num_args();
		$args = func_get_args();
		
		// case 1
		if ($numargs > 1) {
			
			$field = $args[0];
			$value = $args[1];
				
			$operator = null;
			if(!empty($args[2])) {
				$operator = $args[2];
			}
				
			$this->criterias[] = new \rk\db\criteria($field, $value, $operator);
		}
		// case 2/3/4/5
		else {
			
			// case 2/3
			if($args[0] instanceof \rk\db\criteria || $args[0] instanceof \rk\db\criteriaSet) {
				// nothing to do if $args[0] is a known class
				$this->criterias[] = $args[0];
			}
			// case 4/5 
			elseif(is_array($args[0])) {
				
				foreach($args[0] as $name => $criterias) {
									
					// case 4
					if(!is_array($criterias)) {
						$this->add($name, $criterias);
					}
					// case 5
					else {
						foreach($criterias as $operator => $value) {
							$this->add($name, $value, $operator);
						}
					}
				}
				
			} else {
				throw new \rk\exception\invalidFormat('invalid format for $args[0]');
			}
		} 
	}
	
	public function getCriterias() {
		return $this->criterias;
	}
	
	public function getOperator() {
		return $this->operator;
	}
	
	public function getJSONFormatted() {
		$array = array(
			'o'	=> $this->getOperator(),
			'c'	=> $this->getJSONFormattedCriterias(),
		);
		return json_encode($array);
	}
	
	protected function getJSONFormattedCriterias() {
		$array = array();
		foreach($this->getCriterias() as $oneCriteria) {
			if($oneCriteria instanceof \rk\db\criteriaSet) {
				$set = array(
					'o'	=> $oneCriteria->getOperator(),
					'c'	=> $oneCriteria->getJSONFormattedCriterias(),
				);
				$array[] = $set;
			} else {
				$crit = array(
					'f'	=> $oneCriteria->getName(),
					'o'	=> $oneCriteria->getOperator(),
					'v'	=> $oneCriteria->getValue(),
				);
				$array[] = $crit;
			}
		}
		return $array;
	}
	
	/**
	 * return a flat array indexed by name for each criteria that has to be highlighted for current criteriaSet
	 */
	public function getSearchHighlights() {
		$return = array();		
		$this->_getSearchHighlights($return);

		return $return;
	}
	
	protected function _getSearchHighlights(&$values) {
		foreach($this->getCriterias() as $one) {
			if($one instanceof \rk\db\criteriaSet) {
				$one->_getSearchHighlights($values);
			} else {
				if($one->getOperator() == \rk\db\builder::OPERATOR_LIKE
				|| $one->getOperator() == \rk\db\builder::OPERATOR_ILIKE) {
					if(empty($values[$one->getName()])) {
						$values[$one->getName()] = array();
					}
					$values[$one->getName()][] = array(
						'value' => $one->getValue(),
						'operator'	=> $one->getOperator(),
					);
				}
			}
		}
	}
	
}