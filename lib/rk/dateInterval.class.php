<?php 

namespace rk;

class dateInterval extends DateInterval {
	
	public function to_seconds() {
		return ($this->y * 365 * 24 * 60 * 60) +
			($this->m * 30 * 24 * 60 * 60) +
			($this->d * 24 * 60 * 60) +
			($this->h * 60 * 60) +
			($this->i * 60) +
			$this->s;
	}
	
	public function recalculate() {
		$seconds = $this->to_seconds();
		$this->y = floor($seconds/60/60/24/365);
		$seconds -= $this->y * 31536000;
		$this->m = floor($seconds/60/60/24/30);
		$seconds -= $this->m * 2592000;
		$this->d = floor($seconds/60/60/24);
		$seconds -= $this->d * 86400;
		$this->h = floor($seconds/60/60);
		$seconds -= $this->h * 3600;
		$this->i = floor($seconds/60);
		$seconds -= $this->i * 60;
		$this->s = $seconds;
	}
	
}
