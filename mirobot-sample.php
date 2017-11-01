<?php
/* 
*	Управление пылесосом Xiaomi Mi Robot Vacuum по протоколу miIO.
*
*	Copyright (C) 2017 Agaphonov Dmitri aka skysilver [mailto:skysilver.da@gmail.com]
*/

include_once('./devices/mirobot.class.php');

error_reporting(-1);
ini_set('display_errors', 1);

$ip = '192.168.1.135';
$token = '8456756495a714e376964305a79399860';
$bind_ip = null;
$debug = true;

$mirobot = new miRobotVacuum($ip, $bind_ip, $token, $debug);

$mirobot->enableAutoMsgID();

echo PHP_EOL . date('H:i:s', time());
echo PHP_EOL;

if ($mirobot->getStatus()) {
	
	echo 'Код состояния: ' . $mirobot->status['state'] . PHP_EOL;
	echo 'Состояние: ' . $mirobot->status['state_text'] . PHP_EOL;
	echo 'Заряд батареи, %: ' . $mirobot->status['battery'] . PHP_EOL;
	echo 'Мощность турбины, %: ' . $mirobot->status['fan_power'] . PHP_EOL;
	echo 'Код ошибки: ' . $mirobot->status['error_code'] . PHP_EOL;
	echo 'Описание ошибки: ' . $mirobot->status['error_text'] . PHP_EOL;
	echo 'Площадь уборки, кв. см.: ' . $mirobot->status['clean_area'] . PHP_EOL;
	echo 'Время уборки, сек.: ' . $mirobot->status['clean_time'] . PHP_EOL;
	echo 'Режим "не беспокоить": ' . $mirobot->status['dnd_enabled'] . PHP_EOL;
	echo 'В процессе уборки или нет: ' . $mirobot->status['in_cleaning'] . PHP_EOL;
	echo 'Есть карта или нет: ' . $mirobot->status['map_present'] . PHP_EOL;
	echo 'Версия команд: ' . $mirobot->status['msg_ver'] . PHP_EOL;
	echo 'Счетчик команд: ' . $mirobot->status['msg_seq'] . PHP_EOL;
	
} else echo " Статус не получен. Ошибка: $mirobot->error" . PHP_EOL;

/*
echo $mirobot->getInfo() . PHP_EOL;
	
if ($mirobot->start()) echo ' Начата уборка.' . PHP_EOL;
 else echo " Не удалось начать уборку. Ошибка: $mirobot->error" . PHP_EOL;
	 
if ($mirobot->stop()) echo ' Уборка остановлена.' . PHP_EOL;
 else echo " Не удалось остановить уборку. Ошибка: $mirobot->error" . PHP_EOL;
	
if ($mirobot->pause()) echo ' Уборка приостановлена.' . PHP_EOL;
 else echo " Не удалось приостановить уборку. Ошибка: $mirobot->error" . PHP_EOL;
	
if ($mirobot->cleanSpot()) echo ' Начата уборка cleanSpot.' . PHP_EOL;
 else echo " Не удалось начать уборку cleanSpot. Ошибка: $mirobot->error" . PHP_EOL;
	
if ($mirobot->charge()) echo ' Возвращение на базу.' . PHP_EOL;
 else echo " Не удалось вернуться на базу. Ошибка: $mirobot->error" . PHP_EOL;
 
if ($mirobot->findMe()) echo ' Отправлена команда поиска.' . PHP_EOL;
 else echo " Не удалось отправить команду поиска. Ошибка: $mirobot->error" . PHP_EOL;

*/
