<?php

namespace rk\helper;

class output {

	public static function getOutput($tplPath, array $tplParams) {
		if(!file_exists($tplPath) || !is_file($tplPath)) {
			throw new \rk\exception\system('unknown_template', array('tplPath' => $tplPath));
		}

		$tplParams['rkHelper'] = new \rk\helper\template();
		$tplParams['rkUser'] = \rk\manager::getUser();
		
// 		$start = microtime(true);
		
		ob_start();
		
		//transform $tplParams keys into symbole names
		extract($tplParams);
		unset($tplParams);
		
		include($tplPath);
		$output = ob_get_contents();
		ob_end_clean();

// 		$end = microtime(true);
// 		$duration = $end - $start;
// 		$tplPath = str_replace(\rk\manager::getRootDir(), '', $tplPath);
// 		\rk\webLogger::add(array('template' => $tplPath, 'selfDuration' => $duration), 'OUTPUT');
		
		return $output;
	}

}