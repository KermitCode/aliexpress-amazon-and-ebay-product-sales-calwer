<?php 
/*
 *Note:关键词及抓取程序
 *Time:2015-1-27
 *详细：每2分钟运行一次
 * * /2 * * * * /usr/local/php/bin/php /home/wwwroot/default/wishnew/autoRun/curl_start.php >> /home/wwwroot/default/wishnew/autoRun/curl_start.txt
 */

#加载基本配置
$dir=dirname(dirname(__FILE__));
define('AUTO',1);
require_once($dir."/includes/baseLoad.php");
list($file_name,$key_arr)=dir_size(ROOTPATH.'temp/');

#无需要执行的关键词时，停止运行。
if(!$file_name) exit;
set_time_limit(1000);

#处理一个关键词、抓取对应平台
$file_name=$file_name[0];
$this_name=str_replace('.txt','',$file_name);
list($kid,$plat)=explode('_',$this_name);

#test测试时使用以下数据
#$data=$kermitDb->db_select("select * from cate_keyword_new where id=29")->fetch();
#$plat=3;


#取关键词数据
$data=$kermitDb->db_select("select * from cate_keyword_new where id={$kid}")->fetch();
if(!$data || !$data['page']) exit;#关键词已删除
foreach($data as $k=>$v) $$k=$v;
$pro_query_id=$id;

if($plat==1){
#抓取aliexpress数据------------------------------------------------------------------------------------------------------------------------#
	$plat_name='aliexpress';
	$urls=array();
	$keyword=str_replace(' ','+',$keyword);
	#拼接要抓取的基本URL
	$url='http://www.aliexpress.com/wholesale?catId='.$cate_id.'&g=y&SortType=total_tranpro_desc&SearchText='.$keyword;
	for($i=1;$i<=$page;$i++){
		$urls[]=$url."&page={$i}";
	}

	#开始并发批量抓取
	$multi = curl_multi_init();
	$channels=array();
	foreach($urls as $url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:25.0) Gecko/20100101 Firefox/25.0');
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER ,1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,50);	
		curl_setopt($ch, CURLOPT_TIMEOUT,50);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,1);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.aliexpress.com/');
		curl_multi_add_handle($multi,$ch);
		$channels[$url] = $ch;
	}
	
	#执行
	$active = null;
	do {$mrc = curl_multi_exec($multi,$active);
	}while ($active>0);
		while($active && $mrc == CURLM_OK) {
			  if (curl_multi_select($multi) == -1) {
				continue;
			  }
			  do{
				$mrc = curl_multi_exec($multi, $active);
			  } while ($active>0); 
	}
	
	foreach ($channels as $i=>$channel) {
	  $ret=curl_multi_getcontent($channel);
	  curl_multi_remove_handle($multi,$channel);
	  $results[$i]=$ret;
	}
	curl_multi_close($multi);$i=1;
	
	#按页提取产品
	$j=1;
	foreach($results as $url=>$data){
		#取当前页数
		$rs=preg_match('/\&page=(.*)/',$url,$match);
		$pro_query_page=$rs?$match[1]:'';
		$rs=preg_match_all('/<div class="pic">(.*?)<input class="atc-product-id/s',$data,$products);
		if($rs){
			foreach($products[1] as $k=>$prohtml){
				#产品ID值	
				$rs=preg_match('/data-id\d*="(\d*)"/',$prohtml,$match);
				$pro_id=$rs?$match[1]:0;
				
				#产品标题
				$rs=preg_match('/class="history-item.*title="(.*)" >/',$prohtml,$match);
				$pro_name=$rs?$match[1]:'';
				$pro_name=addslashes($pro_name);
				
				#产品图片
				$rs=preg_match('/class="picCore.*src="(.*)"  alt/',$prohtml,$match);
				$pro_image=$rs?$match[1]:'';
			
				#产品价格
				$rs=preg_match('/itemprop="price">US \$(.*)<\/span>/',$prohtml,$match);
				$pro_price=$rs?$match[1]:'';
				
				#评论数量
				$rs=preg_match('/title="Feedback\((.*)\)"/',$prohtml,$match);
				$pro_comments=$rs?$match[1]:0;
				
				#所有订单数量
				$rs=preg_match('/<em title="Total Orders"> Orders \((.*)\)/',$prohtml,$match);
				$pro_orders=$rs?$match[1]:0;
				if(!$pro_id) continue;
				$pro_time=time();
				
				#产品入库保存
				$sql = "INSERT INTO cate_product_aliexpress(pro_id,pro_query_id,pro_query_page,pro_name,pro_price,pro_comments,pro_orders,pro_time,pro_image) VALUES('{$pro_id}','{$pro_query_id}','{$pro_query_page}','{$pro_name}','{$pro_price}','{$pro_comments}','{$pro_orders}','{$pro_time}','{$pro_image}') ON DUPLICATE KEY UPDATE pro_price='{$pro_price}',pro_comments='{$pro_comments}',pro_orders='{$pro_orders}'";
				$kermitDb->db_upandde($sql);
			$j++;}//endfor
		}//endif
	}//endfor
	unlink(ROOTPATH.'temp/'.$file_name);
	
}elseif($plat==2){
	#抓取ebay数据------------------------------------------------------------------------------------------------------------------------#	
	$plat_name='ebay';
	$urls=array();
	$keyword=str_replace(' ','+',$keyword);
	#拼接要抓取的基本URL
	$url='http://www.ebay.com/sch/i.html?_from=R40&_sacat=0&_dmd=2&LH_Auction=1&_skc=48&rt=nc&_nkw='.$keyword;
	for($i=1;$i<=$page;$i++){
		$urls[]=$url."&_pgn={$i}";
	}
	
	#开始并发批量抓取
	$multi = curl_multi_init();
	$channels=array();
	foreach($urls as $url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:25.0) Gecko/20100101 Firefox/25.0');
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER ,1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,0);	
		curl_setopt($ch, CURLOPT_TIMEOUT,0);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,1);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		#curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.ebay.com/');
		curl_multi_add_handle($multi,$ch);
		$channels[$url] = $ch;
	}
	
	#执行
	$active = null;
	do {$mrc = curl_multi_exec($multi,$active);
	}while ($active>0);
		while($active && $mrc == CURLM_OK) {
			  if (curl_multi_select($multi) == -1) {
				continue;
			  }
			  do{
				$mrc = curl_multi_exec($multi, $active);
			  } while ($active>0); 
	}
	
	foreach ($channels as $i=>$channel) {
	  $ret=curl_multi_getcontent($channel);
	  curl_multi_remove_handle($multi,$channel);
	  $results[$i]=$ret;
	}
	curl_multi_close($multi);$i=1;
	
	$j=1;
	#按页提取产品
	foreach($results as $url=>$data){
		#取当前页数
		$rs=preg_match('/\&_pgn=(.*)/',$url,$match);
		$pro_query_page=$rs?$match[1]:'';
		$rs=preg_match_all('/<div class="full-width mimg itmcd itmcdV2" >(.*?)<div class="meta">/s',$data,$products);
		if($rs){
			foreach($products[1] as $k=>$prohtml){
				#产品ID值	
				$rs=preg_match('/<div class="img full-width" count="\d*" iid="(\d*)">/',$prohtml,$match);
				$pro_id=$rs?$match[1]:0;

				#产品标题
				$rs=preg_match('/class="vip" title=".*">(.*)<\/a>/',$prohtml,$match);
				$pro_name=$rs?$match[1]:'';
				$pro_name=addslashes($pro_name);
			
				#产品图片
				$rs=preg_match('/<div class="imgWr">.*src="(.*)" class="img"/s',$prohtml,$match);
				$pro_image=$rs?$match[1]:'';
			
				#产品价格
				$rs=preg_match('/<span class="g-b bold amt">(.*)<\/span>/',$prohtml,$match);
				$pro_price=trim($rs?strip_tags($match[1]):'');
				
				#评论数量
				#$rs=preg_match('/title="Feedback\((.*)\)"/',$prohtml,$match);
				$pro_comments=0;#$rs?$match[1]:0;
				
				#所有订单数量
				$rs=preg_match('/<span class="lbl gvformat">(.*)bids<\/span>/',$prohtml,$match);
				$pro_orders=$rs?trim($match[1]):0;
				
				if(!$pro_id) continue;
				$pro_time=time();
				
				#产品入库保存
				$sql = "INSERT INTO cate_product_ebay(pro_id,pro_query_id,pro_query_page,pro_name,pro_price,pro_comments,pro_orders,pro_time,pro_image) VALUES('{$pro_id}','{$pro_query_id}','{$pro_query_page}','{$pro_name}','{$pro_price}','{$pro_comments}','{$pro_orders}','{$pro_time}','{$pro_image}') ON DUPLICATE KEY UPDATE pro_price='{$pro_price}',pro_comments='{$pro_comments}',pro_orders='{$pro_orders}'";
				$kermitDb->db_upandde($sql);
			$j++;}//endfor
		}//endif
	}//endfor
	unlink(ROOTPATH.'temp/'.$file_name);
}else{
	#抓取amazon数据------------------------------------------------------------------------------------------------------------------------#
	$plat_name='amazon';
	$urls=array();
	$keyword=str_replace(' ','+',$keyword);
	#拼接要抓取的基本URL
	$amazon=str_replace('=','%3D',$amazon);
	$url='http://www.amazon.com/s/ref=nb_sb_noss?url='.$amazon.'&field-keywords='.$keyword;
	for($i=1;$i<=$page;$i++){
		$urls[]=$url."&page={$i}";
	}
	
	#开始并发批量抓取
	$multi = curl_multi_init();
	$channels=array();
	foreach($urls as $url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:25.0) Gecko/20100101 Firefox/25.0');
		curl_setopt($ch, CURLOPT_HEADER,0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER ,1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,0);	
		curl_setopt($ch, CURLOPT_TIMEOUT,0);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,1);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		#curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_REFERER, 'http://www.amazon.com/');
		curl_multi_add_handle($multi,$ch);
		$channels[$url] = $ch;
	}
	
	#执行
	$active = null;
	do {$mrc = curl_multi_exec($multi,$active);
	}while ($active>0);
		while($active && $mrc == CURLM_OK) {
			  if (curl_multi_select($multi) == -1) {
				continue;
			  }
			  do{
				$mrc = curl_multi_exec($multi, $active);
			  } while ($active>0); 
	}
	
	foreach ($channels as $i=>$channel) {
	  $ret=curl_multi_getcontent($channel);
	  curl_multi_remove_handle($multi,$channel);
	  $results[$i]=$ret;
	}
	curl_multi_close($multi);$i=1;

	$j=1;
	#按页提取产品
	foreach($results as $url=>$data){
		#取当前页数
		$rs=preg_match('/\&page=(.*)/',$url,$match);
		$pro_query_page=$rs?$match[1]:'';
		$rs=preg_match_all('/<li id="result_\d*" data-asin="[A-Z0-9]*" class="s-result-item">(.*?)<\/div><\/div><\/li>/s',$data,$products);				  
		#print_r($products[0]);exit;
		
		if($rs){
			foreach($products[0] as $k=>$prohtml){
				
				#产品ID值	
				$rs=preg_match('/<li id="result_\d*" data-asin="([A-Z0-9]*)"/',$prohtml,$match);
				$pro_id=$rs?$match[1]:0;

				#产品标题
				$rs=preg_match('/s-access-title a-text-normal">(.*)<\/h2>/',$prohtml,$match);
				$pro_name=$rs?trim($match[1]):'';
				$pro_name=addslashes(html_entity_decode($pro_name));
#echo $pro_name.'<br>';
				#产品图片
				$rs=preg_match('/<img alt="Product Details" src="(.*)" onload="view/s',$prohtml,$match);
				$pro_image=$rs?$match[1]:'';
			
				#产品价格
				$rs=preg_match('/<span class="a-size-base a-color-price s-price a-text-bold">(.*)<\/span><\/a><span class="a-letter-space">/',$prohtml,$match);
				$pro_price=trim($rs?$match[1]:'');
				if($pro_price==''){
					$rs=preg_match('/<span class="a-size-base a-color-price a-text-bold">(.*)<\/span><span class="a-letter-space"><\/span>new/',$prohtml,$match);
					$pro_price=trim($rs?$match[1]:'');
					}
			
				#评论数量
				#$rs=preg_match('/title="Feedback\((.*)\)"/',$prohtml,$match);
				$pro_comments=0;#$rs?$match[1]:0;
				
				#所有订单数量
				$rs=preg_match('/a-icon-star a-star-([\d-]*)">/',$prohtml,$match);
				$pro_orders=$rs?intval($match[1]):0;
#echo $pro_id.'--<br>';	continue;
				if(!$pro_id) continue;
				$pro_time=time();
				
				#产品入库保存
				$sql = "INSERT INTO cate_product_amazon(pro_id,pro_query_id,pro_query_page,pro_name,pro_price,pro_comments,pro_orders,pro_time,pro_image) VALUES('{$pro_id}','{$pro_query_id}','{$pro_query_page}','{$pro_name}','{$pro_price}','{$pro_comments}','{$pro_orders}','{$pro_time}','{$pro_image}') ON DUPLICATE KEY UPDATE pro_price='{$pro_price}',pro_comments='{$pro_comments}',pro_orders='{$pro_orders}'";
				$kermitDb->db_upandde($sql);
			$j++;}//endfor
		}//endif
	}//endfor

	unlink(ROOTPATH.'temp/'.$file_name);
}
$j--;
echo date('Y-m-d H:i:s').$plat_name."-{$j}\r\n";