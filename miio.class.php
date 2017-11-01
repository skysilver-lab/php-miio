<?php
/* 
*	Класс для работы с wifi-устройствами из экосистемы xiaomi по протоколу miIO.
*
*	+ прием udp-пакетов из сокета
*	+ отправка udp-пакетов в сокет
*	+ процедура рукопожатия (handshake)
*	+ отправка сообщений устройству
*	+ прием ответов от устройства
*	+ поиск устройств (handshake-discovery)
*	
*	https://github.com/aholstenson/miio
*	https://github.com/rytilahti/python-miio
*	https://github.com/marcelrv/XiaomiRobotVacuumProtocol
*
*	Copyright (C) 2017 Agaphonov Dmitri aka skysilver [mailto:skysilver.da@gmail.com]
*/

require('mipacket.class.php');

const 	MIIO_PORT = '54321';

const 	HELLO_MSG = '21310020ffffffffffffffffffffffffffffffffffffffffffffffffffffffff';

class miIO {
	
	public 	$ip = '';
	public 	$token = '';
	public 	$debug = '';
	public 	$send_timeout = 5;
	public 	$disc_timeout = 15;

    public 	$msg_id = '1';
	public	$useAutoMsgID = false;

	public 	$data = '';
	public 	$sock = NULL;
	
	private $miPacket = NULL;

	
	public function __construct($ip = NULL, $bind_ip = NULL, $token = NULL, $debug = false) {
		
		$this->debug = $debug;
		
		$this->miPacket = new miPacket();
		
		if ($ip != NULL) $this->ip = $ip;
		
		if ($bind_ip != NULL) $this->bind_ip = $bind_ip;
		 else $this->bind_ip = '0.0.0.0';
		
		if ($token != NULL) $this->token = $token;
		
		if ($this->debug) {
			if ($this->ip == NULL) echo "Режим широковещательного поиска устройств" . PHP_EOL;
			 else echo "Соединение с устройством IP $this->ip" . PHP_EOL;
			echo "Статус отладки [$this->debug]" . PHP_EOL;
		}
		
		$this->sockCreate();
		
	}
	
	public function __destruct() {
		
		@socket_shutdown($this->sock, 2);
		@socket_close($this->sock);
		
	}

	/*
		Создание udp4 сокета.
	*/
	
	public function sockCreate() {
	
		if (!($this->sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP))) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);
			if ($this->debug) echo "Ошибка создания сокета - [socket_create()] [$errorcode] $errormsg" . PHP_EOL;
			die("Ошибка создания сокета - [socket_create()] [$errorcode] $errormsg \n");
		} else { if ($this->debug) echo 'Сокет успешно создан' . PHP_EOL; }
	
	}
	
	/*
		Установка параметров сокета - таймаут.
	*/
	
	public function sockSetTimeout($timeout = 2) {
	
		if (!socket_set_option($this->sock, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $timeout, "usec" => 0))) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);
			if ($this->debug) echo "Ошибка установки параметра SO_RCVTIMEO сокета - [socket_create()] [$errorcode] $errormsg" . PHP_EOL;
		} else { if ($this->debug) echo 'Параметр SO_RCVTIMEO сокета успешно задан' . PHP_EOL; }
	
	}
	
	/*
		Установка параметров сокета - броадкаст.
	*/
	
	public function sockSetBroadcast() {
	
		if (!socket_set_option($this->sock, SOL_SOCKET, SO_BROADCAST, 1)) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);
			if ($this->debug) echo "Ошибка установки параметра SO_BROADCAST сокета - [socket_create()] [$errorcode] $errormsg" . PHP_EOL;
		} else { if ($this->debug) echo 'Параметр SO_BROADCAST сокета успешно задан' . PHP_EOL; }
	
	}
	
	/*
		Поиск устройства и начало сессии с ним.
	*/
	
	public function discover($ip = NULL) {
		
		if ($ip != NULL) {

			if ($this->debug) echo PHP_EOL . "Проверяем доступность устройства $ip" . PHP_EOL;
			
			$this->sockSetTimeout($this->send_timeout);
			
			if ($this->debug) echo " >>>>> Отправляем hello-пакет на $ip с таймаутом $this->send_timeout" . PHP_EOL;
			
			$helloPacket = hex2bin(HELLO_MSG);
			
			if(!($bytes = socket_sendto($this->sock, $helloPacket, strlen($helloPacket), 0, $ip, MIIO_PORT))) {
				$errorcode = socket_last_error();
				$errormsg = socket_strerror($errorcode);
				if ($this->debug) echo "Не удалось отправить данные в сокет [$errorcode] $errormsg" . PHP_EOL;
			} else { if ($this->debug) echo " >>>>> Отправлено в сокет $bytes байт" . PHP_EOL; }
						
		    $buf = '';
			if (($bytes = @socket_recvfrom($this->sock, $buf, 4096, 0, $remote_ip, $remote_port)) !== false) {
				if ($buf != '') {
					if ($this->debug) {
						echo " <<<<< Получен ответ от IP $remote_ip с порта $remote_port" . PHP_EOL;
						if ($this->debug) echo "Прочитано $bytes байта из сокета" . PHP_EOL;
					}
					$this->miPacket->msgParse(bin2hex($buf));
					if ($this->debug) {
						$this->miPacket->printHead();
						$timediff = hexdec($this->miPacket->ts) - time();
						$ts_server = time();
						echo 'ts_server: ' . dechex($ts_server) . ' --> ' . $ts_server . ' секунд' . ' --> ' . date('Y-m-d H:i:s', $ts_server) . PHP_EOL;
						echo 'timediff: ' . $timediff . PHP_EOL;
					}
					return true;
				}
			} else if ($bytes === 0 || $bytes === false) {
				$errorcode = socket_last_error();
				$errormsg = socket_strerror($errorcode);
				if ($this->debug) echo "Ошибка чтения из сокета [$errorcode] $errormsg" . PHP_EOL;
				return false;
			}
		} else {

			if ($this->debug) echo PHP_EOL . 'Поиск доступных устройств в локальной сети (handshake discovery)' . PHP_EOL;

			$this->sockSetTimeout($this->disc_timeout);
			
			$this->sockSetBroadcast();
			
 			if( !@socket_bind($this->sock, $this->bind_ip , 0) ) {
				$errorcode = socket_last_error();
				$errormsg = socket_strerror($errorcode);
				if ($this->debug) echo "Не удалось привязать сокет к адресу $this->bind_ip [$errorcode] $errormsg" . PHP_EOL;
			} else { if ($this->debug) echo "Сокет успешно привязан к адресу $this->bind_ip" . PHP_EOL; }
			
			$ip = '255.255.255.255';

			if ($this->debug) echo " >>>>> Отправляем hello-пакет на $ip с таймаутом $this->disc_timeout" . PHP_EOL;
			
			$helloPacket = hex2bin(HELLO_MSG);
			
			if(!($bytes = socket_sendto($this->sock, $helloPacket, strlen($helloPacket), 0, $ip, MIIO_PORT))) {
				$errorcode = socket_last_error();
				$errormsg = socket_strerror($errorcode);
				if ($this->debug) echo "Не удалось отправить данные в сокет [$errorcode] $errormsg" . PHP_EOL;
			} else { if ($this->debug) echo " >>>>> Отправлено в сокет $bytes байт" . PHP_EOL; }
			
			$buf = '';
		    $count = 0;
			$devinfo = array();
			$devices = array();
			
			while ($bytes = @socket_recvfrom($this->sock, $buf, 4096, 0, $remote_ip, $remote_port)) {
				if ($buf != '') {
					if ($this->debug) {
						echo ($count+1) . " <<<<< Получен ответ от IP $remote_ip с порта $remote_port" . PHP_EOL;
						if ($this->debug) echo "Прочитано $bytes байта из сокета" . PHP_EOL;
					}
					$this->miPacket->msgParse(bin2hex($buf));

					if ($this->debug) {
						$this->miPacket->printHead();
						$timediff = hexdec($this->miPacket->ts) - time();
						$ts_server = time();
						echo 'ts_server: ' . dechex($ts_server) . ' --> ' . $ts_server . ' секунд' . ' --> ' . date('Y-m-d H:i:s', $ts_server) . PHP_EOL;
						echo 'timediff: ' . $timediff . PHP_EOL;
					}
					
					$devinfo = $this->miPacket->info;
					$devinfo += ["ip" => $remote_ip];
					$devices[] = json_encode($devinfo);
				}
				$count += 1;
				if ($bytes === 0 || $bytes === false) {
					$errorcode = socket_last_error();
					$errormsg = socket_strerror($errorcode);
					if ($this->debug) echo "Ошибка чтения из сокета [$errorcode] $errormsg" . PHP_EOL;
				}
			}
			
			if(!empty($devices)) $this->data = '{"devices":'. json_encode($devices) .'}';
		
			if ($count != 0 || !empty($this->data)) return true;
			 else return false;
		}
	}
	
	public function fastDiscover() {
		
		$timeout = 2;
		
		$this->sockSetTimeout($timeout);
		$this->sockSetBroadcast();
			
		if( !@socket_bind($this->sock, $this->bind_ip , 0) ) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);
			if ($this->debug) echo " --> Не удалось привязать сокет к адресу $this->bind_ip [$errorcode] $errormsg" . PHP_EOL;
		} else { if ($this->debug) echo " --> Сокет успешно привязан к адресу $this->bind_ip" . PHP_EOL; }
			
		$ip = '255.255.255.255';

		if ($this->debug) echo " --> Отправляем hello-пакет на $ip с таймаутом $timeout" . PHP_EOL;
			
		$helloPacket = hex2bin(HELLO_MSG);
			
		if(!($bytes = socket_sendto($this->sock, $helloPacket, strlen($helloPacket), 0, $ip, MIIO_PORT))) {
			$errorcode = socket_last_error();
			$errormsg = socket_strerror($errorcode);
			if ($this->debug) echo " --> Не удалось отправить данные в сокет [$errorcode] $errormsg" . PHP_EOL . PHP_EOL;
		} else { if ($this->debug) echo " --> Отправлено в сокет $bytes байт" . PHP_EOL . PHP_EOL; }
		
	}
	
	/*
		Сокеты. Запись и чтение.
	*/

	public function socketWriteRead($msg) {
		
		if ($this->discover($this->ip)) {

			if ($this->debug) echo PHP_EOL . "Устройство $this->ip доступно" . PHP_EOL;

			$this->sockSetTimeout($this->send_timeout);
			
			if ($this->token != NULL) {
				if(!$this->miPacket->setToken($this->token)) {
					if ($this->debug) echo 'Неверный формат токена!' . PHP_EOL;
					die('Неверный формат токена!\n');
				} else {
					if ($this->debug) echo 'Используется токен, указанный вручную, - ' . $this->token . PHP_EOL;
				}
			} else {
				if ($this->debug) echo 'Используется токен, полученный от устройства, - ' . $this->miPacket->getToken() . PHP_EOL;
			}
			
			if ($this->debug) echo " >>>>> Отправляем пакет на $this->ip с таймаутом $this->send_timeout" . PHP_EOL;
			
			$packet = hex2bin($this->miPacket->msgBuild($msg));
			
			if ($this->debug) {
				$this->miPacket->printHead();
				$timediff = hexdec($this->miPacket->ts) - time();
				echo 'data: ' . $this->miPacket->data . PHP_EOL;
				$ts_server = time();
				echo 'ts_server: ' . dechex($ts_server) . ' --> ' . $ts_server . ' секунд' . ' --> ' . date('Y-m-d H:i:s', $ts_server) . PHP_EOL;
				echo 'timediff: ' . $timediff . PHP_EOL;
			}
			
			if(!($bytes = socket_sendto($this->sock, $packet, strlen($packet), 0, $this->ip, MIIO_PORT))) {
				$errorcode = socket_last_error();
				$errormsg = socket_strerror($errorcode);
				if ($this->debug) echo "Не удалось отправить данные в сокет [$errorcode] $errormsg" . PHP_EOL;
			} else { if ($this->debug) echo " >>>>> Отправлено в сокет $bytes байт" . PHP_EOL; }
			
			$this->miPacket->data = '';
			
		    $buf = '';
			if (($bytes = @socket_recvfrom($this->sock, $buf, 4096, 0, $remote_ip, $remote_port)) !== false) {
				if ($buf != '') {
					if ($this->debug) {
						echo " <<<<< Получен ответ от IP $remote_ip с порта $remote_port" . PHP_EOL;
						if ($this->debug) echo "Прочитано $bytes байта из сокета" . PHP_EOL;
					}
					$this->miPacket->msgParse(bin2hex($buf));
					if ($this->debug) $this->miPacket->printPacket();
					$data_dec = $this->miPacket->decryptData($this->miPacket->data);	
					if ($this->debug) echo "Расшифрованные данные: $data_dec" . PHP_EOL;
					//проверить json на валидность
					json_decode($data_dec);
					if ($jsonErrCode = json_last_error() !== JSON_ERROR_NONE) {
						$jsonErrMsg = $this->jsonLastErrorMsg();
						if ($this->debug) echo "Данные JSON не валидны. Ошибка: $jsonErrMsg" . PHP_EOL;
						if ($jsonErrCode == JSON_ERROR_CTRL_CHAR) {
							// если ошибка в управляющих символах, то удаляем хвосты в начале и в конце и возвращаем
							if ($this->debug) echo 'Выполняем trim()' . PHP_EOL;
							$this->data = trim($data_dec);
							return true;
						} else {
							// если иная ошибка, возвращаем как есть для обработки на верхнем уровне
							$this->data = $data_dec;
						}
					} else {
						// если ошибок нет, то возвращаем как есть
						if ($this->debug) echo 'Данные JSON валидны.' . PHP_EOL;
						$this->data = $data_dec;
					}
					return true;
				}
			} else if ($bytes === 0 || $bytes === false) {
				$errorcode = socket_last_error();
				$errormsg = socket_strerror($errorcode);
				if ($this->debug) echo "Ошибка чтения из сокета [$errorcode] $errormsg" . PHP_EOL;
				return false;
			}
		} else {
			if ($this->debug) echo "Устройство по адресу $this->ip не ответило на hello-запрос!" . PHP_EOL;
			return false;
		}
	}
	
	/*
		Отправка сообщения (метод и параметры раздельно) устройству и прием ответа.
	*/
	
	public function msgSendRcv($command, $parameters = NULL, $id = 1) {
	
		if (isset($id) && ($id > 0) && !$this->useAutoMsgID) $this->msg_id = $id;
		 else if ($this->useAutoMsgID) $this->msg_id = $this->getMsgID($this->ip);
		
		$msg = '{"id":' . $this->msg_id . ',"method":"'. $command . '"}';
			
		if ($parameters != NULL) {
			$msg = '{"id":' . $this->msg_id . ',"method":"'. $command . '","params":' . $parameters . '}';
		}
			
		if ($this->debug) echo "Команда для отправки - $msg" . PHP_EOL;
			
		return $this->socketWriteRead($msg);

	}
	
	/*
		Отправка сообщения (как есть) устройству и прием ответа.
	*/
	
	public function msgSendRcvRaw($msg) {
	
		if (substr_count($msg, "'") > 0 ) $msg = str_replace("'", '"', $msg);
		
		if ($this->debug) echo "Команда для отправки - $msg" . PHP_EOL;
		
		return $this->socketWriteRead($msg);
 
	}
	
	/*
		Получить новый идентификатор для команды.
	*/
	
	public function getMsgID($ip) {
	
		if (file_exists ('id.json')) {
			$file = file_get_contents('id.json');
			$ids = json_decode($file, TRUE);
		} else {
			file_put_contents('id.json', '');
			$ids = array();
		}

		if (!empty($ids)) {
			if (array_key_exists($ip, $ids)) {
				if ($ids[$ip] > 1000) $ids[$ip] = 1;
				 else $ids[$ip] += 1;
			} else {
				$ids += [$ip => 1];
			}
		} else {
			$ids = [$ip => 1];
		}

		file_put_contents('id.json', json_encode($ids));
	
		return $ids[$ip];
	}
	
	/*
		Получить описание ошибки JSON.
		(определяем функцию, если старая версия PHP)
	*/
	
	public function jsonLastErrorMsg() {
	
		if (!function_exists('json_last_error_msg')) {
		
			function json_last_error_msg() {
			
				static $ERRORS = array(JSON_ERROR_NONE => 'No error has occurred',
										JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
										JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
										JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
										JSON_ERROR_SYNTAX => 'Syntax error',
										JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded');

				$error = json_last_error();
				return isset($ERRORS[$error]) ? $ERRORS[$error] : 'Unknown error';
			}
		}
						
		return json_last_error_msg();
		
	}
	
	/*
		Получить miIO-сведения об устройстве.
	*/
	
	public function getInfo($msg_id = 1) {
	
		return $this->msgSendRcv('miIO.info', '[]', $msg_id);
	 
	}
	
}
