<?php
class SS_Loader extends CI_Loader{
	
	var $main_view_loaded=FALSE;
	var $sidebar_loaded=FALSE;

	var $view_data=array();//要传递给视图的参数
	
	var $blocks=array();
	
	var $inner_js='';
	
	/**
	 * 传统视图输出内容被以ajax方式传输时，指定载入到页面的位置
	 * @var type 
	 */
	var $selector='article';
	
	/**
	 * 在ajax响应页面中，用来保存提示信息的数组
	 */
	var $message=array(
		'notice'=>array(),
		'warning'=>array()
	);
	
	function __construct(){
		parent::__construct();
	}

	function getViewData($param=NULL){
		if(isset($param)){
			return $this->view_data[$param];
		}else{
			return $this->view_data;
		}
	}
	
	/**
	 * 将数据传输给视图
	 * @param $name 视图中可以调用的变量名
	 * @param $value 数据
	 */
	function addViewData($name,$value){
		$this->view_data+=array($name=>$value);
	}
	
	/**
	 * 将数据传输给视图（数组形式）
	 * @param array $array 数据 Array(视图中可以调用的变量名=>值,..)
	 */
	function addViewArrayData(array $array){
		$this->view_data+=$array;
	}
	
	/**
	 * @param $part_name: FALSE:进入输出缓存, 否则存入Loader::part[$part_name]
	 */
	function view($view, $return=FALSE, $block_name = FALSE){
		
		$vars=array_merge($this->getViewData());//每次载入视图时，都将当前视图数据传递给他一次
		
		if($block_name===FALSE){
			return parent::view($view, $vars, $return);
		}
		else{
			if(!array_key_exists($block_name, $this->blocks)){
				$this->blocks[$block_name]='';
			}
			
			$block=parent::view($view, $vars, TRUE);
			
			$this->blocks[$block_name].=$block;
			
			return $block;
		}
		
	}
	
	/**
	 * 从$_SESSION[CONTROLLER][post][对象ID]中取得相应值，取不到的话从Loader::view_data里取
	 * @param $index
	 * @return mixed
	 */
	function value($index){
		if(!is_null(post($index))){
			return post($index);
		}else{
			$CI=&get_instance();

			$view_data=$CI->load->view_data;

			$index_array=explode('/',$index);

			if(isset($view_data[$index_array[0]])){
				$value=$view_data[$index_array[0]];
			}else{
				return;
			}

			for($i=1;$i<count($index_array);$i++){
				if(isset($value[$index_array[$i]])){
					$value=$value[$index_array[$i]];
				}else{
					return;
				}
			}

			return $value;
		}
	}
	
}
?>