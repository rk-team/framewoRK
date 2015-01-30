<?php

namespace rk;

class email {
	
	protected $subject;
	
	protected $content;
	
	
	public function __construct($to, $subject, $message, array $params = array()) {
		
		if(is_array($to)) {
			$to = implode(',', $to);
		}
		
		$this->to = $to;
		$this->subject = $subject;
		$this->message = $message;
		
		$from = \rk\manager::getConfigParam('email.default_from', '');
		$replyTo = \rk\manager::getConfigParam('email.default_replyto', '');
		$bcc = \rk\manager::getConfigParam('email.default_bcc', '');
		
		if(!empty($params['from'])) {
			$from = $params['from'];
		}
		if(!empty($params['replyTo'])) {
			$replyTo = $params['replyTo'];
		}

		
		if(empty($from)) {
			throw new \rk\exception('unknown "from" for email');
		}
		
		$headers = array(
			'From' => $from
		);
		
		if(!empty($replyTo)) {
			$headers['Reply-To'] = $replyTo;
		}
		if(!empty($bcc)) {
			$headers['Bcc'] = $bcc;
		}
		
		if(!empty($params['headers'])) {
			foreach($params['headers'] as $key => $value) {
				$headers[$key] = $value;
			}
		}
		
		if(!empty($params['HTML'])) {
			$headers['MIME-Version'] = '1.0';
			$headers['Content-type'] = 'text/html; charset=UTF-8';
		}
		
		$additionnalParams = '';
		$i = 0;
		$nbHeaders = count($headers);
		foreach($headers as $key => $value) {
			
			$additionnalParams .= $key . ': ' . $value;
			$i++;
			if($i < $nbHeaders) {
				$additionnalParams .= "\r\n";
			}
		}
		
		$this->additionnalParams = $additionnalParams;
	}
	
	public function send() {
// 		var_dump($this->to, $this->subject, $this->message, $this->additionnalParams);
// 		return true;
	    return mail($this->to, $this->subject, $this->message, $this->additionnalParams);
	}
	
}