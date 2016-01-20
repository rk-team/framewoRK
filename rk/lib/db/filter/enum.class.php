<?php

namespace rk\db\filter;

use \rk\db\builder as builder;

class enum extends \rk\db\filter {
	
	protected
		$defaultOperator = builder::OPERATOR_EQUAL,
		$allowedOperators = array(
			builder::OPERATOR_EQUAL, 
			builder::OPERATOR_NOTEQUAL,
		);

}