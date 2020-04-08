<?php
/* 
*	ChuangMiPlug via miIO protocol.
*
*	Copyright (C) 2017 Agaphonov Dmitri aka skysilver [mailto:skysilver.da@gmail.com]
*/

include_once('./devices/chuangmiplug.class.php');

error_reporting(-1);
ini_set('display_errors', 1);

$ip = '192.168.2.100';
$token = 'd3f180ade2ea8266db00521637caeffb';
$bind_ip = null;
$debug = false;

$cmd_id = 100;

$plug = new chuangMiPlug($ip, $bind_ip, $token, $debug);

// By default, the ID of all commands is 1.
// If you need to auto-generate unique IDs for commands from the id.json file, then call the enableAutoMsgID () function.
// Or you can generate IDs dynamically and pass their values ​​to the function explicitly.
// $plug->enableAutoMsgID();

echo PHP_EOL . date('H:i:s', time());

if($plug->getStatus($cmd_id)) {
	echo ' Статус получен.' . PHP_EOL;
	echo 'Power: ' . $plug->status['power'] . PHP_EOL;
	$cmd_id += 1;
	sleep(2);
	
	echo PHP_EOL . date('H:i:s', time()) . PHP_EOL;
	echo $plug->getInfo($cmd_id) . PHP_EOL;
	$cmd_id += 1;
	sleep(2);

	echo PHP_EOL . date('H:i:s', time());
	if($plug->powerOn($cmd_id)) echo ' The plug is on.' . PHP_EOL;
	 else echo " The plug is not connected. Error: $plug->error" . PHP_EOL;
	$cmd_id += 1;
	sleep(2);

	echo PHP_EOL . date('H:i:s', time());
	if($plug->powerOff($cmd_id)) echo ' The plug is on.' . PHP_EOL;
	 else echo " The plug is not connected. Error: $plug->error" . PHP_EOL;
	$cmd_id += 1;
	sleep(2);

} else echo " Plug status not received. Error: $plug->error" . PHP_EOL ;
