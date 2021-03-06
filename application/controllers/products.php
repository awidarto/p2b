<?php

class Products_Controller extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->controller_name = str_replace('_Controller', '', get_class());

		$this->title = $this->controller_name;

		$this->crumb = new Breadcrumb();
		$this->crumb->add(strtolower($this->controller_name),ucfirst($this->controller_name));

		$this->model = new Product();

	}

	public function get_index()
	{

		$this->heads = array(
			array('Name',array('search'=>true,'sort'=>true)),
			array('Product Code',array('search'=>true,'sort'=>true)),
			array('Permalink',array('search'=>true,'sort'=>true)),
			array('Description',array('search'=>true,'sort'=>true)),
			array('Section',array('search'=>true,'sort'=>true,'select'=>Config::get('shoplite.search_sections'))),
			array('Category',array('search'=>true,'sort'=>true,'select'=>Config::get('shoplite.search_categories'))),
			//array('Category',array('search'=>true,'sort'=>true)),
			//array('Tags',array('search'=>true,'sort'=>true)),
			array('Currency',array('search'=>true,'sort'=>true)),
			array('Retail Price',array('search'=>true,'sort'=>true)),
			array('Sale Price',array('search'=>true,'sort'=>true)),
			//array('Effective From',array('search'=>true,'sort'=>true)),
			//array('Effective Until',array('search'=>true,'sort'=>true)),
			array('Created',array('search'=>true,'sort'=>true,'date'=>true)),
			array('Last Update',array('search'=>true,'sort'=>true,'date'=>true)),
			//array('Productsequence',array('search'=>true,'sort'=>true))
		);

		return parent::get_index();

	}

	public function post_index()
	{
		$this->fields = array(
			array('name',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true,'callback'=>'namePic','attr'=>array('class'=>'expander'))),
			array('productcode',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			array('permalink',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			array('description',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			array('section',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			array('category',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			//array('tags',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			array('priceCurrency',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			array('retailPrice',array('kind'=>'currency','query'=>'like','pos'=>'both','show'=>true)),
			array('salePrice',array('kind'=>'currency','query'=>'like','pos'=>'both','show'=>true)),
			//array('effectiveFrom',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			//array('effectiveUntil',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true)),
			array('createdDate',array('kind'=>'date','query'=>'like','pos'=>'both','show'=>true)),
			array('lastUpdate',array('kind'=>'date','query'=>'like','pos'=>'both','show'=>true)),
			//array('productsequence',array('kind'=>'text','query'=>'like','pos'=>'both','show'=>true))
		);

		return parent::post_index();
	}

	public function post_add($data = null)
	{
		$this->validator = array(
		    'name' => 'required',
		    'productcode' => 'required',
		    'permalink' => 'required',
		    'description' => 'required',
		    'category' => 'required',
		    //'tags' => 'required',
		    'priceCurrency' => 'required',
		    'retailPrice' => 'required',
		    //'salePrice' => 'required',
		    'effectiveFrom' => 'required',
		    'effectiveUntil' => 'required'
	    );

		//transform data before actually save it

		$data = Input::get();

		$in = array($data['size'],$data['color'],$data['qty'],$data['link']);
		$keys = array('size','color','qty','link');
		$types = array('text','text','text','text');
		$data['variants'] = combiner($in,$keys,$types);

		$in = array($data['related'],$data['relatedId']);
		$keys = array('related','relatedId');
		$types = array('text','text');
		$data['relatedProducts'] = combiner($in,$keys,$types);

		$in = array($data['component'],$data['componentId']);
		$keys = array('component','componentId');
		$types = array('text','text');
		$data['componentProducts'] = combiner($in,$keys,$types);

		$in = array($data['cfield'],$data['cvalue'],$data['cunit']);
		$keys = array('field','val','unit');
		$types = array('text','text','text');
		$data['customFields'] = combiner($in,$keys,$types);

		$customs = customcombiner($data['cfield'],$data['cvalue'],$data['cunit']);

		$data = array_merge($data,$customs);

		// access posted object array
		$files = Input::file();

		$data['publishFrom'] = new MongoDate(strtotime($data['publishFrom']));
		$data['publishUntil'] = new MongoDate(strtotime($data['publishUntil']));

		$data['retailPrice'] = new MongoInt64($data['retailPrice']);
		$data['salePrice'] = new MongoInt64($data['salePrice']);

		$seq = new Sequence();

		$rseq = $seq->find_and_modify(array('_id'=>'product'),array('$inc'=>array('seq'=>1)),array('seq'=>1),array('new'=>true));

		$regsequence = str_pad($rseq['seq'], 6, '0',STR_PAD_LEFT);

		//$reg_number[] = $regsequence;

		$data['productsequence'] = $regsequence;

		$data['onsale'] = (isset($data['onsale']) && $data['onsale'] == 'Yes')?true:false;
		$data['groupParent'] = (isset($data['groupParent']) && $data['groupParent'] == 'Yes')?true:false;

		//normalize
		$data['cache_id'] = '';
		$data['cache_obj'] = '';
		$data['groupId'] = '';
		$data['groupName'] = '';

		$productpic = array();

		foreach($files as $key=>$val){
			if($val['name'] != ''){
				$productpic[$key] = $val;
			}
		}

		$data['productpic'] = $productpic;

		// deal with tags
		$data['tags'] = explode(',',$data['tags']);

		if(count($data['tags']) > 0){
			$tag = new Tag();
			foreach($data['tags'] as $t){
				$tag->update(array('tag'=>$t),array('$inc'=>array('count'=>1)),array('upsert'=>true));
			}
		}

		return parent::post_add($data);
	}


	public function post_edit($id,$data = null)
	{
		//print_r(Input::get());


		$this->validator = array(
		    'name' => 'required',
		    'productcode' => 'required',
		    'permalink' => 'required',
		    'description' => 'required',
		    'category' => 'required',
		    //'tags' => 'required',
		    'priceCurrency' => 'required',
		    'retailPrice' => 'required',
		    //'salePrice' => 'required',
		    'effectiveFrom' => 'required',
		    'effectiveUntil' => 'required'
	    );

		//transform data before actually save it

		$data = Input::get();

		$in = array($data['size'],$data['color'],$data['qty'],$data['link']);
		$keys = array('size','color','qty','link');
		$types = array('text','text','text','text');
		$data['variants'] = combiner($in,$keys,$types);

		$in = array($data['related'],$data['relatedId']);
		$keys = array('related','relatedId');
		$types = array('text','text');
		$data['relatedProducts'] = combiner($in,$keys,$types);

		$in = array($data['component'],$data['componentId']);
		$keys = array('component','componentId');
		$types = array('text','text');
		$data['componentProducts'] = combiner($in,$keys,$types);

		$in = array($data['cfield'],$data['cvalue'],$data['cunit']);
		$keys = array('field','val','unit');
		$types = array('text','text','text');
		$data['customFields'] = combiner($in,$keys,$types);

		$customs = customcombiner($data['cfield'],$data['cvalue'],$data['cunit']);

		$data = array_merge($data,$customs);

		//print_r($customs);

		// access posted object array
		$files = Input::file();

		$data['publishFrom'] = new MongoDate(strtotime($data['publishFrom']));
		$data['publishUntil'] = new MongoDate(strtotime($data['publishUntil']));

		$data['retailPrice'] = new MongoInt64($data['retailPrice']);
		$data['salePrice'] = new MongoInt64($data['salePrice']);

		$productpic = array();

		foreach($files as $key=>$val){
			if($val['name'] != ''){
				$productpic[$key] = $val;
			}
		}

		$data['onsale'] = (isset($data['onsale']) && $data['onsale'] == 'Yes')?true:false;
		$data['groupParent'] = (isset($data['groupParent']) && $data['groupParent'] == 'Yes')?true:false;

		$data['productpic'] = $productpic;

		// deal with tags
		$data['tags'] = explode(',',$data['tags']);

		if(count($data['tags']) > 0){
			$tag = new Tag();
			foreach($data['tags'] as $t){
				$tag->update(array('tag'=>$t),array('$inc'=>array('count'=>1)),array('upsert'=>true));
			}
		}

		return parent::post_edit($id,$data);
	}

	public function makeActions($data){
		$delete = '<a class="action icon-"><i>&#xe001;</i><span class="del" id="'.$data['_id'].'" >Delete</span>';
		$edit =	'<a class="icon-"  href="'.URL::to('products/edit/'.$data['_id']).'"><i>&#xe164;</i><span>Update Product</span>';

		$actions = $edit.$delete;
		return $actions;
	}

	public function namePic($data){
		$name = HTML::link('products/view/'.$data['_id'],$data['name']);
		$display = HTML::image(URL::base().'/storage/products/'.$data['_id'].'/sm_pic0'.$data['defaultpic'].'.jpg?'.time(), 'sm_pic01.jpg', array('id' => $data['_id']));
		return $display.'<br />'.$name;
	}

	public function beforeValidateAdd($data)
	{
		$data['size'] = '';
		$data['color'] = '';
		$data['qty'] = '';
		$data['link'] = '';
		$data['related'] = '';
		$data['relatedId'] = '';
		$data['cfield'] = '';
		$data['cvalue'] = '';
		$data['cunit'] = '';

		return $data;
	}

	public function beforeUpdateForm($population){
		if(isset($population['tags']) && is_array($population['tags']))
		{
			$population['tags'] = implode(',', $population['tags'] );
		}

		$population['size'] = '';
		$population['color'] = '';
		$population['qty'] = '';
		$population['link'] = '';
		$population['related'] = '';
		$population['relatedId'] = '';
		$population['cfield'] = '';
		$population['cvalue'] = '';
		$population['cunit'] = '';

		//print_r($population['variants']);

		//$population['variants'] = array_unique($population['variants']);

		$keycheck = array();

		foreach ($population['variants'] as $v) {
			//print $v['size'].'_'.$v['color'];
			$keycheck[] = $v['size'].'_'.$v['color'].'_'.$v['link'];
		}

		$keycheck = array_unique($keycheck);

		//print_r($keycheck);

		$vars = array();

		foreach($keycheck as $k)
		{
			$pop = array();

			$v = explode('_',$k);
			$variant_params = array(
					'size' => $v[0],
	                'color' => $v[1]
				);

			$variants = getvariantinventory($population['_id']->__toString(),$variant_params);

            //$pop['qty_avail'] = $variants['avail'];
			$pop['size'] = $v[0];
            $pop['color'] = $v[1];
            $pop['qty'] = $variants['total'];
            $pop['link'] = $v[2];
            $pop['avail'] = $variants['avail'];

            $vars[] = $pop;

		}

		//print_r($variants);
		$population['variants'] = $vars;

		return $population;
	}

	public function afterUpdate($id,$data = null)
	{

		$files = Input::file();

		$newid = $id;

		$newdir = realpath(Config::get('kickstart.storage')).'/products/'.$newid;

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
		        		->save( Config::get('kickstart.storage').'/products/'.$newid.'/'.$s['prefix'].$key.$s['ext'] , $s['q'] );
				}

			}
		}

		$inventory = new Inventory();

		if(isset($data['variants']) && count($data['variants']) > 0){

			$o = array();

            $nw = array();

			foreach($data['variants'] as $v){

                $nw[] = $v['size'].'_'.$v['color'];

				$v['productId'] = $id;

				$qty = (int) $v['qty'];

				unset($v['qty']);
				unset($v['link']);
				unset($v['avail']);

				$avail = $inventory->count($v);

				//print $qty.' vs '.$avail;


				if($qty > $avail){
					$qty = $qty - $avail;

					$v['status'] = 'available';
					$v['createdDate'] = new MongoDate();
					$v['cartId'] = '';

					for($i = 0; $i < $qty;$i++)
					{
						$v['_id'] = new MongoId(Str::random(24));
						$inventory->insert($v,array('upsert'=>false));
					}

				}elseif($qty < $avail){

					$qty = $avail - $qty;

					$d['productId'] = $id;
					$d['status'] = 'available';
					$d['cartId'] = '';

					for($i = 0; $i < $qty;$i++)
					{
						$inventory->deleteOne($d);
					}

				}

			}

		}

        //print_r($nw);

        //print_r($data['old_var']);

        $tobe_deleted = array_diff($data['old_var'],$nw);

        //print_r($tobe_deleted);

        if(!empty($tobe_deleted) && count($tobe_deleted) > 0){
            foreach($tobe_deleted as $td){
                if(in_array($td,$data['old_var'])){

                    $vs = explode('_',$td);

                    $d['size'] = $vs[0];
                    $d['color'] = $vs[1];
                    $d['productId'] = $id;
                    $d['status'] = 'available';
                    $d['cartId'] = '';

                    for($i = 0; $i < $qty;$i++)
                    {
                        $inventory->deleteOne($d);
                    }
                }
            }
        }

        //exit();

        return $id;
		//return false;

	}

	public function beforeUpdate($id,$data)
	{
		return $data;
	}

	public function afterSave($obj)
	{

		$files = Input::file();

		$newid = $obj['_id']->__toString();

		$newdir = realpath(Config::get('kickstart.storage')).'/products/'.$newid;

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
		        		->save( Config::get('kickstart.storage').'/products/'.$newid.'/'.$s['prefix'].$key.$s['ext'] , $s['q'] );
				}

			}
		}

		$inventory = new Inventory();

		if(isset($obj['variants']) && count($obj['variants']) > 0){

			$o = array();

			foreach($obj['variants'] as $v){

				$qty = (int) $v['qty'];

				$v['productId'] = $obj['_id']->__toString();
				$v['createdDate'] = new MongoDate();
				$v['status'] = 'available';
				$v['cartId'] = '';

				for($i = 0; $i < $qty;$i++)
				{
					$v['_id'] = new MongoId(Str::random(24));
					$inventory->insert($v,array('upsert'=>false));
				}

			}

		}

		return $obj;
	}

}