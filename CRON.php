<?php

$FLG_RESTART_NGINX = false;

$RESTARTS_MINIMUM_INTERVAL = 240; 

$REASON = '';

$CONF_FILE = '/etc/nginx/conf.d/zzz-data2-internal-monitor.conf';

$DIR_INTERNAL = '/etc/data2-internal-monitor/';
if(!is_dir($DIR_INTERNAL))
{
	echo $DIR_INTERNAL . " [CREATING]" . PHP_EOL;
	mkdir($DIR_INTERNAL);
}
else
{
	echo $DIR_INTERNAL . " [EXISTS]" . PHP_EOL;
}


$FILE_MONITOR = $DIR_INTERNAL . 'data2-monitor.php';

if(!is_file($FILE_MONITOR))
{
	 $Content = get_data('https://raw.githubusercontent.com/d2scripts/internal-monitor-auto-restart/refs/heads/main/monitor/monitor-data2.php');
	 if( strpos($Content, '#DATA2-INTERNAL-CHECK-START') !== false && strpos($Content, '#DATA2-INTERNAL-CHECK-END') !== false)
	 {
		file_put_contents($FILE_MONITOR, $Content);
	 }	
	 else
	 {
		echo "ERROR GETTING " . $FILE_MONITOR . PHP_EOL . $Content . PHP_EOL;
		exit;
	 }
}
else
{
	echo $FILE_MONITOR . " [EXISTS]" . PHP_EOL;
}

if(!is_file($CONF_FILE)) {
    $Content = get_Data("https://raw.githubusercontent.com/d2scripts/internal-monitor-auto-restart/refs/heads/main/model-nginx__zzz-data2-internal-monitor.conf");
    if( strpos($Content, '#DATA2-INTERNAL-CHECK-START') !== false && strpos($Content, '#DATA2-INTERNAL-CHECK-END') !== false)
    {
       file_put_contents($CONF_FILE, $Content);
       $FLG_RESTART_NGINX = true;
	   $REASON = 'CREATING NGINX FILE';
    }
	else
	 {
		echo "ERROR GETTING " . $CONF_FILE . PHP_EOL;
		exit;
	 }
}
else
{
	echo $CONF_FILE . " [EXISTS]" . PHP_EOL;
}


if($FLG_RESTART_NGINX)
{
   system('systemctl restart nginx');
   $TXT = 'RESTARTING NGINX ' . $REASON;
   system('data2-notify ' . escapeshellarg($TXT));
}
else
{
	echo "NGINX INTACT" . PHP_EOL;
}



$PING = randomstr(128);

echo $PING . PHP_EOL;

$PARAMS = [
	'ping' => $PING,
	't' => time(),
];

$URL = 'http://127.0.0.1/data2-monitor.php?' . http_build_query($PARAMS);

$RETORNO = get_data($URL, [], true, ['Host: data2-internal-monitor'], 3);

print_r($RETORNO);

$JSON = json_decode($RETORNO, true);

if($JSON && $JSON['load'])
{
	if(md5($PING) === $JSON['pong'])
	{
		if(array_sum($JSON['services']) == count($JSON['services']))
		{
			echo "PONG OK " . $JSON['pong'] . PHP_EOL;
			exit;
		}		
	}
	else
	{
		#echo "PONG FAIL " . $JSON['pong'] . PHP_EOL;
		#$FLG_RESTART_NGINX = true;
		#$REASON = 'PONG FAIL';
	}
}	

__RELOAD_SERVICES();







function randomstr($qtde = 10)
{
		return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',32)),0,$qtde);
}

function get_data($url, $POST = false, $FLG_FOLLOW_REDIRECT = false, $ADDHEADERS = [], $timeout = 10)
{
	$ch = curl_init();
	#$timeout = 10;
	$header = $ADDHEADERS ?? [];

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36');

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

	if($FLG_FOLLOW_REDIRECT)
	{
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	}
  
	if($POST && is_array($POST))
	{
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS, http_build_query($POST));
	}

	#curl_setopt($ch, CURLOPT_COOKIEJAR, APP_PATH . 'cookie.cookie');
    #curl_setopt($ch, CURLOPT_COOKIEFILE, APP_PATH . 'cookie.cookie');

	$data = curl_exec($ch);
	curl_close($ch);

	return $data;
}

function __RELOAD_SERVICES()
{
	global $RESTARTS_MINIMUM_INTERVAL;

	$FILE_CONTROLE = '/dev/shm/last-restart-monitor';
	
	if(is_file($FILE_CONTROLE))
	{
		$TIME = filemtime($FILE_CONTROLE);
		if( (time() - $TIME) < $RESTARTS_MINIMUM_INTERVAL)
		{
			echo "LAST RESTART WITHIN ".$RESTARTS_MINIMUM_INTERVAL." SECONDS" . PHP_EOL;
			return;
		}
	}

	system('data2-notify "RESTARTING SERVICES [START]"');
	$RET = shell_exec('systemctl restart {nginx,php-fpm,mysql,mysqld,mariadb} 2>&1');
	system('data2-notify "RESTARTING SERVICES [END]"');
	system('data2-notify ' . escapeshellarg($RET));
	touch($FILE_CONTROLE);
}
