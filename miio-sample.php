<?php
/* 
*	Реализация взаимодействия с wifi-устройствами из экосистемы xiaomi по протоколу miIO.
*
*	Пример работы с функциями класса miIO.
*
*	Copyright (C) 2017 Agaphonov Dmitri aka skysilver [mailto:skysilver.da@gmail.com]
*/

require('miio.class.php');

error_reporting(-1);
ini_set('display_errors', 1);

$ip = '192.168.1.47';
$bind_ip = '192.168.1.36';
$token = 'b31c928032e6a4afc898c5c8768a518f';
$debug = false;

echo PHP_EOL . '<----- php-miio start ----->' . PHP_EOL . PHP_EOL;

$dev = new miIO(null, $bind_ip, null, $debug);

if ($dev->discover() == true) {
	echo 'Поиск выполнен.' . PHP_EOL;
	$devices = json_decode($dev->data);
	$count = count($devices->devices);
	echo "Найдено $count устройств." . PHP_EOL;
	foreach($devices->devices as $dev) {
		$devprop = json_decode($dev);
		echo 	' IP ' . 		$devprop->ip . 
				' DevType ' . 	$devprop->devicetype . 
				' Serial ' . 	$devprop->serial . 
				' Token ' . 	$devprop->token . PHP_EOL;
		$d = new miIO($devprop->ip, $devprop->token, $debug);
		$d->getInfo();
		echo $d->data . PHP_EOL;
	}
} else {
	echo 'Поиск выполнен. Устройство не найдено.' . PHP_EOL;
}

/*

$dev = new miIO($ip, $bind_ip, null, $debug);

echo PHP_EOL . "Отправляем команду на $dev->ip" . PHP_EOL;

if ($dev->msgSendRcv('miIO.info', '[]') == true) {
	echo "Устройство $dev->ip доступно и ответило:" . PHP_EOL;
	echo $dev->data . PHP_EOL;
} else {
	echo "Устройство $dev->ip не доступно или не отвечает." . PHP_EOL;
}

sleep(2);

if ($dev->msgSendRcvRaw('{"id":1,"method":"toggle","params":[]}') == true) {
	echo "Устройство $dev->ip доступно и ответило:" . PHP_EOL;
	echo $dev->data . PHP_EOL;
} else {
	echo "Устройство $dev->ip не доступно или не отвечает." . PHP_EOL;
}

sleep(2);

if ($dev->getInfo() == true) {
	echo "Устройство $dev->ip доступно и ответило:" . PHP_EOL;
	echo $dev->data . PHP_EOL;
} else {
	echo "Устройство $dev->ip не доступно или не отвечает." . PHP_EOL;
}
*/

echo PHP_EOL . '<-----  php-miio end  ----->' . PHP_EOL;
echo PHP_EOL;
