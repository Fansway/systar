<?php
class Achievement extends SS_controller{
	function __construct(){
		$this->default_method='mine';
		parent::__construct();
	}
	
	function mine(){

		$field=array(
			'case_name'=>array('title'=>'案件','td_title'=>'width="25%"','content'=>'<a href="/cases/edit/{case}" class="right" style="margin-left:10px;">查看</a>{case_name}'),
			'client_name'=>array('title'=>'客户'),
			'account_time'=>array('title'=>'到账时间','td_title'=>'width="100px"'),
			'filed_time'=>array('title'=>'归档时间','td_title'=>'width="100px"'),
			'amount'=>array('title'=>'创收','td_title'=>'width="100px"'),
			'contribution'=>array('title'=>'贡献'),
			'bonus'=>array('title'=>'奖金'),
			'role'=>array('title'=>'角色')
		);
		$month_start_timestamp=strtotime(date('Y-m',$this->config->item('timestamp')).'-1');
		$month_end_timestamp=mktime(0,0,0,date('m',$this->config->item('timestamp'))+1,1,date('Y',$this->config->item('timestamp')));
		
		$achievement_sum=array(
			'_field'=>array(
				'field'=>'本月',
				'total'=>'全所',
				'my'=>'主办',
				'contribute'=>'贡献'
			),
			
			'contracted'=>array(
				'field'=>'签约',
				'total'=>$this->achievement->sum('contracted','total',$month_start_timestamp),
				'my'=>$this->achievement->sum('contracted','my',$month_start_timestamp),
				'contribute'=>$this->achievement->sum('contracted','contribute',$month_start_timestamp)
			),
			
			'estimated'=>array(
				'field'=>'预计',
				'total'=>$this->achievement->sum('estimated','total',$month_start_timestamp,$month_end_timestamp),
				'my'=>$this->achievement->sum('estimated','my',$month_start_timestamp,$month_end_timestamp),
				'contribute'=>$this->achievement->sum('estimated','contribute',$month_start_timestamp,$month_end_timestamp)
			),
			
			'collected'=>array(
				'field'=>'到账',
				'total'=>$this->achievement->sum('collected','total',$month_start_timestamp),
				'my'=>$this->achievement->sum('collected','my',$month_start_timestamp),
				'contribute'=>$this->achievement->sum('collected','contribute',$month_start_timestamp)
			)
		);
		
		$contribute_type=$this->input->get('contribute_type')=='actual'?'actual':'fixed';
		
		$achievement=$this->achievement->myBonus(array('case',$contribute_type),option('date_range/from_timestamp'),option('date_range/to_timestamp'));

		$achievement_dashboard=array(
			'_field'=>array(
				'奖金'
			),
			array(
				$achievement
			)
		);
		$table=$this->table->setFields($field)
			->setData($this->achievement->getList())
			->generate();
		$this->load->addViewData('list',$table);
		$achievement_view_data=compact('achievement_dashboard','achievement_sum');
		$this->load->addViewArrayData($achievement_view_data);
		$this->load->view('list');
		$this->load->view('achievement/list_sidebar',true,'sidebar');
	}

	function receivable($method=NULL){
		
		
		
		$field=array(
			'type'=>array('title'=>'类别','td_title'=>'width="85px"'),
			'case_name'=>array('title'=>'案件','td_title'=>'width="25%"','content'=>'<a href="/cases/edit/{case}" class="right" style="margin-left:10px;">查看</a>{case_name}'),
			'lawyers'=>array('title'=>'主办律师'),
			'fee'=>array('title'=>'预估','td_title'=>'width="100px"'),
			'pay_time'=>array('title'=>'时间','td_title'=>'width="100px"'),
			'uncollected'=>array('title'=>'未收','td_title'=>'width="100px"'),
			'clients'=>array('title'=>'客户')
		);
		
		$table=$this->table->setFields($field)
					->setData($this->achievement->getReceivableList($method))
					->generate();
				
		
		$this->load->addViewData('list',$table);

		$receivable_sum=$this->achievement->receivableSum($method,option('date_range/from'),option('date_range/to'));
		$this->load->addViewData('receivable_sum', $receivable_sum['sum']);

		$this->load->view('list');	
		$this->load->view('achievement/receivable_sidebar');
	}
	
	function caseBonus(){
		
		$field=array(
			'staff_name'=>array('title'=>'人员'),
			'contribute_sum'=>array('title'=>'合计贡献'),
			'bonus_sum'=>array('title'=>'合计奖金')
		);
		
		$table=$this->table->setFields($field)
					->setData($this->achievement->getCaseBonusList())
					->generate();
		
		$this->load->addViewData('list',$table);
		
		$this->load->view('list');
		$this->load->view('achievement/casebonus_sidebar',true,'sidebar');
	}

	function teambonus(){
		
		
		
		$field=array(
			'staff_name'=>array('title'=>'人员'),
			'bonus_sum'=>array('title'=>'团奖')
		);
		
		
		$table=$this->table->setFields($field)
					->setData($this->achievement->getTeambonusList())
					->generate();
		
		$this->load->addViewData('list',$table);
		
		$this->load->view('list');
	}
	
	function summary(){
		$monthly_collect=$this->achievement->getMonthlyAchievement();
		
		$months=array_sub($monthly_collect,'month');
		$collect=array_sub($monthly_collect,'collect');
		$contract=array_sub($monthly_collect,'contract');
		
		$series=array(
			array(
				'name'=>'创收',
				'data'=>$collect
			),
			array(
				'name'=>'签约',
				'data'=>$contract
			),
		);

		$months=json_encode($months);
		$series=json_encode($series,JSON_NUMERIC_CHECK);
		$this->load->addViewArrayData(compact('months','series'));
		$this->load->view('achievement/summary');
	}
	
	function query(){
		$monthly_queries=$this->achievement->getMonthlyQueries();
		$this->load->view_data['chart_monthly_queries_catogary']=json_encode(array_sub($monthly_queries,'month'));
		$chart_monthly_queries_series=array(
			array('name'=>'总量','data'=>array_sub($monthly_queries,'queries')),
			array('name'=>'归档','color'=>'#AAA','data'=>array_sub($monthly_queries,'filed_queries')),
			array('name'=>'在谈','data'=>array_sub($monthly_queries,'live_queries')),
			array('name'=>'新增案件','data'=>array_sub($monthly_queries,'cases'))

		);
		$this->load->view_data['chart_monthly_queries_series']=json_encode($chart_monthly_queries_series,JSON_NUMERIC_CHECK);

		$personally_queries=$this->achievement->getPersonallyQueries();
		$this->load->view_data['chart_personally_queries_catogary']=json_encode(array_sub($personally_queries,'staff_name'));
		$chart_personally_queries_series=array(
			array('name'=>'归档','color'=>'#AAA','data'=>array_sub($personally_queries,'filed_queries')),
			array('name'=>'成案','data'=>array_sub($personally_queries,'success_case')),
			array('name'=>'在谈','data'=>array_sub($personally_queries,'live_queries'))

		);
		$this->load->view_data['chart_personally_queries_series']=json_encode($chart_personally_queries_series,JSON_NUMERIC_CHECK);

		$personally_type_queries=$this->achievement->getPersonallyTypeQueries();
		$this->load->view_data['chart_personally_type_queries_catogary']=json_encode(array_sub($personally_type_queries,'staff_name'));
		$chart_personally_type_queries_series=array(
			array('name'=>'网上咨询','data'=>array_sub($personally_type_queries,'online_queries')),
			array('name'=>'电话咨询','data'=>array_sub($personally_type_queries,'call_queries')),
			array('name'=>'面谈咨询','data'=>array_sub($personally_type_queries,'face_queries'))

		);
		$this->load->view_data['chart_personally_type_queries_series']=json_encode($chart_personally_type_queries_series,JSON_NUMERIC_CHECK);
		$this->load->view('achievement/query');
	}
	
	function client(){
		//TODO 新增客户统计
	}
	
	function caseType(){
		$chart_casetype_income_data=$this->achievement->getCaseTypeIncome();
		$this->load->addViewData('chart_casetype_income_data', json_encode($chart_casetype_income_data,JSON_NUMERIC_CHECK));
		$this->load->view('achievement/casetype');
	}
}
?>