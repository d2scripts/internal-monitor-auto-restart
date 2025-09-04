<?php

$FLG_RESTART_NGINX = false;

$REASON = '';

$CONF_FILE = '/etc/nginx/conf.d/zzz-data2-internal-monitor.conf';

$DIR_INTERNAL = '/etc/data2-internal-monitor/';
if(!is_dir($DIR_INTERNAL))
{
	mkdir($DIR_INTERNAL);
}
else
{
	echo $DIR_INTERNAL . " [EXISTS]" . PHP_EOL;
}


$FILE_MONITOR = $DIR_INTERNAL . 'data2-monitor.php';

if(!is_file($FILE_MONITOR))
{
	 $Content = 'https://raw.githubusercontent.com/d2scripts/internal-monitor-auto-restart/refs/heads/main/monitor/monitor-data2.php';
	 if( strpos($Content, '#DATA2-INTERNAL-CHECK-START') !== false && strpos($Content, '#DATA2-INTERNAL-CHECK-END') !== false)
	 {
		file_put_contents($FILE_MONITOR, $Content);
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


function get_data($url, $POST = false, $FLG_FOLLOW_REDIRECT = false, $ADDHEADERS = [])
{
	$ch = curl_init();
	$timeout = 10;
	$header = $ADDHEADERS ?? [];

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36');

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

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
