<?php 
/*
 *Note:Wish抓产品数据入库
 *Time:2015-1-22
 */

#基本设置
set_time_limit(600);
define('DEBUG',true);
error_reporting(E_ALL);
date_default_timezone_set('PRC');
$PHP_SELF=$_SERVER['PHP_SELF'];

#参数
function sendget($url){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER ,1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,30);	
	curl_setopt($ch, CURLOPT_TIMEOUT,30);
	curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,1);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
	$result = curl_exec($ch);
	$curl_errno = curl_errno($ch);
	if($curl_errno>0) return false;
	curl_close($ch);
	return $result;
}

#连接数据库取得产品ID
$config=array(
	'hostname'=>'localhost',
	'username'=>'kermit',
	'password'=>'KermitBuyu123457',
	'database'=>'wishdata'
);
define('THIS_PATH',str_replace('\\','/',dirname(dirname(__FILE__))).'/');
require_once(THIS_PATH."includes/kermitDb.php");

$kermitDb=new kermitDb($config['hostname'],$config['username'],$config['password'],$config['database']);
$sql='select id,pid from wish_data where title is NULL or title="" order by id asc limit 10';
$products=$kermitDb->db_select($sql)->fetchAll();

foreach($products as $product){
	#抓取产品
	$data=array();
	if($product){
		$url='https://www.wish.com/c/'.$product['pid'];
		$curldata=sendget($url);
		
		$html=preg_match("/mainContestObj'\] = (.*);\s*pageParams\['relatedContestObjs/s",$curldata,$match);
		$curldata=json_decode($match[1]);
		
		if($curldata){
			#标签
			$tags='';
			foreach($curldata->tags as $k=>$obj){
				$tags[]=$obj->name;
			}
			#运费相关
			
			#匹配结果
			$data=array(
				'title'=>$curldata->name,
				'image'=>$curldata->contest_page_picture,
				'price'=>$curldata->commerce_product_info->variations[0]->price,
				'price_market'=>$curldata->commerce_product_info->variations[0]->retail_price,
				'prop'=>serialize($curldata->commerce_product_info->sizing_chart_data),
				'content'=>$curldata->description,
				'tags'=>implode(',',$tags),
				'send'=>serialize($curldata->commerce_product_info->variations),
				);
			
			$kermitDb->db_update_mutidata('wish_data',$data," id={$product['id']}");
			echo date('Y-m-d H:i:s').':'.$product['id']."\r\n";
		}
		
	}
}