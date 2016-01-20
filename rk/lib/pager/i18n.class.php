<?php

namespace rk\pager;


class i18n extends \rk\pager {

	protected function initColumns() {

		// add i18n specific columns
		$this->initColumnsFromI18nReference($this->getModel()->getReference('i18n'));
		
		// use standard process to add other columns
		parent::initColumns();
	}
	
	protected function initColumnsFromI18nReference(\rk\model\reference $ref) {
		
		// add a column for the first text field of the i18n table
		$params = array(
			'name' 			=> $ref->getReferencedTableAlias() . '.' . $ref->getDisplayField(),
			'label'			=> \rk\i18n::getORMKey($ref->getReferencedModel()->getDbConnectorName(), $ref->getReferencedTable(), $ref->getDisplayField()),
			'table'			=> $ref->getReferencedTableAlias(), 
		);
		
		$params['formatterParams'] = array(
			'table'	=> $ref->getReferencedTableAlias(), 
			'field'	=> $ref->getDisplayField(),
			'languageField'	=> $ref->getLanguageField(),
		);
		$params['formatter'] = function($row, $params) {
			$return = '';
			
			if(!empty($row[$params['table']])) {
				// try to get translation in user's language
				$userLanguage = \rk\manager::getUser()->getLanguage();
				if(!empty($row[$params['table']][$userLanguage])) {
					$i18nData = $row[$params['table']][$userLanguage];	
				} else {
					// else get the first translation
					$keys = array_keys($row[$params['table']]);
					$i18nData = $row[$params['table']][$keys[0]];
				}
				
				if(!empty($i18nData)) {
					$return = '[' . $i18nData[$params['languageField']] . '] ';
					$return .= $i18nData[$params['field']];
				}
			}
			
			return $return;
		};
		$this->setColumn($params);
		
		
		// add a column with the count a existing translations
		$params = array(
			'name' 			=> 'nb_i18n',
			'label'			=> 'pager.nb_i18n',
			'table'			=> $ref->getReferencedTableAlias(), 
		);
		
		$params['formatterParams'] = array( 
			'table'			=> $ref->getReferencedTableAlias(),
		);
		$params['formatter'] = function($row, $params) {
			return count($row[$params['table']]);
		};
		$this->setColumn($params);
		
		
		// add a column with the list of existing translations
		$params = array(
			'name' 			=> 'existing_i18n',
			'label'			=> 'pager.existing_i18n',
			'table'			=> $ref->getReferencedTableAlias(), 
		);
		
		$params['formatterParams'] = array( 
			'table'			=> $ref->getReferencedTableAlias(),
			'languageField'	=> $ref->getLanguageField()
		);
		$params['formatter'] = function($row, $params) {
			$list = '';
			
			if(!empty($row[$params['table']])) {
				foreach($row[$params['table']] as $oneI18nRow) {
					$list .= ' ' . $oneI18nRow[$params['languageField']];
				}
			}
			return $list;
		};
		$this->setColumn($params);
	}

}