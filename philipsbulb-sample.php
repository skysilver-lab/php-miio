<?php
/* 
*	Управление wifi-лампами Philips Light Bulb по протоколу miIO.
*
*	Copyright (C) 2017 Agaphonov Dmitri aka skysilver [mailto:skysilver.da@gmail.com]
*/

include_once('./devices/philipsbulb.class.php');

error_reporting(-1);
ini_set('display_errors', 1);

$ip = '192.168.1.46';
$token = '40011346b40fdb540a5b6eeae1167bf5';
$bind_ip = null;
$debug = false;

$cmd_id = 100;

$bulb = new philipsBulb($ip, $bind_ip, $token, $debug);

// По умолчанию ID всех команд равен 1.
// Если нужно авто-формирование уникальных ID для команд из файла id.json, то вызываем функцию enableAutoMsgID().
// Либо можно формировать ID динамически и передавать их значения в функцию явно.
// $bulb->enableAutoMsgID();

echo PHP_EOL . date('H:i:s', time());

if($bulb->getStatus($cmd_id)) {
	echo ' Статус получен.' . PHP_EOL;
	echo 'Питание: ' . $bulb->status['power'] . PHP_EOL;
	echo 'Яркость: ' . $bulb->status['bright'] . PHP_EOL;
	echo 'Цветовая температура: ' . $bulb->status['cct'] . PHP_EOL;
	echo 'Сцена: ' . $bulb->status['snm'] . PHP_EOL;
	echo 'Таймер выключения: ' . $bulb->status['dv'] . PHP_EOL;
	$cmd_id += 1;
	sleep(2);
	
	echo PHP_EOL . date('H:i:s', time()) . PHP_EOL;
	echo $bulb->getInfo($cmd_id) . PHP_EOL;
	$cmd_id += 1;
	sleep(2);

	echo PHP_EOL . date('H:i:s', time());
	if($bulb->powerOn($cmd_id)) echo ' Лампа включена.' . PHP_EOL;
	 else echo " Лампа не включена. Ошибка: $bulb->error" . PHP_EOL;
	$cmd_id += 1;
	sleep(2);

	echo PHP_EOL . date('H:i:s', time());
	if($bulb->powerOff($cmd_id)) echo ' Лампа выключена.' . PHP_EOL;
	 else echo " Лампа не выключена. Ошибка: $bulb->error" . PHP_EOL;
	$cmd_id += 1;
	sleep(2);

	for ($i = 1; $i < 5; $i++) {
		echo PHP_EOL . date('H:i:s', time());
		if($bulb->setScene($i, $cmd_id)) echo " Включена сцена $i." . PHP_EOL;
		 else echo " Сцена $i не выключена. Ошибка: $bulb->error" . PHP_EOL;
		$cmd_id += 1;
		sleep(2);
	}

	echo PHP_EOL . date('H:i:s', time());
	if($bulb->powerOff($cmd_id)) echo ' Лампа выключена.' . PHP_EOL;
	 else echo " Лампа не выключена. Ошибка: $bulb->error" . PHP_EOL;

} else echo " Статус лампы не получен. Ошибка: $bulb->error" . PHP_EOL;
