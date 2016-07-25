<?php 
/*
 *Note:每天7点准时启动nohup命令
 *Time:2015-1-24
 *Content:
 */

#加载基本配置
error_reporting(E_ALL);
date_default_timezone_set('PRC');
define('THIS_PATH',str_replace('\\','/',dirname(dirname(__FILE__))).'/');
set_time_limit(0);

#启动aliexpress进程：
$rs=shell_exec("nohup /usr/bin/php /home/wwwroot/metadata.arbion.net/autoRun/nohup_aliexpress.php > /home/wwwroot/metadata.arbion.net/autoRun/nohup_aliexpress.txt 2>&1 &");
sleep(10);

#启动amazon进程：
$rs=shell_exec("nohup /usr/bin/php /home/wwwroot/metadata.arbion.net/autoRun/nohup_amazon.php >> /home/wwwroot/metadata.arbion.net/autoRun/nohup_amazon.txt 2>&1 &");
sleep(10);

#启动ebay进程：
$rs=shell_exec("nohup /usr/bin/php /home/wwwroot/metadata.arbion.net/autoRun/nohup_ebay.php >> /home/wwwroot/metadata.arbion.net/autoRun/nohup_ebay.txt 2>&1 &");

echo date('Y-m-d H:i:s')." start:nohup express & amazon & ebay\r\n";

#查找进程
/*$data=shell_exec("ps -ef | grep nohup_aliexpress.php");
$data=str_replace(array("\r","\n"),'',$data);

#如果进程存在，则杀死进程
if(strpos($data,'/home/wwwroot/default/wishnew/autoRun/nohup_aliexpress.php')!==false){
	#进程在执行，杀死进程
	$rs=preg_match('/root\s*(\d*)\s*\d/',$data,$match); 	
	$id=$rs?trim($match[1]):0;
	if($id){
		$data=shell_exec("kill {$id}");
		echo "kill {$id}\r\n\r\n";
		print_r($data);
	}
}*/