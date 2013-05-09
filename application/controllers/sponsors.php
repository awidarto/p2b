<?php

class Sponsors_Controller extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->controller_name = str_replace('_Controller', '', get_class());
		
		$this->crumb = new Breadcrumb();
		$this->crumb->add(strtolower($this->controller_name),ucfirst($this->controller_name));

		$this->model = new Sponsor();

	}

	public function get_index()
	{

		$this->heads = array(
			array('Title',array('search'=>true,'sort'=>true)),
			array('Shorts',array('search'=>true,'sort'=>true)),
			array('Slug',array('search'=>true,'sort'=>true)),
			array('Section',array('search'=>true,'sort'=>true)),
			array('Category',array('search'=>true,'sort'=>true)),
			array('Tags',array('search'=>true,'sort'=>true)),
			array('Created',array('search'=>true,'sort'=>true)),
			array('Last Update',array('search'=>true,'sort'=>true)),
		);

		return parent::get_index();

	}

	public function post_index()
	{
		$this->fields = array(
			array('title',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true,'callback'=>'namePic','attr'=>array('class'=>'expander'))),
			array('shorts',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			array('slug',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			array('section',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			array('category',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			array('tags',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			array('createdDate',array('kind'=>'date','query'=>'like','pos'=>'both','show'=>true)),
			array('lastUpdate',array('kind'=>'date','query'=>'like','pos'=>'both','show'=>true)),
		);

		return parent::post_index();
	}

	public function post_add($data = null)
	{
		$this->validator = array(
		    'title' => 'required', 
		    'shorts' => 'required',
		    'slug' => 'required',
		    'section' => 'required',
		    'category' => 'required',
		    'tags' => 'required'
	    );

		//transform data before actually save it

		$data = Input::get();

		// access posted object array
		$files = Input::file();

		$data['publishFrom'] = new MongoDate(strtotime($data['publishFrom']));
		$data['publishUntil'] = new MongoDate(strtotime($data['publishUntil']));

		//normalize
		$data['cache_id'] = '';
		$data['cache_obj'] = '';
		$data['groupId'] = '';
		$data['groupName'] = '';

		$sponsorpic = array();

		foreach($files as $key=>$val){
			if($val['name'] != ''){
				$sponsorpic[$key] = $val;
			}				
		}

		$data['sponsorpic'] = $sponsorpic;

		return parent::post_add($data);
	}

	public function makeActions($data){
		$delete = '<a class="action icon-"><i>&#xe001;</i><span class="del" id="'.$data['_id'].'" >Delete</span>';
		$edit =	'<a class="icon-"  href="'.URL::to('sponsors/edit/'.$data['_id']).'"><i>&#xe164;</i><span>Update Sponsor</span>';

		$actions = $edit.$delete;
		return $actions;
	}

	public function namePic($data){
		$display = HTML::image(URL::base().'/storage/sponsors/'.$data['_id'].'/sm_pic0'.$data['defaultpic'].'.jpg?'.time(), 'sm_pic01.jpg', array('id' => $data['_id']));
		return $display;
	}

	public function afterUpdate($id)
	{

		$files = Input::file();

		$newid = $id;

		$newdir = realpath(Config::get('kickstart.storage')).'/sponsors/'.$newid;

		if(!file_exists($newdir)){
			mkdir($newdir,0777);
		}

		foreach($files as $key=>$val){

			if($val['name'] != ''){

				$val['name'] = fixfilename($val['name']);

				Input::upload($key,$newdir,$val['name']);

				$path = $newdir.'/'.$val['name'];

				foreach(Config::get('shoplite.picsizes') as $s){
					$smsuccess = Resizer::open( $path )
		        		->resize( $s['w'] , $s['h'] , $s['opt'] )
		        		->save( Config::get('kickstart.storage').'/sponsors/'.$newid.'/'.$s['prefix'].$key.$s['ext'] , $s['q'] );					
				}

			}				
		}

		return $id;

	}

	public function afterSave($obj)
	{

		$files = Input::file();

		$newid = $obj['_id']->__toString();

		$newdir = realpath(Config::get('kickstart.storage')).'/sponsors/'.$newid;

		if(!file_exists($newdir)){
			mkdir($newdir,0777);
		}

		foreach($files as $key=>$val){

			if($val['name'] != ''){

				$val['name'] = fixfilename($val['name']);

				Input::upload($key,$newdir,$val['name']);

				$path = $newdir.'/'.$val['name'];

				foreach(Config::get('shoplite.picsizes') as $s){
					$smsuccess = Resizer::open( $path )
		        		->resize( $s['w'] , $s['h'] , $s['opt'] )
		        		->save( Config::get('kickstart.storage').'/sponsors/'.$newid.'/'.$s['prefix'].$key.$s['ext'] , $s['q'] );					
				}

			}				
		}

		return $obj;
	}

}