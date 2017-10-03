<?php
/* 
*	Реализация взаимодействия с устройствами из экосистемы xiaomi по протоколу miIO.
*
*	Copyright (C) 2017 Agaphonov Dmitri aka skysilver [mailto:skysilver.da@gmail.com]
*/

require('miio.class.php');

$ip = '192.168.1.47'; // Адрес устройства.
$token = 'b31c928032e6a4afc898c5c8768axxxx'; // Уникальный токен устройства.
$debug = false; // Для отладки сменить на true.

echo PHP_EOL . '<----- php-miio start ----->' . PHP_EOL;

// Если не указывать токен, то он будет запрошен у устройства.
$dev = new miIO($ip, null, $debug);

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

echo PHP_EOL . '<-----  php-miio end  ----->' . PHP_EOL;
echo PHP_EOL;
