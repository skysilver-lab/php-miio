<?php
/* 
*	Класс для работы с пылесосом Xiaomi Mi Robot Vacuum по протоколу miIO.
*
*	Copyright (C) 2017 Agaphonov Dmitri aka skysilver [mailto:skysilver.da@gmail.com]
*
*/

include_once('miio.class.php');

class miRobotVacuum {
	
	public 	$ip = '';
	public 	$token = '';
	public 	$debug = '';
	public 	$error = '';
	
	public 	$status = array();
	
	public 	$dev = NULL;
	
	private	$state_codes = array('0' => 'Unknown',
					'1' => 	'Initiating',
					'2' => 	'Sleeping',
					'3' => 	'Waiting',
					'4' => 	'Unknown',
					'5' => 	'Cleaning',
					'6' => 	'Back to home',
					'7' => 	'Unknown',
					'8' => 	'Charging',
					'9' => 	'Charging Error',
					'10' => 'Pause',
					'11' => 'Spot Cleaning',
					'12' => 'In Error',
					'13' => 'Shutting down',
					'14' => 'Updating',
					'15' => 'Docking',
					'100' => 'Full');

	private $error_codes = array('0' => 'No error',
					'1' => 	'Laser distance sensor error',
					'2' => 	'Collision sensor error',
					'3' => 	'Wheels on top of void, move robot',
					'4' => 	'Clean hovering sensors, move robot',
					'5' => 	'Clean main brush',
					'6' => 	'Clean side brush',
					'7' => 	'Main wheel stuck',
					'8' => 	'Device stuck, clean area',
					'9' => 	'Dust collector missing',
					'10' => 'Clean filter',
					'11' => 'Stuck in magnetic barrier',
					'12' => 'Low battery',
					'13' => 'Charging fault',
					'14' => 'Battery fault',
					'15' => 'Wall sensors dirty, wipe them',
					'16' => 'Place me on flat surface',
					'17' => 'Side brushes problem, reboot me',
					'18' => 'Suction fan problem',
					'19' => 'Unpowered charging station');
	
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
		Получить расширенные сведения miIO
	*/
	
	public function getInfo($msg_id = 1) {
	
		if ($this->dev->getInfo($msg_id)) return $this->dev->data;
		 else return false;
	
	}
	
	/*
		Получить текущий статус:
			state - код состояния
			state_text - состояние
			battery - заряд батареи
			fan_power - мощность турбины
			error_code - код ошибки
			error_text - описание ошибки
			clean_area - площадь уборки, кв. см.
			clean_time - время уборки, сек.
			dnd_enabled - режим "не беспокоить"
			in_cleaning - в процессе уборки или нет
			map_present - есть карта или нет
			msg_ver - версия команд
			msg_seq - счетчик команд
	*/
	
	public function getStatus($msg_id = 1) {
	
		$result = $this->dev->msgSendRcv('get_status', '[]', $msg_id);
		
		if ($result) {
			if ($this->dev->data != '') {
				$res = json_decode($this->dev->data, true);
				if (isset($res['result'][0])) {
					foreach($res['result'][0] as $key => $value) {
						$this->status[$key] = $value;
						if ($key == 'state') {
							if (array_key_exists($value, $this->state_codes)) $this->status['state_text'] = $this->state_codes[$value];
						}
						if ($key == 'error_code') {
							if (array_key_exists($value, $this->error_codes)) $this->status['error_text'] = $this->error_codes[$value];
						}
					} 
					return true;
				} else if (isset($res['error'])) {
					$this->error = $this->dev->data;
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
		Начать уборку
	*/
	
	public function start($msg_id = 1) {
	
		$result = $this->dev->msgSendRcv('app_start', '[]', $msg_id);
		return $this->verify($result);

	}
	
	/*
		Завершить уборку
	*/
	
	public function stop($msg_id = 1) {
	
		$result = $this->dev->msgSendRcv('app_stop', '[]', $msg_id);
		return $this->verify($result);
	
	}
	
	/*
		Приостановить уборку
	*/
	
	public function pause($msg_id = 1) {
	
		$result = $this->dev->msgSendRcv('app_pause', '[]', $msg_id);
		return $this->verify($result);
	
	}
	
	/*
		Убрать вокруг себя
	*/
	
	public function cleanSpot($msg_id = 1) {
	
		$result = $this->dev->msgSendRcv('app_spot', '[]', $msg_id);
		return $this->verify($result);
	
	}
	
	/*
		Вернуться на базу и встать на зарядку
	*/
	
	public function charge($msg_id = 1) {
	
		$result = $this->dev->msgSendRcv('app_charge', '[]', $msg_id);
		return $this->verify($result);
		
	}
	
	/*
		Поиск пылесоса
	*/
	
	public function findMe($msg_id = 1) {
	
		$result = $this->dev->msgSendRcv('find_me', '[]', $msg_id);
		return $this->verify($result);
	
	}
	
	/*
		Проверка ответа
	*/
	
	public function verify ($result) {

		if ($result) {
			if ($this->dev->data != '') {
				$res = json_decode($this->dev->data);
				if($res instanceof \stdClass && property_exists($res, 'result')){
					if(is_array($res->{'result'}) && in_array('ok', $res->{'result'})) {
						return true;
					}
					elseif (isset($res->{'result'}) && ($res->{'result'} == 0)) return true;
				} else {
					$this->error = $this->dev->data;
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
