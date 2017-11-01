<?php
/* 
*	Класс для работы с wifi-лампами Philips Light Bulb по протоколу miIO.
*
*	Copyright (C) 2017 Agaphonov Dmitri aka skysilver [mailto:skysilver.da@gmail.com]
*
*/

include_once('miio.class.php');

class philipsBulb {
	
	public 	$ip = '';
	public 	$token = '';
	public 	$debug = '';
	public 	$error = '';
	
	public 	$status = array('power' => '',
							'bright' => '',
							'cct' => '',
							'snm' => '',
							'dv' => '');
	
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
			cct - цветовая температура (от 1 до 100), 
			snm - номер сцены (от 1 до 4),
			dv - таймер на выключение, макс. 6 часов (в секундах от 1 до 21600)
	*/
	
	public function getStatus($msg_id = 1) {

		$result = $this->dev->msgSendRcv('get_prop', '["power","bright","cct","snm","dv"]', $msg_id);
		
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
		Установка цветовой температуры
	*/
	
	public function setColorTemperature($level = 50, $msg_id = 1) {
	
		if ( ($level < 1) or ($level > 100) ) $level = 50;
		$result = $this->dev->msgSendRcv('set_cct', "[$level]", $msg_id);
		return $this->verify($result);
	
	}
	
	/*
		Переключение сцен - ярко, ТВ, тепло, полноч.
	*/
	
	public function setScene($num = 1, $msg_id = 1) {
	
		if ( ($num < 1) or ($num > 4) ) $num = 1;
		$result = $this->dev->msgSendRcv('apply_fixed_scene', "[$num]", $msg_id);
		return $this->verify($result);
	
	}
	
	/*
		Установка таймера на выключение
	*/
	
	public function setDelayOff($seconds = 60, $msg_id = 1) {
		
		if ( ($seconds < 0) or ($seconds > 21600) ) $seconds = 60;
		$result = $this->dev->msgSendRcv('delay_off', "[$seconds]", $msg_id);
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
