<?php 
	require("includes/head.php");
	list($file_arr,$key_arr)=dir_size(ROOTPATH.'temp/');
	?>
		<div class="span10">
        	<script language="javascript" src="bootstrap/jquery.min.js"></script>
        	<script type="text/javascript" src="bootstrap/highcharts/js/modules/exporting.js"></script>
            <script type="text/javascript" src="bootstrap/highcharts/js/highcharts.js"></script>
        	<h4><?php echo $pageName;
			if(!$kid) msg_show('请选择关键词后再进入此页','keyword_list.php');
			echo "&nbsp;&nbsp;<small>当前热门产品是在指定关键词：<span style='color:red;'><strong>{$key_array['keyword']}</strong></span>下的热门产品</small>";
			?></h4>
            <hr>
            <?php 
				#取最近一周日期数组
				$date_arr=array();
				for($i=7;$i>=0;$i--){
					$d=strtotime("-{$i} days");
					$date_arr[date('Y-m-d',$d)]=0;
				}
				#取起始日期
				$date_keys=array_keys($date_arr);
				$date_start=current($date_keys);
				$date_end=end($date_keys);
				#查询近一周产品数据
				$sql="select * from product_data_aliexpress where date>='{$date_start}' and date<='{$date_end}' and query_id={$kid} order by date asc";
				$data=$kermitDb->db_select($sql)->fetchAll();
				
				#分析数据
				$product_orders=$product_comments=array();
				foreach($data as $k=>$row){
					$pid=$row['pro_id'];$d=$row['date'];
					if(!isset($product_orders[$pid])) $product_orders[$pid]=$date_arr;
					if(!isset($product_comments[$pid])) $product_comments[$pid]=$date_arr;
					$product_orders[$pid][$d]=$row['orders'];
					$product_comments[$pid][$d]=$row['comments'];
				}
				
				require(ROOTPATH."includes/HotRule.php");
				$HotRule=new HotRule($product_orders);
				#根据6月销量返回数据
				$hotArr=$HotRule->getAliexpressFun();
				#根据累计评论数返回排序数据
				$HotRule->changeData($product_comments);
				$hotArrCom=$HotRule->getAliexpressFun();
				
				#将销量数据和评论数据按6:4的权重进行加权
				$data=array();
				foreach($hotArr as $pid=>$value){
					$score=$value*0.6+$hotArrCom[$pid]*0.4;
					$data[$pid]=$score;
				}
				arsort($data);
				$data=array_slice($data,0,20,true);
			?>
             <?php 
			 #数据展示
			if($data){$i=1;
			
				#从数据库中取得产品
				$product_keys=array_keys($data);
				$sql="select * from cate_product_aliexpress where pro_id in(".implode(',',$product_keys).")";
				$products=$kermitDb->db_select($sql)->fetchAll();
				$products_arr=array();
				foreach($products as $k=>$row) $products_arr[$row['pro_id']]=$row;
				$products=NULL;
				
				#取日期字符串
				$date_string="'".implode("','",array_keys($date_arr))."'";
				foreach($data as $k=>$value){
					$row=$products_arr[$k];
					#取产品评论数据走势
					$orders_arr=array_values($product_orders[$k]);
					$comments_arr=array_values($product_comments[$k]);
					$orders_string=implode(',',$orders_arr);
					$comments_string=implode(',',$comments_arr);
					?>
				<div class="row-fluid" >    
				   <table class='table'>
					<tr>
						<td rowspan="3" style="width:120px;">
							<a href='http://www.aliexpress.com/item/aliexpress/<?php echo $row['pro_id'];?>.html' target='_blank'>
							<img class="img-rounded" src="<?php echo $row['pro_image'];?>" />
							</a>
						</td>
						<td>排序</td>
						<td>潜力指数</td>
                        <td>评论数</td>

						<td>订单数</td>
						<td>产品价格</td>
						<td>产品标题</td>
					</tr>
					<tr>
						<td class='sortn'><?php echo $i;?></td>
						<td class='sortn'><?php echo $value;?></td>
						<td><?php echo $row['pro_orders'];?></td>
						<td><?php echo $row['pro_orders'];?></td>
						<td>$ <?php echo $row['pro_price'];?></td>
						<td><?php echo $row['pro_name'];?></td>
					</tr>
                    <tr>
                    	<td colspan="6" style="background-color:#eee;">
                     		<div id="stat<?php echo $i;?>" style="width:100%; height:300px;"></div>
                    	</td>
                    </tr>
				   </table>
				</div>
					<script type="text/javascript">
					$(function() {
							$('#stat<?php echo $i;?>').highcharts({
								chart: {type: 'line'},
								title: {text: ''},
								subtitle: {text: 'Source: Arbion.inc'},
								xAxis: {categories: [<?php echo $date_string;?>]},
								yAxis: {title: {text: '个数'},min:0},
								plotOptions: {line: {dataLabels: {enabled: true},enableMouseTracking: false}},
								series: [
								{name: '订单数走势',data: [<?php echo $orders_string;?>]},
								{name: '评论数走势',data: [<?php echo $comments_string;?>]}
								]
							});
						});
					</script>
                    
                    
				<?php $i++;}
			}else echo '当前无产品数据';?>
		</div>
<?php require("includes/foot.php");?>