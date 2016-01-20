<?php

namespace rk\form\widget;

class date extends \rk\form\widget {
	
	public function setValue($value) {
		$this->value = \rk\date::get($value);
	}

	public function useLabelAsPlaceholder() {
		$this->setParam('placeholder', i18n($this->getParam('label')));
		$this->setParam('label', '');
	}
}