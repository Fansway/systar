<?php
class Query extends SS_controller{
	function __construct(){
		$this->default_method='lists';
		parent::__construct();
	}
	
	function filed(){
		$this->lists('filed');
	}
	
	function lists($para=NULL){

		$field=array(
			'first_contact'=>array('title'=>'日期','td_title'=>'width="95px"','td'=>'hash="cases/edit/{id}"'),
			'num'=>array('title'=>'编号','td_title'=>'width="143px"'),
			'client_name'=>array('title'=>'咨询人','wrap'=>array('mark'=>'a','href'=>'#client/edit/{client}')),
			'type'=>array('title'=>'方式','td_title'=>'width="80px"'),
			'source'=>array('title'=>'来源'),
			'staff_names'=>array('title'=>'接洽人'),
			'summary'=>array('title'=>'概况','td'=>'class="ellipsis" title="{summary}"'),
			'comment'=>array('title'=>'备注','td'=>'class="ellipsis" title="{comment}"')
		);
		$table=$this->table->setFields($field)
			->setData($this->query->getList($para))
			->generate();

		$this->load->addViewData('list',$table);
		$this->load->view('list');
	}

	function add(){
		$this->query->id=$this->query->add(array('is_query'=>true));
		$this->output->status='redirect';
		$this->output->data='query/edit/'.$this->query->id;
	}

	function edit($id){
		
		$this->query->id=$id;
		
		$this->load->model('client_model','client');
		$this->load->model('staff_model','staff');
		
		$query=$this->query->fetch($id);
		
		$labels=$this->query->getLabels($this->query->id);
		
		if(!$query['name']){
			$this->output->setData('未命名咨询','name');
		}else{
			$this->output->setData(strip_tags($query['name']), 'name');
		}
		
		$this->load->addViewData('cases', $query);

		$this->load->view('query/edit');
		$this->load->view('query/edit_sidebar',true,'sidebar');
	}
	
	function submit($submit,$id){
		
		$this->query->id=$id;

		$this->load->model('client_model','client');
		$this->load->model('staff_model','staff');
		
		$query=array_merge($this->query->fetch($id),$this->input->sessionPost('cases'));
		
		try{
			
			if($submit=='cancel'){
				unset($_SESSION[CONTROLLER]['post'][$this->query->id]);
				$this->query->clearUserTrash();
			}
		
			elseif($submit=='query'){
				
				$labels=$this->input->sessionPost('labels');
				
				if(!$labels['咨询方式']){
					$this->output->message('请选择咨询方式','warning');
					throw new Exception;
				}
				
				if(!$labels['领域']){
					$this->output->message('请选择业务领域','warning');
					throw new Exception;
				}
				
				if(!$query['summary']){
					$this->output->message('请填写咨询概况','warning');
					throw new Exception;
				}
				
				$related_staff_name=$this->input->sessionPost('related_staff_name');
				
				if(!$related_staff_name['接洽律师']){
					$this->output->message('请填写接洽律师（跟进人员中间一项）');
					throw new Exception;
				}
				
				$related_staff=array();
				try{
					foreach($related_staff_name as $role => $staff_name){
						$related_staff[$role]=$this->staff->check($staff_name);
					}
				}
				catch(Exception $e){
					//截获staff->check可能抛出的错误
				}
				
				$query['is_query']=true;

				$client=$this->input->sessionPost('client');
				
				if(!$client['id']){
					if(!$client['name']){
						$this->output->message('请填写咨询人', 'warning');
						throw new Exception;
					}
					
					$client_profiles=$this->input->sessionPost('client_profiles');
					
					if(!$client_profiles['电话'] && !$client_profiles['电子邮件']){
						$this->output->message('至少输入一种联系方式','warning');
						throw new Exception;
					}
					
					if(!isset($client['staff'])){
						$client['staff']=$this->staff->check($client['staff_name']);
					}
					
					$client_source=$this->input->sessionPost('client_source');
					
					$client['source']=$this->client->setSource($client_source['type'], @$client_source['detail']);

					$client['id']=$this->client->add(
						$client
						+array('profiles'=>$client_profiles)
						+array('labels'=>array('类型'=>'潜在客户'))
					);
				}

				post('cases/num',$this->query->getNum($this->query->id,NULL,$labels['领域'],true,$query['first_contact']));

				post('cases/display',true);
				
				post('cases/name',$client['name'].' 咨询');
				
				$this->query->update($this->query->id,post('cases'));
				
				$this->query->updateLabels($this->query->id, post('labels'));

				$this->query->addPeople($this->query->id,$client['id'],'客户');
				
				foreach($related_staff as $role=>$staff){
					$this->query->addStaff($this->query->id,$staff,$role);
				}
			}
			if(!$this->output->status){
				$this->output->status='success';
			}
			
		}catch(exception $e){
			$this->output->status='fail';
		}
	}
}
?>