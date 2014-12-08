<?php

namespace rk\db\filter;

use \rk\db\builder as builder;

class integer extends \rk\db\filter {
	
	protected
		$defaultOperator = builder::OPERATOR_EQUAL,
		$allowedOperators = array(
			builder::OPERATOR_EQUAL, 
			builder::OPERATOR_NOTEQUAL,
			builder::OPERATOR_GREATER,
			builder::OPERATOR_LOWER,
			builder::OPERATOR_GREATEREQUAL,
			builder::OPERATOR_LOWEREQUAL
		);

}