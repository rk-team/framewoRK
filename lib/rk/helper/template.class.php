<?php

namespace rk\helper;

class template {

	public function includeTpl($tplPath, array $tplParams = array()) {
		
		$tplPath = \rk\manager::getTemplatePath($tplPath);
		
		return \rk\helper\output::getOutput($tplPath, $tplParams);
	}

	public function includePagerTpl($tplPath, array $tplParams = array()) {
		
		$tplPath = \rk\manager::getPagerTemplatePath($tplPath);
		
		return \rk\helper\output::getOutput($tplPath, $tplParams);
	}

	public function includeFormTpl($tplPath, array $tplParams = array()) {
		
		$tplPath = \rk\manager::getFormTemplatePath($tplPath);
		
		return \rk\helper\output::getOutput($tplPath, $tplParams);
	}
	
	public function includeFormWidgetTpl($tplPath, array $tplParams = array()) {
		
		$tplPath = \rk\manager::getFormWidgetTemplatePath($tplPath);
		
		return \rk\helper\output::getOutput($tplPath, $tplParams);
	}
	
	public function includeActionTpl($moduleName, $tplPath, array $tplParams = array()) {
	
		$tplPath = \rk\manager::getActionTemplatePath($moduleName, $tplPath);
	
		return \rk\helper\output::getOutput($tplPath, $tplParams);
	}
	
	public function includeAction($module, $action, array $params = array()) {
		return \rk\manager::includeAction($module, $action, $params);
	}
	
}
