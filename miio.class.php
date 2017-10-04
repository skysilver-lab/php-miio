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
*	ToDo: поиск устройств (mdns-discovery)
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
	public 	$disc_timeout = 2;

	public 	$_device_ts = NULL;
    public 	$_id = '1';
    public 	$_devtype = NULL;
    public 	$_serial = NULL;

	public 	$data = '';
	
	private $miPacket = NULL;

	
	public function __construct($ip = NULL, $token = NULL, $debug = false) {
		
		$this->debug = $debug;
		
		$this->miPacket = new miPacket();
		
		if ($ip != NULL) $this->ip = $ip;
		if ($token != NULL) $this->token = $token;
		
		if ($this->debug) {
			if ($this->ip == NULL) echo "Поиск устройств" . PHP_EOL;
			 else echo "Взаимодействие с устройством IP $this->ip" . PHP_EOL;
			echo "Статус отладки [$this->debug]" . PHP_EOL;
		}
	}

	/*
		Поиск устройства и начало сессии с ним.
	*/
	
	public function discover($ip = NULL) {
		
		if ($ip != NULL) {
			//handshake
			if ($this->debug) echo PHP_EOL . 'Проверяем доступность устройства ' . $ip . PHP_EOL;
			if (!($sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP))) {
				$errorcode = socket_last_error();
				$errormsg = socket_strerror($errorcode);
				if ($this->debug) echo "Ошибка создания сокета [$errorcode] $errormsg" . PHP_EOL;
				die("Ошибка создания сокета [$errorcode] $errormsg \n");
			}
			
			if ($this->debug) echo 'Сокет успешно создан' . PHP_EOL;

			socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $this->disc_timeout, "usec" => 0));
			
			if ($this->debug) echo "Отправляем hello-пакет на $ip с таймаутом $this->disc_timeout" . PHP_EOL;
			
			$helloPacket = hex2bin(HELLO_MSG);
			
			socket_sendto($sock, $helloPacket, strlen($helloPacket), 0, $ip, MIIO_PORT);
						
		    $buf = '';
			if (@socket_recvfrom($sock, $buf, 4096, 0, $remote_ip, $remote_port) == false) {
				$errorcode = socket_last_error();
				$errormsg = socket_strerror($errorcode);
				if ($this->debug) echo "Ошибка чтения из сокета [$errorcode] $errormsg" . PHP_EOL;
				socket_close($sock);
				return false;
			} else if ($buf != '') {
				if ($this->debug) echo "Получен ответ от IP $remote_ip" . PHP_EOL;
				$this->miPacket->msgParse(bin2hex($buf));
				if ($this->debug) $this->miPacket->printHead();
				socket_close($sock);
				return true;
			}
		} else {
			//broadcast discovery
			if ($this->debug) echo PHP_EOL . 'Поиск доступных устройств в локальной сети (handshake discovery)' . PHP_EOL;

			if (!($sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP))) {
				$errorcode = socket_last_error();
				$errormsg = socket_strerror($errorcode);
				if ($this->debug) echo "Ошибка создания сокета [$errorcode] $errormsg" . PHP_EOL;
				die("Ошибка создания сокета [$errorcode] $errormsg \n");
			}
			
			if ($this->debug) echo 'Сокет успешно создан' . PHP_EOL;

			socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $this->disc_timeout, "usec" => 0));
			socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
			
			$ip = '255.255.255.255';
			
			if ($this->debug) echo "Отправляем hello-пакет на $ip с таймаутом $this->disc_timeout" . PHP_EOL;
			
			$helloPacket = hex2bin(HELLO_MSG);
			
			socket_sendto($sock, $helloPacket, strlen($helloPacket), 0, $ip, MIIO_PORT);
			
			$buf = '';
		    $count = 0;
			$devinfo = array();
			$devices = array();
			
			while ($ret = @socket_recvfrom($sock, $buf, 4096, 0, $remote_ip, $remote_port)) {
				if ($buf != '') {
					if ($this->debug) echo ($count+1) . "  Получен ответ от IP $remote_ip" . PHP_EOL;
					$this->miPacket->msgParse(bin2hex($buf));
					if ($this->debug) $this->miPacket->printHead();
					$devinfo = $this->miPacket->info;
					$devinfo += ["ip" => $remote_ip];
					$devices[] = json_encode($devinfo);
				}
				$count += 1;
			}
			
			if(!empty($devices)) $this->data = '{"devices":'. json_encode($devices) .'}';
		
			socket_close($sock);
			
			if ($count != 0 || !empty($this->data)) return true;
			 else return false;
		}
	}
	
	/*
		Сокеты. Запись и чтение.
	*/

	public function socketWriteRead($msg) {
	
		if ($this->discover($this->ip)) {

			if ($this->debug) echo PHP_EOL . "Устройство $this->ip доступно" . PHP_EOL;

			if (!($sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP))) {
				$errorcode = socket_last_error();
				$errormsg = socket_strerror($errorcode);
				if ($this->debug) echo "Ошибка создания сокета [$errorcode] $errormsg" . PHP_EOL;
				die("Ошибка создания сокета [$errorcode] $errormsg \n");
			}
			
			if ($this->debug) echo 'Сокет успешно создан' . PHP_EOL;
			
			socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $this->send_timeout, "usec" => 0));
			
			if ($this->token != NULL) {
				if(!$this->miPacket->setToken($this->token)) {
					if ($this->debug) echo 'Неверный формат токена!' . PHP_EOL;
					socket_close($sock);
					die('Неверный формат токена!\n');
				} else {
					if ($this->debug) echo 'Используется токен, указанный вручную, - ' . $this->token . PHP_EOL;
				}
			} else {
				if ($this->debug) echo 'Используется токен, полученный от устройства, - ' . $this->miPacket->getToken() . PHP_EOL;
			}
			
			if ($this->debug) echo "Отправляем пакет на $this->ip с таймаутом $this->send_timeout" . PHP_EOL;
			
			$packet = hex2bin($this->miPacket->msgBuild($msg));

			socket_sendto($sock, $packet, strlen($packet), 0, $this->ip, MIIO_PORT);
						
		    $buf = '';
			if (@socket_recvfrom($sock, $buf, 4096, 0, $remote_ip, $remote_port) == false) {
				$errorcode = socket_last_error();
				$errormsg = socket_strerror($errorcode);
				if ($this->debug) echo "Ошибка чтения из сокета [$errorcode] $errormsg" . PHP_EOL;
				socket_close($sock);
				return false;
			} else if ($buf != '') {
				if ($this->debug) echo "Получен ответ от IP $remote_ip" . PHP_EOL;
				$this->miPacket->msgParse(bin2hex($buf));
				if ($this->debug) $this->miPacket->printPacket();
				$data_dec = $this->miPacket->decryptData($this->miPacket->data);	
				if ($this->debug) echo "Расшифрованные данные: $data_dec" . PHP_EOL;
				$this->data = $data_dec;
				socket_close($sock);
				return true;
			}
		} else {
			if ($this->debug) echo "Устройство по адресу $this->ip не ответило на hello-запрос!" . PHP_EOL;
			return false;
		}
	}
	
	/*
		Отправка сообщения (метод и параметры раздельно) устройству и прием ответа.
	*/
	
	public function msgSendRcv($command, $parameters = NULL) {
	
		$msg = '{"id":' . $this->_id . ',"method":"'. $command . '"}';
			
		if ($parameters != NULL) {
			$msg = '{"id":' . $this->_id . ',"method":"'. $command . '","params":' . $parameters . '}';
		}
			
		if ($this->debug) echo "Команда для отправки - $msg" . PHP_EOL;
			
		return $this->socketWriteRead($msg);

	}
	
	/*
		Отправка сообщения (как есть) устройству и прием ответа.
	*/
	
	public function msgSendRcvRaw($msg) {
	
		if ($this->debug) echo "Команда для отправки - $msg" . PHP_EOL;
		
		return $this->socketWriteRead($msg);
 
	}
	
	/*
		Получить miIO-сведения об устройстве.
	*/
	
	public function getInfo() {
	
		return $this->msgSendRcv('miIO.info', '[]');
	 
	}
	
}
