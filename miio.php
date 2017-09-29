<?php
/* 
*	Реализация взаимодействия с устройствами из экосистемы xiaomi по протоколу miIO.
*
*	Copyright (C) 2017 Agaphonov Dmitri aka skysilver [mailto:skysilver.da@gmail.com]
*/

require('mipacket.class.php');

$device_ip = '192.168.1.47';

const MIIO_PORT = '54321';

// команды управления mi-устройством (примеры)
$cmd_miioinfo = '{"id":1,"method":"miIO.info","params":[]}';
$cmd_off = '{"id":1,"method":"set_power","params":["off"]}';
$cmd_on =  '{"id":1,"method":"set_power","params":["on"]}';
$cmd_status = '{"id":1,"method":"get_prop","params":["power","color_mode","bright","ct","rgb","flowing","pdo_status","hue","sat","save_state","flow_params"]}';
 

$con = pfsockopen('udp://'.$device_ip, MIIO_PORT, $errno, $errstr, 10);

if ($con) {
	
	$miPacket = new miPacket();
	
	echo PHP_EOL;
	echo 'Отправлен hello-пакет размером ' . fwrite($con, hex2bin($miPacket->getHello())) . ' байт' . PHP_EOL;
	$cart = fread($con, 1024);
	echo 'Получен ответ: ' . PHP_EOL;
	
	$miPacket->setRaw(bin2hex($cart)); 
	$miPacket->printPacket();
	
	echo 'token: ' . $miPacket->getToken() . PHP_EOL;
	
	$cmd = $cmd_miioinfo; //$cmd_miioinfo $cmd_off $cmd_on $cmd_status
	
	echo 'Отправляем команду ' . $cmd . PHP_EOL;
	echo 'Отправлен пакет размером ' . fwrite($con, hex2bin($miPacket->getRaw($cmd))) . ' байт' . PHP_EOL;
	$cart = fread($con, 1024);
	echo 'Получен ответ: ' . PHP_EOL;
		
	$miPacket->setRaw(bin2hex($cart));
	$miPacket->printPacket();

	$data_dec = $miPacket->decryptData($miPacket->data);	
	
	echo 'Расшифрованные данные: ' . $data_dec . PHP_EOL;
	echo PHP_EOL;
	
	fclose($con);
}
