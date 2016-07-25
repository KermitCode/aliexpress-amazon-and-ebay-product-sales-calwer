<?php
#Kermit
#2015-1-28
#Note:产品爆品算法程序


/*产品爆品规则
 *入数据：[960828334] => Array
        (
            [2015-01-21] => 0
            [2015-01-22] => 0
            [2015-01-23] => 0
            [2015-01-24] => 0
            [2015-01-25] => 0
            [2015-01-26] => 0
            [2015-01-27] => 1803
            [2015-01-28] => 1803
        )
 *一、已处于热卖产品：
 *规则A：取每日平均值，平均值越大，越是爆品，占比60%;
 *规则B：取每天比前一天的增加值，此值越大，绝对增幅越大。未来潜力依然大; 占比40%;

 *二、有潜力的产品
 *规则A：取产品日平均值，计算每日与平均值的绝对相差的总和，总和越大，大小越分散，则越是爆口，占比60%；
 *规则B：取产品平均值，以最大值为100分计算，越大，产品的权重越大，占比40%
 */

class HotRule{
	
	public $ori_array=array();
	public $avg_array=array();	#产品平均值
	public $avg_max=0;			#产品最大平均值
	public $add_array=array();	#日增加值之和
	public $add_max=0;			#日增加最大值
	public $less_array=array();	#每个产品每天与平均值绝对差值之和
	public $less_max=0;
	
	#构建函数,初始化原始数据
	public function __construct($data=array()){
		$this->ori_array=$data;
	}
	
	#变更产品数据
	public function changeData($data){
		$this->ori_array=$data;	
	}

	#计算产品平均值
	public function getAvg(){
		$temp_avg=array();$avg_max=0;
		#计算最大平均值以及平均值
		foreach($this->ori_array as $pid=>$data){
			$temp_avg[$pid]=intval(array_sum($data)/7);
			if($temp_avg[$pid]>$avg_max) $avg_max=$temp_avg[$pid];
		}
		$this->avg_array=$temp_avg;
		$this->avg_max=$avg_max;
		return $this->avg_array;
	}
	
	#计算产品的日绝对增加数量之和
	public function getAdd(){
		$temp_add=array();$add_max=0;
		#计算最大平均值以及平均值
		foreach($this->ori_array as $pid=>$data){
			$i=0;$last=0;$add=0;
			foreach($data as $date=>$value){
				if(!$i){
					$last=$value;
				}else{
					$add+=$value-$last;
					$last=$value;
				}
				$i++;
			}
			$temp_add[$pid]=$add;
			if($add>$add_max) $add_max=$add;
		}
		$this->add_array=$temp_add;
		$this->add_max=$add_max;
		return $this->add_array;		
	}
	
	#计算产品与平均值的公差，此值越大，数量越分散，表示越是爆品
	public function getLess(){
		if(!$this->avg_array) $this->getAvg();
		$less_max=0;
		foreach($this->ori_array as $pid=>$data){
			$less=0;
			#取此产品的平均值
			$avg=$this->avg_array[$pid];
			#取各值与之差值
			foreach($data as $date=>$value){
				$less+=abs($avg-$value);
			}
			$this->less_array[$pid]=$less;
			if($less_max<$less) $less_max=$less;
		}
		$this->less_max=$less_max;
		return $this->less_array;	
	}
	
	#按照aliexpress_6月累计销量数据--返回已处于热卖产品
	public function getAliexpressHot(){
		#规则：1，累计销量数据平均值越大   					占比：90%
		#规则：2，日差值之和越小，则数据越靠近，越是成熟热卖品	占比：10%
		#累计销量数据占绝对比重
		$this->getAvg();
		$this->getLess();
		$hot_arr=array();
		#计算各产品得分
		foreach($this->avg_array as $pid=>$avg_value){
			$score_avg=($avg_value/$this->avg_max)*100;
			$less=$this->less_array[$pid];
			$score_less=100-($less/$this->less_max)*100;
			$hot_arr[$pid]=intval($score_avg*0.9+$score_less*0.1);			
		}
		arsort($hot_arr);
		return $hot_arr;
	}
	
	#按照aliexpress_6月累计销量数据--返回已处于热卖产品
	public function getAliexpressFun(){
		#规则：1，累计销量数据平均值越大   					占比：20%
		#规则：2，日差值之和越大，则数据越分散，销售变动越大		占比：80%
		#累计销量数据占绝对比重
		$this->getAvg();
		$this->getLess();
		$hot_arr=array();
		#计算各产品得分
		foreach($this->avg_array as $pid=>$avg_value){
			$score_avg=($avg_value/$this->avg_max)*100;
			$less=$this->less_array[$pid];
			$score_less=$less/$this->less_max*100;
			$hot_arr[$pid]=intval($score_avg*0.2+$score_less*0.8);			
		}
		arsort($hot_arr);
		return $hot_arr;
	}
	
	
}