<?php 
/*
 *Note:每天自动抓取ebay数据
 *Time:2015-1-28
 *Content:凌晨1点开始执行此NOHUP程序，扫描ebay产品数据，取当前数据
 */

#加载基本配置
define('AUTO',1);
define('THIS_PATH',str_replace('\\','/',dirname(dirname(__FILE__))).'/');
require_once(THIS_PATH."includes/baseLoad.php");

#取产品数据
$sql="select pro_id,pro_name,pro_query_id from cate_product_ebay order by id asc";
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
		$url="http://www.ebay.com/itm/ebay/{$product['pro_id']}?hash=item";
		#提取产品评论数和6月销量数
		$data=sendgetFollow($url);
		$query_id=$product['pro_query_id'];
		
		#取浏览量数
		$rs=preg_match('/alt="feedback score: (\d*)"/',$data,$match);
		$orders=$rs?$match[1]:0;
		$orders=trim($orders);
		$orders=intval($orders);

		#取评论总数
		#$rs=preg_match('/>Feedback \((.*)\)<\/a>\s*<\/li>/s',$data,$match);
		#$comments=$rs?$match[1]:0;
		$comments=0;
		
		#if($k==2) {echo $url.'<br><br>';echo $data;exit;}
		/*echo $pid.'<br>';
		echo $orders.'----'.$comments.'<br>';*/
			
		$sql="insert into product_data_ebay(pro_id,date,comments,orders,query_id) values('{$pid}','{$day}','{$comments}','{$orders}','{$query_id}') on duplicate key update comments='{$comments}',orders='{$orders}'";
		$kermitDb->db_upandde($sql);
		$i++;
		#休息一秒
		sleep(1);	
	}
	
	echo date('Y-m-d')." {$i}\r\n";
	break;
	exit;
}
exit;