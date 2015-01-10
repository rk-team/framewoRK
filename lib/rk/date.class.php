<?php

namespace rk;

class date extends \DateTime {
	

	/**
	 * builds an \rk\date with given values
	 * @param string $value
	 * @param string $timeZone
	 * @param string $format
	 * @throws \rk\exception
	 */
	public static function get($value, $timeZone = null, $format = null) {
		
		if(is_null($timeZone)) {
			// use PHP's default TZ if none specified
			$strDefaultTimeZone = date_default_timezone_get();
			$timeZone = new \DateTimeZone($strDefaultTimeZone);
		}
		
		if($value instanceof \rk\date) {
			return $value;
		}
		
		if($value == '0000-00-00 00:00:00' || $value == '0000-00-00') {
			// for values with no sense, we return null
			return null;
		} else{
			if(empty($format)) {
				// try to guess the format
				// DB with time
				$date = \rk\date::createFromFormat(self::getDBFormat(), $value, $timeZone);
				if(!$date) {
					// DB without time
					$date = \rk\date::createFromFormat(self::getDBFormat(false), $value, $timeZone);
					if($date) {
						// force time to 0 as current time is used by default
						$date->setTime(0, 0, 0);
					}
				}
				
				if(!$date) {
					// user format with time
					$date = \rk\date::createFromFormat(self::getUserFormat(), $value, $timeZone);
				}
				
				if(!$date) {
					// user format without time
					$date = \rk\date::createFromFormat(self::getUserFormat(false), $value, $timeZone);
					if($date) {
						// force time to 0 as current time is used by default
						$date->setTime(0, 0, 0);
					}
				}
	
			} else {
				// use given format
				$date = \rk\date::createFromFormat($format, $value, $timeZone);
			}
		}
		
		
		if(!$date) {
			return null;
		}

		
		// convert the DateTime to a \rk\date object
		$rkDate = new \rk\date($date->format('Y-m-d H:i:s'), $timeZone);
		return $rkDate;
	}
	
	
	
	
	public static function getInterval($dateStart, $dateEnd) {
		$dateStart = \rk\date::get($dateStart);
		$dateEnd = \rk\date::get($dateEnd);
		if(empty($dateStart) || empty($dateEnd)) {
			return false;
		}
		$interval = $dateStart->diff($dateEnd);
		
		return self::formatDuration($interval);
	}
	
	public static function formatDuration($interval) {
		if(!$interval instanceof \DateInterval) {
			// $interval can be either a \DateInterval or a number of seconds
			$pos = strrpos($interval, '.');
			if($pos !== false) {
				$decimal = substr($interval, $pos + 1);
				if($decimal > 0.5) {
					$interval++;
				}
				$interval = substr($interval, 0, $pos);
			}
			$interval = abs($interval);
			$interval = new \rk\DateInterval('PT' . $interval . 'S');
			$interval->recalculate();
		}
		$return = '';
		
		$data = array(
			'y' => 'date.year_short',
			'm' => 'date.month_short',
			'd' => 'date.day_short',
			'h' => 'date.hour_short',
			'i' => 'date.minute_short',
			's' => 'date.second_short',
		);
		
		$count = 0;
		foreach($data as $key => $value) {
			$val = $interval->$key;
			if(!empty($val) && $count < 2) {
				$return .= $interval->format('%' . $key) . ' ' . i18n($value) . ' ';
				$count++;
			}
		}
		
		if(empty($return)) {
			$return .= '0 ' . i18n('date.second_short') . ' ';
		} else if ($interval->invert == 1) {
			$return = ' - ';
		}
				
		return $return;
	}
	
	
	public function __toString() {
		return $this->userFormat();
	}
	
	public static function getDBFormat($withTime = true) {
		$format = 'Y-m-d';
		if($withTime) {
			$format .= ' H:i:s';
		}
		
		return $format;
	}
	
	public static function getUserFormat($withTime = true) {
		$language = \rk\manager::getUser()->getLanguage();
		
		$format = 'm/d/Y';
		if($language == 'fr') {
			$format = 'd/m/Y';
		}
		
		if($withTime) {
			$format .= ' H:i:s';
		}
		
		return $format;
	}
	
	
	public static function createFromDB($value, $withTime = true) {
		$format = self::getDBFormat($withTime);
		
		$date = self::get($value, null, $format);
		if(!$withTime) {
			$date->setTime(0, 0, 0);
		}
		
		return $date;
	}
	
	public static function createFromUserFormat($value, $withTime = true) {
		$format = self::getUserFormat($withTime);
		
		$date = self::get($value, null, $format);
		if(!$withTime) {
			$date->setTime(0, 0, 0);
		}
		
		return $date;
	}
	
// 	public static function userFormat($value, $withTime = true) {
// 		$format = self::getUserFormat($withTime);

// 		return self::format($value, $format);
// 	}
// 	public static function dbFormat($value, $withTime = true) {
// 		$format = self::getDBFormat($withTime);
		
// 		return self::format($value, $format);
// 	}
	
	
// 	public static function format($value, $format) {
// 		$date = \rk\date::get($value);
		
// 		return $date->format($format);
// 	}
	
	public function userFormat($withTime = true) {
		$format = self::getUserFormat($withTime);
		
		return $this->format($format);
	}
	public function dbFormat($withTime = true) {
		$format = self::getDBFormat($withTime);
		
		return $this->format($format);
	}
	
}
