<?php
/* 
*	Класс для работы с настольными лампами Xiaomi Philips Eyecare Smart Lamp 2 по протоколу miIO.
*
*	Copyright (C) 2017 Agaphonov Dmitri aka skysilver [mailto:skysilver.da@gmail.com]
*
*/

include_once('miio.class.php');

class philipsEyecare2 {
	
	public 	$ip = '';
	public 	$token = '';
	public 	$debug = '';
	public 	$error = '';
	
	public 	$status = array('power' => '',
							'bright' => '',
							'notifystatus' => '',
							'ambstatus' => '',
							'ambvalue' => '',
							'eyecare' => '',
							'scene_num' => '',
							'bls' => '',
							'dvalue' => '');
	
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
		Активирует авто-формирование уникальных ID сообщений с их сохранением в файл id.json
	*/
	
	public function enableAutoMsgID() {
	
		$this->dev->useAutoMsgID = true;
	
	}
	
	/*
		Деактивирует авто-формирование уникальных ID сообщений с их сохранением в файл id.json
		ID сообщений необходимо передавать в виде аргумента при каждой отправке команды,
		либо не указывать вообще, тогда ID будет 1 для всех сообщений.
	*/
	
	public function disableAutoMsgID() {
	
		$this->dev->useAutoMsgID = false;
	
	}
	
	/*
		Получить расширенные сведения
	*/
	
	public function getInfo($msg_id = 1) {
	
		if ($this->dev->getInfo($msg_id)) return $this->dev->data;
		 else return false;
	
	}
	
	/*
		Получить текущий статус:
			power - питание (on или off), 
			bright - яркость (от 1 до 100), 
			notifystatus - напоминание об усталости глаз,
			ambstatus - доп. боковая подсветка (on или off),
			ambvalue - яркость доп. боковой подсветки (от 1 до 100),
			eyecare - смарт режим безопасный для глаз (???),
			scene_num - номер сцены (от 1 до 3, study, reading, phone)
			bls - режим смарт-ночника (???),
			dvalue - таймер на выключение, макс. 60 минут (в минутах от 1 до 60).
	*/
	
	public function getStatus($msg_id = 1) {
	
		$result = $this->dev->msgSendRcv('get_prop', '["power","bright","notifystatus","ambstatus","ambvalue","eyecare","scene_num","bls","dvalue"]', $msg_id);
		
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
				$this->error = 'Нет данных';
				return false;
			}
		} else {
			$this->error = 'Ответ не получен';
			return false;
		}
		
	}
	
	/*
		Включить
	*/
	
	public function powerOn($msg_id = 1) {
	
		$result = $this->dev->msgSendRcv('set_power', '["on"]', $msg_id);
		return $this->verify($result);

	}
	
	/*
		Выключить
	*/
	
	public function powerOff($msg_id = 1) {
	
		$result = $this->dev->msgSendRcv('set_power', '["off"]', $msg_id);
		return $this->verify($result);
	
	}
	
	/*
		Установка яркости
	*/
	
	public function setBrightness($level = 50, $msg_id = 1) {
	
		if ( ($level < 1) or ($level > 100) ) $level = 50;
		$result = $this->dev->msgSendRcv('set_bright', "[$level]", $msg_id);
		return $this->verify($result);
	
	}
	
	/*
		Проверка ответа
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
