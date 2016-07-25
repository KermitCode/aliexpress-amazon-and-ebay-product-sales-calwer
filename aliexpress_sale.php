<?php require("includes/head.php");?>
<?php 
#取总数
$sql=$kid?"where pro_query_id='{$kid}'":'';
$all=$kermitDb->db_select("select count(*) as anum from cate_product_aliexpress {$sql}")->fetch();
$all=$all['anum'];
$pagenum=50;
$pagechar=makePage($all,$pagenum,$page,"aliexpress_sale.php?kid={$kid}");
#取当前数
$start=$pagenum*($page-1);
$data=$kermitDb->db_select("select * from cate_product_aliexpress {$sql} order by pro_orders desc limit {$start},$pagenum")->fetchAll();
?>
		<div class="span10">
        	<h4><?php echo $pageName;
			if(!$kid) echo "&nbsp;&nbsp;<small>当前未指定分析关键词！请从关键词列表找到您要分析的关键词进入此页</small>";
			else echo "&nbsp;&nbsp;<small>当前查看监控关键词：<span style='color:red;'><strong>{$key_array['keyword']}</strong></span></small>";
			?></h4>
            <hr>
            <div class="pagination text-right">
                <ul>
                    <?php echo $pagechar;?>
                </ul>
            </div>
            <?php 
			$i=$start+1;
			if($data){
				foreach($data as $k=>$row){?>
				<div class="row-fluid" >    
				   <table class='table'>
					<tr>
						<td rowspan="2" style="width:120px;">
							<a href='http://www.aliexpress.com/item/aliexpress/<?php echo $row['pro_id'];?>.html' target='_blank'>
							<img class="img-rounded mimg" src="<?php echo $row['pro_image'];?>" />
							</a>
						</td>
						<td>排序</td>
						<td>评论数</td>
                        <td>关键词</td> 
						<td>订单数</td>
						<td>产品价格</td>
						<td>产品标题</td>
					</tr>
					<tr>
						<td class='sortn'><?php echo $i;?></td>
						<td><?php echo $row['pro_comments'];?></td>
                        <td><?php echo $keyword_array[$row['pro_query_id']]['keyword'];?></td>
						<td><?php echo $row['pro_orders'];?></td>
						<td>$ <?php echo $row['pro_price'];?></td>
						<td><?php echo $row['pro_name'];?></td>
					</tr>
				   </table>
				</div>
				<?php $i++;}
			}else echo '当前还没有该关键词的产品数据';?>
            <div class="pagination text-right">
                <ul>
                    <?php echo $pagechar;?>
                </ul>
            </div>
		</div>
<?php require("includes/foot.php");?>