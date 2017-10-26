<?php
/* 
*	Управление wifi-лампами Philips Light Bulb по протоколу miIO.
*
*	Copyright (C) 2017 Agaphonov Dmitri aka skysilver [mailto:skysilver.da@gmail.com]
*/

require('philipsbulb.class.php');

error_reporting(-1);
ini_set('display_errors', 1);

$ip = '192.168.1.46';
$token = '40011346b40fdb540a5b6eeae1167bf5';
$bind_ip = null;
$debug = false;

$bulb = new philipsBulb($ip, $bind_ip, $token, $debug);

echo PHP_EOL . date('H:m:s', time());
if($bulb->getStatus()) echo ' Статус получен.' . PHP_EOL;
echo 'Питание: ' . $bulb->status['power'] . PHP_EOL;
echo 'Яркость: ' . $bulb->status['bright'] . PHP_EOL;
echo 'Цветовая температура: ' . $bulb->status['cct'] . PHP_EOL;
echo 'Сцена: ' . $bulb->status['snm'] . PHP_EOL;
echo 'Таймер выключения: ' . $bulb->status['dv'] . PHP_EOL;

sleep(2);

echo PHP_EOL . date('H:m:s', time()) . PHP_EOL;
echo $bulb->getInfo() . PHP_EOL;

sleep(2);

echo PHP_EOL . date('H:m:s', time());
if($bulb->powerOn()) echo ' Лампа включена.' . PHP_EOL;
 else echo "Лампа не включена. Ошибка: $bulb->error" . PHP_EOL;

sleep(2);

echo PHP_EOL . date('H:m:s', time());
if($bulb->powerOff()) echo ' Лампа выключена.' . PHP_EOL;
 else echo "Лампа не выключена. Ошибка: $bulb->error" . PHP_EOL;

sleep(2);

for ($i = 1; $i < 5; $i++) {
	echo PHP_EOL . date('H:m:s', time());
	if($bulb->setScene($i)) echo " Включена сцена $i." . PHP_EOL;
	sleep(2);
}

echo PHP_EOL . date('H:m:s', time());
if($bulb->powerOff()) echo ' Лампа выключена.' . PHP_EOL;
 else echo "Лампа не выключена. Ошибка: $bulb->error" . PHP_EOL;
