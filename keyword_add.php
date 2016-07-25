<?php require("includes/head.php");
	
	#取类目列表
	$class=$kermitDb->db_select("select id,cate_name,cate_id from cate_class order by id asc")->fetchAll();

	if(isset($_POST['class_id']) && isset($_POST['add_keyword'])){
		
		#取关键词列表
		$keywords=$kermitDb->db_select("select * from cate_keyword_new order by id asc")->fetchAll();
	
		#处理添加关键词
		$class_id=intval($_POST['class_id']);
		
		#如果添加小类则取小类的ID
		$class_id_small=intval($_POST['class_id_small']);
		if($class_id_small) $class_id=$class_id_small;
		
		$add_keyword=$_POST['add_keyword'];
		$amazon=$_POST['amazon'];
		$keyword_page=intval($_POST['keyword_page']);
		!$keyword_page && $keyword_page=3;
		if(!$class_id || $add_keyword=='') msg_show('类目和关键词都不能为空!');
		foreach($keywords as $k=>$row){
		 	if($row['keyword']==$add_keyword && $row['cate_id']==$class_id) msg_show('该类目下关键词已存在，请不要重复添加!');
		}
		
		#将关键词入库
		$sql="insert into cate_keyword_new(cate_id,keyword,page,amazon) values({$class_id},'{$add_keyword}',{$keyword_page},'{$amazon}')";
		$keywordId=$kermitDb->db_insertget($sql);
		
		#标记要抓取数据
		for($i=1;$i<=3;$i++){
			#$file1=ROOTPATH."temp/{$keywordId}_1.txt";#aliexpress
			#$file1=ROOTPATH."temp/{$keywordId}_2.txt";#ebay
			#$file1=ROOTPATH."temp/{$keywordId}_3.txt";#amazon
			$file1=ROOTPATH."temp/{$keywordId}_{$i}.txt";
			$fp=fopen($file1,'wb');
			fwrite($fp,$keyword_page.'-=-'.$add_keyword);
			fclose($fp);
		}
		
		#跳至抓取结果页
		msg_show('添加成功，请稍候通过关键词列表-分析链接-查看数据!','keyword_do.php');
	}
	
	#取小类ID
	$smidArr=array();
	$smids=$kermitDb->db_select("select * from cate_aliexpress_small order by id asc")->fetchAll();
	foreach($smids as $k=>$row){
		$smidArr[$row['parent_id']][$row['smallid']]=$row['smallname'];
	}

	#echo '<pre>';print_r($smidArr);echo '</pre>';
?>		<script language="javascript" src="bootstrap/jquery.min.js"></script>
		<div class="span10">
        	<h4>请添加需要监控的关键词</h4>
            <hr>
        	<form class="form-horizontal" method="post">
				<div class="control-group">
					 <label class="control-label" for="inputPassword">关键词速卖通所属类目：</label>
					<div class="controls">
						<select name="class_id" id="class_id">
                        	<option value='0'>请选择关键词类目分类</option>
                        	<?php foreach($class as $k=>$row){
								echo "<option value='{$row['cate_id']}'>{$row['cate_name']}</option>\r\n";
								}?>
                        </select>
                        &nbsp;
                        细分小类：<select name="class_id_small" id="class_id_small">
                        	<option value='0'>请选择大类</option>
                        </select>
					</div>
				</div>
                <div class="control-group">
					 <label class="control-label" for="inputPassword">亚马逊中搜索类目：</label>
					<div class="controls">
						<select name="amazon">
                        	<option value='0'>请选择关键词搜索分类</option>
                        	<?php 
							$amazon=getAmazonClass();
							foreach($amazon as $k=>$v){
								echo "<option value='{$k}'>{$v}</option>\r\n";
							}?>
                        </select>
					</div>
				</div>
                  
                <div class="control-group">
					 <label class="control-label" for="inputEmail">关键词：</label>
					<div class="controls">
						<input id="inputEmail" type="text" name="add_keyword" />
					</div>
				</div>
                
                <div class="control-group">
					 <label class="control-label" for="inputEmail">该词抓前几页产品：</label>
					<div class="controls">
						<input id="inputEmail" type="text" name="keyword_page"  value="3"/> <small>（销量排序抓取页数，热门产品可加大）</small>
					</div>
				</div>
				
				<div class="control-group">
					<div class="controls">
						 <label class="checkbox"><button type="submit" class="btn">添加此关键词</button>
					</div>
				</div>
                <hr>
                <p style="line-height:24px;"><small>
                <b>备注：</b><br />
                1，添加的关键词将会同时自动监控（速卖通/亚马逊/易贝）三个平台<br />
                2，添加关键词请指定所属类目（速卖通类目不发送，亚马逊搜索时必须发送类目）。<br />
                3，添加关键词之后3分钟左右内会陆续取得（速卖通/亚马逊/易贝）的数据。
                </small></p>
                <script language="javascript">
				$(document).ready(function(){
					var smallIDs=<?php echo json_encode($smidArr);?>;
					$("#class_id").change(function(){
						var bid=$("#class_id").val();
						$("#class_id_small").empty();
						$("#smallclass").append('<option value="0">请选择</option>'); 
						$.each(smallIDs[bid],function(smid,smval){
							$("#class_id_small").append("<option value='"+smid+"'>"+smval+"</option>");
						});
					});
				});
				</script>
			</form>
		</div>
<?php require("includes/foot.php");?>