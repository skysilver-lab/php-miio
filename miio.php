<?php
/*
	+ генерация ключа и вектора инициализации из токена
	+ расшифровка
	+ шифрование
	+ разбор udp-пакета
	+ формирование udp-пакета
	+ прием udp-пакетов из сокета
	+ отправка udp-пакетов в сокет
*/

$device_ip = '192.168.1.47';						// ip-адрес mi-устройства

// команды управления mi-устройством (примеры)
$cmd_miioinfo = '{"id":1,"method":"miIO.info","params":[]}';
$cmd_off = 		'{"id":1,"method":"set_power","params":["off"]}';
$cmd_on =  		'{"id":1,"method":"set_power","params":["on"]}';
$cmd_status = '{"id":1,"method":"get_prop","params":["power","color_mode","bright","ct","rgb","flowing","pdo_status","hue","sat","save_state","flow_params"]}';
 
// hello-пакет
$hello_str = '21310020ffffffffffffffffffffffffffffffffffffffffffffffffffffffff';
	
$con = pfsockopen('udp://'.$device_ip, 54321, $errno, $errstr, 10);

if ($con) {
	echo PHP_EOL;
	echo 'Отправлен hello-пакет размером ' . fwrite($con, hex2bin($hello_str)) . ' байт' . PHP_EOL;
	$cart = fread($con, 1024);
	echo 'Получен ответ: ' . bin2hex($cart) . PHP_EOL;
	
	$res_str = bin2hex($cart);
		
	$stamp = substr($res_str, 24, 8);
	$stamp_sec = hexdec($stamp);
	$devicetype = substr($res_str, 16, 4);
	$serial = substr($res_str, 20, 4);
	$token = substr($res_str, 32, 32);
	
	echo 'magic: ' . substr($res_str, 0, 4) . PHP_EOL;
	echo 'length: ' . substr($res_str, 4, 4) . ' --> ' . hexdec(substr($res_str, 4, 4)) . ' байт' . PHP_EOL;
	echo 'unknown1: ' . substr($res_str, 8, 8) . PHP_EOL;
	echo 'devicetype: ' . $devicetype . PHP_EOL;
	echo 'serial: ' . $serial . PHP_EOL;
	echo 'stamp: ' . $stamp . ' --> ' . $stamp_sec . ' секунд'. PHP_EOL;
	echo 'token: ' . $token . PHP_EOL;

	$key_bin = hash('md5', hex2bin($token), true);	// вычисляем ключ шифрования
	$key_str = bin2hex($key_bin);					// ключ в hex-формате

	$iv_bin = hash('md5', hex2bin($key_str.$token), true);	// вычисляем вектор	инициализации
	$iv_str = bin2hex($iv_bin);								// вектор инициализации в hex-формате

	echo PHP_EOL;
	echo 'key = ' . $key_str . PHP_EOL;
	echo 'iv = ' . $iv_str . PHP_EOL;
	echo PHP_EOL;
	
	$cmd = $cmd_miioinfo; //$cmd_miioinfo $cmd_off $cmd_on $cmd_status
	
	echo 'Шифруем команду ' . $cmd . PHP_EOL;
	$data_enc = bin2hex(openssl_encrypt($cmd, 'AES-128-CBC', $key_bin, OPENSSL_RAW_DATA, $iv_bin));
	echo 'Длина шифрованной команды: ' . strlen($data_enc) . PHP_EOL;
	
	
	$packet_length = sprintf('%04x', (int)strlen($data_enc)/2 + 32);
	echo 'Длина пакета: ' . $packet_length . PHP_EOL;
	
	$packet = '2131'.$packet_length.'00000000'.$devicetype.$serial.$stamp.$token.$data_enc;
		
	$md5packet = hash('md5', hex2bin($packet), true);
	$md5packet = bin2hex($md5packet);
	echo 'CRC пакета: ' . $md5packet . PHP_EOL;
	echo PHP_EOL;
	
	$packet = '2131'.$packet_length.'00000000'.$devicetype.$serial.$stamp.$md5packet.$data_enc;
	
	echo 'Отправлен пакет размером ' . fwrite($con, hex2bin($packet)) . ' байт' . PHP_EOL;
	$cart = fread($con, 1024);
	$res_str = bin2hex($cart);
	echo 'Получен ответ: ' . $res_str . PHP_EOL;
		
	$data_length = strlen($res_str) - 64;
	$stamp = substr($res_str, 24, 8);
	$stamp_sec = hexdec($stamp);
	$data_enc = substr($res_str, 64, $data_length);
		
	echo 'magic: ' . substr($res_str, 0, 4) . PHP_EOL;
	echo 'length: ' . substr($res_str, 4, 4) . ' --> ' . hexdec(substr($res_str, 4, 4)) . ' байт' . PHP_EOL;
	echo 'unknown1: ' . substr($res_str, 8, 8) . PHP_EOL;
	echo 'devicetype: ' . substr($res_str, 16, 4) . PHP_EOL;
	echo 'serial: ' . substr($res_str, 20, 4) . PHP_EOL;
	echo 'stamp: ' . $stamp . ' --> ' . $stamp_sec . ' секунд'. PHP_EOL;
	echo 'checksum: ' . substr($res_str, 32, 32) . PHP_EOL;
	
	echo 'Длина зашифрованных данных: ' . $data_length . PHP_EOL;
	echo 'Данные для расшифровки: ' . $data_enc . PHP_EOL;
	
	$data_dec = openssl_decrypt(hex2bin($data_enc), 'AES-128-CBC', $key_bin, OPENSSL_RAW_DATA, $iv_bin);
		
	echo 'Расшифрованные данные: ' . $data_dec . PHP_EOL;
	echo PHP_EOL;
	
	fclose($con);
}
