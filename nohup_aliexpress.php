<?php 
/*
 *Note:每天自动抓取aliexpress数据
 *Time:2015-1-28
 *Content:凌晨1点开始执行此NOHUP程序，扫描aliexpress产品数据，取当前数据
 *命令：nohup /usr/bin/php /home/wwwroot/default/wish/autoRun/curl_date_aliexpress.php > /home/wwwroot/default/wish/autoRun/curl_date_aliexpress.txt 2>&1 &
 */

#加载基本配置
define('AUTO',1);
define('THIS_PATH',str_replace('\\','/',dirname(dirname(__FILE__))).'/');
require_once(THIS_PATH."includes/baseLoad.php");

#取产品数据
$sql="select pro_id,pro_name,pro_query_id from cate_product_aliexpress order by id asc";
$products=$kermitDb->db_select($sql)->fetchAll();

#如无产品，则不运行
if(!$products) return;

#aliexpress产品页URL生成
function changeUrl($char){
	$char=str_replace(array('! Brand','\&','!','\'',"'",'.','&amp;','&#39;','/'),'',$char);
	$char=str_replace(array(','),' ',$char);
	$char=trim(preg_replace("/[ ]{1,}/"," ",$char)); 
	$char=str_replace(' ','-',$char);
	return $char;
}

#抓取函数
function sendgetFollow($url,$ssl=false){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER ,1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,30);	
	curl_setopt($ch, CURLOPT_TIMEOUT,30);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	if($ssl){
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,1);
    	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
	}
	$result = curl_exec($ch);
	$curl_errno = curl_errno($ch);
	#print_r(curl_getinfo($ch));exit;
	if($curl_errno>0) return false;
	curl_close($ch);
	return $result;
}

#当日日期
$day=date('Y-m-d');
$i=0;

#执行nohup操作
while(true){
	
	#循环抓取产品
	foreach($products as $k=>$product){
		
		if(!$product){break;}
		$name=changeUrl($product['pro_name']);
		$pid=$product['pro_id'];
		$url="http://www.aliexpress.com/item/{$name}/{$product['pro_id']}.html";
		$query_id=$product['pro_query_id'];
		
		#提取产品评论数和6月销量数
		$data=sendgetFollow($url);
		
		#取6月销售量
		$rs=preg_match('/<span class="orders-count">\s*<b>(.*)<\/b> orders/s',$data,$match);
		$orders=$rs?$match[1]:0;
		
		#取评论总数
		$rs=preg_match('/>Feedback \((.*)\)<\/a>\s*<\/li>/s',$data,$match);
		$comments=$rs?$match[1]:0;
	
		#if($k==2) {echo $url.'<br><br>';echo $data;exit;}
		/*echo $pid.'<br>';
		echo $orders.'----'.$comments.'<br>';*/
			
		$sql="insert into product_data_aliexpress(pro_id,date,comments,orders,query_id) values('{$pid}','{$day}','{$comments}','{$orders}','{$query_id}') on duplicate key update comments='{$comments}',orders='{$orders}'";
		$kermitDb->db_upandde($sql);
		$i++;
		#休息一秒
		sleep(1);	
	}
	
	echo date('Y-m-d')." {$i}\r\n";
	exit;
}