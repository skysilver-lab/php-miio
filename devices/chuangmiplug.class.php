<?php
/* 
*	Класс для работы с wifi-лампами Philips Light Bulb по протоколу miIO.
*
*	Copyright (C) 2017 Agaphonov Dmitri aka skysilver [mailto:skysilver.da@gmail.com]
*
*/

include_once('miio.class.php');

class chuangMiPlug {
	
	public 	$ip = '';
	public 	$token = '';
	public 	$debug = '';
	public 	$error = '';
	
	public 	$status = array('power' => '');
	
	public 	$dev = NULL;
	
	public function __construct($ip = NULL, $bind_ip = NULL, $token = NULL, $debug = false) {
		
		$this->ip = $ip;
		$this->token = $token;
		$this->debug = $debug;
		
		if ($bind_ip != NULL) $this->bind_ip = $bind_ip;
		 else $this->bind_ip = '0.0.0.0';
		
		$this->dev = new miIO($this->ip, $this->bind_ip, $this->token, $this->debug);
		
	}

	/*
		Activates auto-generation of unique message IDs with their saving to the id.json file
	*/
	
	public function enableAutoMsgID() {
	
		$this->dev->useAutoMsgID = true;
	
	}
	
	/*
		Deactivates auto-generation of unique message IDs with their saving to the id.json file
		Message IDs must be passed as an argument each time a command is sent,
		or do not specify at all, then the ID will be 1 for all messages.
	*/
	
	public function disableAutoMsgID() {
	
		$this->dev->useAutoMsgID = false;
	
	}
	
	/*
		Get Advanced Information
	*/
	
	public function getInfo($msg_id = 1) {
	
		if ($this->dev->getInfo($msg_id)) return $this->dev->data;
		 else return false;
	
	}
	
	/*
		Get current status:
			power - power (on or off)
	*/
	
	public function getStatus($msg_id = 1) {

		$result = $this->dev->msgSendRcv('get_prop', '["power"]', $msg_id);
		
		if ($result) {
			if ($this->dev->data != '') {
				$res = json_decode($this->dev->data);
				if (isset($res->{'result'})) {
					$i = 0;
					foreach($this->status as $key => $value) { 
						$this->status[$key] = $res->{'result'}[$i];
						$i++;
					} 
					return true;
				} else if (isset($res->{'error'})) {
					$this->error = $res->{'error'}->{'message'};
					return false;
				}
			} else {
				$this->error = 'No data';
				return false;
			}
		} else {
			$this->error = 'No response received';
			return false;
		}
		
	}
	
	/*
		Power On
	*/
	
	public function powerOn($msg_id = 1) {
	
		$result = $this->dev->msgSendRcv('set_power', '["on"]', $msg_id);
		return $this->verify($result);

	}
	
	/*
		Power Off
	*/
	
	public function powerOff($msg_id = 1) {
	
		$result = $this->dev->msgSendRcv('set_power', '["off"]', $msg_id);
		return $this->verify($result);
	
	}
	
	
	/*
		Response Check
	*/
	
	private function verify ($result) {
		
		if ($result) {
			if ($this->dev->data != '') {
				$res = json_decode($this->dev->data);
				if (isset($res->{'result'})) {
					if ($res->{'result'}[0] == 'ok') return true;
					if ($res->{'result'}[0] == 'error') {
						$this->error = 'Unknown error.';
						return false;
					}
				} else if (isset($res->{'error'})) {
					$this->error = $res->{'error'}->{'message'};
					return false;
				}
			} else {
				$this->error = 'Нет данных';
				return false;
			}
		} else {
			$this->error = 'Ответ не получен';
			return false;
		}
		
	}
	
}
