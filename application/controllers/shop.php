<?php

class Shop_Controller extends Base_Controller {

	/*
	|--------------------------------------------------------------------------
	| The Default Controller
	|--------------------------------------------------------------------------
	|
	| Instead of using RESTful routes and anonymous functions, you might wish
	| to use controllers to organize your application API. You'll love them.
	|
	| This controller responds to URIs beginning with "home", and it also
	| serves as the default controller for the application, meaning it
	| handles requests to the root of the application.
	|
	| You can respond to GET requests to "/home/profile" like so:
	|
	|		public function action_profile()
	|		{
	|			return "This is your profile!";
	|		}
	|
	| Any extra segments are passed to the method as parameters:
	|
	|		public function action_profile($id)
	|		{
	|			return "This is the profile for user {$id}.";
	|		}
	|
	*/

	public $restful = true;

	public function __construct(){
		//$this->filter('before','auth');
		$this->crumb = new Breadcrumb();
		$this->crumb->add('shop','Shop');
		//$this->filter('before', 'auth')->only(array('confirm'));
	}

	public function get_index()
	{
		$heads = array('#','Articles','Category','Tags','Action');
		$colclass = array('','span9','span1','span1');
		//$searchinput = array(false,'title','created','last update','creator','project manager','tags',false);
		$searchinput = array(false,'project','tags',false);

		return View::make('tables.simple')
			->with('title','Articles')
			->with('newbutton','New Article')
			->with('disablesort','0,3')
			->with('addurl','content/add')
			->with('colclass',$colclass)
			->with('searchinput',$searchinput)
			->with('ajaxsource',URL::to('content'))
			->with('ajaxpaygolf',URL::to('attendee/paystatusgolf'))
			->with('ajaxdel',URL::to('content/del'))
	        ->with('crumb',$this->crumb)
			->with('heads',$heads);
	}

	public function post_index()
	{
		$fields = array(array('title','body'),'projectTag');

		$rel = array('like','like');

		$cond = array('both','both');

		$pagestart = Input::get('iDisplayStart');
		$pagelength = Input::get('iDisplayLength');

		$limit = array($pagelength, $pagestart);

		$defsort = 1;
		$defdir = -1;

		$idx = 0;
		$q = array();
		$hilite = array();
		$hilite_replace = array();
		foreach($fields as $field){
			if(Input::get('sSearch_'.$idx))
			{
				$hilite_item = Input::get('sSearch_'.$idx);
				$hilite[] = $hilite_item;
				$hilite_replace[] = '<span class="hilite">'.$hilite_item.'</span>';

				if($rel[$idx] == 'like'){
					if(is_array($field)){
						$q = array('$or'=>'');
						$sub = array();
						foreach($field as $f){
							if($cond[$idx] == 'both'){
								$sub[] = array($f=> new MongoRegex('/'.Input::get('sSearch_'.$idx).'/i') );
							}else if($cond[$idx] == 'before'){
								$sub[] = array($f=> new MongoRegex('/^'.Input::get('sSearch_'.$idx).'/i') );						
							}else if($cond[$idx] == 'after'){
								$sub[] = array($f=> new MongoRegex('/'.Input::get('sSearch_'.$idx).'$/i') );						
							}
						}
						$q['$or'] = $sub;
					}else{
						if($cond[$idx] == 'both'){
							$q[$field] = new MongoRegex('/'.Input::get('sSearch_'.$idx).'/i');
						}else if($cond[$idx] == 'before'){
							$q[$field] = new MongoRegex('/^'.Input::get('sSearch_'.$idx).'/i');						
						}else if($cond[$idx] == 'after'){
							$q[$field] = new MongoRegex('/'.Input::get('sSearch_'.$idx).'$/i');						
						}						
					}
				}else if($rel[$idx] == 'equ'){
					$q[$field] = Input::get('sSearch_'.$idx);
				}
			}
			$idx++;
		}


		$document = new Content();

		/* first column is always sequence number, so must be omitted */
		$fidx = Input::get('iSortCol_0');
		if($fidx == 0){
			$fidx = $defsort;			
			$sort_col = $fields[$fidx];
			$sort_dir = $defdir;
		}else{
			$fidx = ($fidx > 0)?$fidx - 1:$fidx;
			$sort_col = $fields[$fidx];
			$sort_dir = (Input::get('sSortDir_0') == 'asc')?1:-1;
		}


		$count_all = $document->count();

		if(count($q) > 0){
			$documents = $document->find($q,array(),array($sort_col=>$sort_dir),$limit);
			$count_display_all = $document->count($q);
		}else{
			$documents = $document->find(array(),array(),array($sort_col=>$sort_dir),$limit);
			$count_display_all = $document->count();
		}




		$aadata = array();

		$counter = 1 + $pagestart;
		foreach ($documents as $doc) {
			if(isset($doc['tags'])){
				$tags = array();

				foreach($doc['tags'] as $t){
					$tags[] = '<span class="tagitem">'.$t.'</span>';
				}

				$tags = implode('',$tags);

			}else{
				$tags = '';
			}

			$item = View::make('content.item')->with('doc',$doc)->with('popsrc','content/view')->with('tags',$tags)->render();

			$item = str_replace($hilite, $hilite_replace, $item);

			$aadata[] = array(
				$counter,
				$item,
				$doc['category'],
				$tags,
				'<a href="'.URL::to('content/view/'.$doc['_id']).'"><i class="foundicon-clock action"></i></a>&nbsp;'.
				'<a href="'.URL::to('content/edit/'.$doc['_id']).'"><i class="foundicon-edit action"></i></a>&nbsp;'.
				'<i class="foundicon-trash action del" id="'.$doc['_id'].'"></i>'
			);
			$counter++;
		}

		
		$result = array(
			'sEcho'=> Input::get('sEcho'),
			'iTotalRecords'=>$count_all,
			'iTotalDisplayRecords'=> $count_display_all,
			'aaData'=>$aadata,
			'qrs'=>$q
		);

		return Response::json($result);

	}

	public function get_home()
	{
		$products = new Product();
		$articles = new Article();

		//$results = $model->find(array(),array(),array($sort_col=>$sort_dir),$limit);

		$pagelength= 3;
		$pagestart = 0;

		$limit = array($pagelength, $pagestart);

		$mixmatch = $products->find(array('section'=>'mixmatch'),array(),array('createdDate'=>-1),$limit);

		$pow = $products->find(array('section'=>'pow'),array(),array('createdDate'=>-1),$limit);

		$otb = $products->find(array('section'=>'otb'),array(),array('createdDate'=>-1),$limit);

		$kind = $products->find(array('section'=>'kind'),array(),array('createdDate'=>-1),$limit);
		
		$mixmatchartikel = $articles->find(array('section'=>'mixmatch'),array(),array('createdDate'=>-1),$limit);

		

		// /$mixandmact
		$new = array();
		$featured = array();

		return View::make('shop.home')
			->with('new',$new)
			->with('pow',$pow)
			->with('otb',$otb)
			->with('kind',$kind)
			->with('featured',$featured)
			->with('pow',$pow)
			// /->with('otb',$otb)
			->with('mixmatch',$mixmatch);
	}

	public function get_collection($category = 'all',$page = 0,$search = null)
	{
		$new = array();
		$featured = array();
		$mixmatch = array();
		
		return View::make('shop.collection')
			->with('new',$new)
			->with('featured',$featured)
			->with('mixmatch',$mixmatch);
	}


	public function get_pow($category = 'all',$page = 0,$search = null)
	{
		$products = new Product();

		//$results = $model->find(array(),array(),array($sort_col=>$sort_dir),$limit);

		$pagelength= 3;
		$pagestart = 0;

		$limit = array($pagelength, $pagestart);
		
		$new = array();
		$featured = array();
		$pow = $products->find(array('section'=>'pow'),array(),array('createdDate'=>-1),$limit);

		
		
		return View::make('shop.collection')
			->with('new',$new)
			->with('featured',$featured)
			->with('products',$pow);

	}

	public function get_otb($category = 'all',$page = 0,$search = null)
	{
		$products = new Product();
		$pagelength= 3;
		$pagestart = 0;

		$limit = array($pagelength, $pagestart);
		
		$new = array();
		$featured = array();
		$otb = $products->find(array('section'=>'otb'),array(),array('createdDate'=>-1),$limit);
		
		return View::make('shop.collection')
			->with('new',$new)
			->with('featured',$featured)
			->with('products',$otb);

	}

	public function get_mixmatch($category = 'all',$page = 0,$search = null)
	{
		$products = new Product();

		//$results = $model->find(array(),array(),array($sort_col=>$sort_dir),$limit);

		$pagelength= 3;
		$pagestart = 0;

		$limit = array($pagelength, $pagestart);

		$new = array();
		$featured = array();
		$mixmatch = $products->find(array('section'=>'mixmatch'),array(),array('createdDate'=>-1),$limit);
		
		return View::make('shop.collection')
			->with('new',$new)
			->with('featured',$featured)
			->with('products',$mixmatch);

	}

	public function get_kind($category = 'all',$page = 0,$search = null)
	{
		$products = new Product();
		$pagelength= 3;
		$pagestart = 0;

		$limit = array($pagelength, $pagestart);
		
		$new = array();
		$featured = array();
		$kind = $products->find(array('section'=>'kind'),array(),array('createdDate'=>-1),$limit);
		
		return View::make('shop.collection')
			->with('new',$new)
			->with('featured',$featured)
			->with('products',$kind);

	}

	public function get_about()
	{
		$new = array();
		$featured = array();
		$mixmatch = array();
		
		return View::make('shop.collection')
			->with('new',$new)
			->with('featured',$featured)
			->with('mixmatch',$mixmatch);

	}

	public function get_view($id,$slug = null){

		$this->crumb->add('content/view/'.$section,ucfirst($section));

		$this->crumb->add('content/view/'.$section.'/'.$category,ucfirst($category));


		if(is_null($slug)){
			$heads = array('#','Articles','Section','Category','Tags');
			$colclass = array('one','','one','one','two');
			//$searchinput = array(false,'title','created','last update','creator','project manager','tags',false);
			$searchinput = array(false,'article',false,false,'tags');

			return View::make('tables.simple')
				->with('title','Articles')
				->with('newbutton','New Article')
				->with('disablesort','0')
				->with('colclass',$colclass)
				->with('searchinput',$searchinput)
				->with('ajaxsource',URL::to('content/view/'.$section.'/'.$category))
				->with('ajaxdel',URL::to('content/del'))
		        ->with('crumb',$this->crumb)
				->with('heads',$heads);
		}else{

			$content = new Content();

			$article = $content->get(array('slug'=>$slug));

			$this->crumb->add('content/view/'.$section.'/'.$category.'/'.$slug,$article['title']);

			return View::make('content.view')
				->with('crumb',$this->crumb)
				->with('title',$article['title'])
				->with('body', $article['body']);

		}

		$project = new Content();

		$_id = new MongoId($id);

		$projectdata = $project->get(array('_id'=>$_id));

	}

	public function get_detail($id){

		$_id = new MongoId($id);
		$products = new Product();

		$product = $products->get(array('_id'=>$_id));

		$inventory = new Inventory();

		$variants = $inventory->find(array('productId'=>$_id),array('size'=>true,'color'=>true,'_id'=>false));

		$ca = array();
		$sa = array();

		foreach($variants as $v){
			$ca[] = $v['color'];
			$sa[] = $v['size'];
		}

		$related = array();
		if(isset($product['relatedProducts']) && count($product['relatedProducts']) > 0){
			foreach($product['relatedProducts'] as $r){
				$r_id = new MongoId($r['relatedId']);
				$related[] = $products->get(array('_id'=>$r_id));
			}
		}

		$product['relatedProducts'] = $related;


		$sizes = array_unique($sa);
		$colors = array_unique($ca);

		return View::make('shop.detail')
			->with('sizes',$sizes)
			->with('colors',$colors)
			->with('product',$product);
	}

	public function post_size()
	{


	}

	public function post_color()
	{
		$in = Input::get();

		$inv = new Inventory();

		$_pid = new MongoId($in['_id']);

		$colors = $inv->find(array('productId'=>$_pid,'size'=>$in['size']),array('color'=>true, '_id'=>false));

		//print_r($colors);

		$ca = array();
		foreach($colors as $c){
			$ca[] = $c['color'];
		}

		$ca = array_unique($ca);

		$html = '';
		$opt = '<option value="%s">%s</option>';
		foreach($ca as $c){
			$html .= sprintf($opt,$c,$c);
		}

		return Response::json(array('colors'=>$ca,'html'=>$html));

	}

	public function post_qty()
	{
		$in = Input::get();

		$inv = new Inventory();

		$_pid = new MongoId($in['_id']);

		$count = $inv->count(array('productId'=>$_pid,'size'=>$in['size'],'color'=>$in['color'],'status'=>'available'));

		$html = '';

		$opt = '<option value="%s">%s</option>';
		for($i = 1; $i <= $count; $i++){
			$html .= sprintf($opt,$i,$i);
		}

		return Response::json(array('qty'=>$count,'html'=>$html));

	}

	public function post_addtocart()
	{
		if(Auth::shoppercheck() == false ){

			return Response::json(array('result'=>'NOTSIGNEDIN','message'=>'You are not signed in'));

		}else{
			$in = Input::get();

		    $item['color'] = $in['color'];
		    $item['size'] = $in['size'];
		    $item['productId'] = $in['_id'];

		    $qty = $in['qty'];

	    	if(isset(Auth::shopper()->activeCart) == false || Auth::shopper()->activeCart == ''){
	    		$cart = $this->newCart();

	    	}else{
	    		$cart = $this->getCurrentCart();
	    	}

	    	//$result = $cart;

	    	$result = $this->addToCart($cart,$item,$qty);

	    	$query = $item;
	    	$query['productId'] = new MongoId($query['productId']);
	    	$query['status'] = 'available';

	    	$inv = new Inventory();

	    	$result['remaining'] = $inv->count($query);

			$carts = new Cart();

			$upcart = $carts->update(array('_id'=>$result['_id']),array('$set'=>array('items'=>$result['items'])),array('upsert'=>true));

			//return Response::json(array('result'=>'PRODUCTNOTAVAIL','message'=>'Product no longer available'));

			//return Response::json(array('result'=>'PRODUCTLESSQTY','message'=>'Available quantity is less than you ordered'));

			return Response::json(array('result'=>'PRODUCTADDED','message'=>'Product added into Shopping Cart','data'=>$result));
		}

	}

	public function post_signin()
	{
		$in = Input::get();

	    $username = Input::get('username');
	    $password = Input::get('password');

	    $item['color'] = $in['color'];
	    $item['size'] = $in['size'];
	    $item['productId'] = $in['_id'];

	    $qty = $in['qty'];

	    if ( $userdata = Auth::shopperattempt(array('username'=>$username, 'password'=>$password)) )
	    {
	    	
	    	if(Auth::shopper()->activeCart == ''){
	    		$cart = $this->newCart();
	    	}else{
	    		$cart = $this->getCurrentCart();
	    	}

	    	$result = $this->addToCart($cart,$item,$qty);

			print_r($result);

			return Response::json(array('result'=>'PRODUCTADDED','message'=>'Successfully Signed In and Product Added','data'=>$cart));
	    }
	    else
	    {
			return Response::json(array('result'=>'FAILEDSIGNEDIN','message'=>'You are not signed in'));
	    }

	}

	private function addToCart($cartobj, $item, $qty)
	{
		$carts = new Cart();

		$inventory = new Inventory();

		$query = $item;

		$query['status'] = 'available';
		$query['productId'] = new MongoId($query['productId']);

		$pagelength = $qty;
		$pagestart = 0;
		
		$limit = array($pagelength, $pagestart);

		$invitem = $inventory->find($query,array(),array('createdDate'=>1),$limit);

		$item_ids = array_keys($invitem);

		$up = array();

		foreach ($item_ids as $key) {
			$up[] = array('_id'=>new MongoId($key));

			$setinv = $inventory->update(array('_id'=>new MongoId($key),'status'=>'available'),
				array(
					'$set'=>array(
						'status'=>'incart',
						'cartId'=>$cartobj['_id']
					)
				)
			);
		}

		$aquery = array('productId'=>$query['productId'],
			'cartId'=>$cartobj['_id'],
			'status'=>'incart',
			'size'=>$item['size'],
			'color'=>$item['color']);

		$actual = $inventory->find($aquery);

		$item['items'] = $actual;
		$item['actual'] = count($actual);

		if(isset($item['ordered'])){
		    $item['ordered'] += $qty;
		}else{
		    $item['ordered'] = $qty;
		}

	    $cartobj['items'][$item['productId']][$item['size'].'_'.$item['color']] = $item;

	    return $cartobj;

	}

	private function removeFromCart($cartobj, $item, $qty)
	{
		$carts = new Cart();

	}

	private function getCurrentCart(){

		$carts = new Cart();

		$cart_id = Auth::shopper()->activeCart;

		$cart = $carts->get(array('_id'=>new MongoId($cart_id) ));

		return $cart;
	}

	private function newCart()
	{
		$thecart = array();
		$thecart['shopper_id'] = Auth::shopper()->id;
		$thecart['items'] = array();
		$thecart['createdDate'] = new MongoDate();
		$thecart['lastUpdate'] = new MongoDate();
		$thecart['cartStatus'] = 'open';
		$thecart['buyerDetail'] = Auth::shopper();

		$cart = new Cart();

		if($newcart = $cart->insert($thecart,array('upsert'=>true))){

			$shopper = new Shopper();

			$_id = new MongoId(Auth::shopper()->id);

			$shopper->update(array('_id'=>$_id),
				array('$set'=>array('activeCart'=>$newcart['_id']->__toString() )),
				array('upsert'=>true)
				);

			return $newcart;
		}else{
			return false;
		}

	}

	public function get_cart(){

		$this->filter('before','auth');

		$form = new Formly();

		$active_cart = new MongoId(Auth::shopper()->activeCart);

		$carts = new Cart();

		$cart = $carts->get(array('_id'=>$active_cart));

		$or = array();
		foreach($cart['items'] as $key=>$val){
			$or[] = array('_id'=>new MongoId($key));
		}

		$prods = new Product();

		$products = $prods->find(array('$or'=>$or));

		return View::make('shop.cart')
			->with('ajaxsource',URL::to('shop/cartloader'))
			->with('ajaxdel',URL::to('shop/itemdel'))
			->with('products',$products)
			->with('cart',$cart)
			->with('form',$form);
	}

	public function post_checkout()
	{
		//print_r(Input::get());
		$this->filter('before','auth');

		$form = new Formly();

		$in = Input::get();

		$active_cart = new MongoId($in['cartId']);

		$carts = new Cart();

		$cart = $carts->get(array('_id'=>$active_cart));

		$or = array();
		foreach($cart['items'] as $key=>$val){
			$or[] = array('_id'=>new MongoId($key));
		}

		$prods = new Product();

		$products = $prods->find(array('$or'=>$or));

		$shippingFee = 30000;

		return View::make('shop.checkout')
			->with('ajaxsource',URL::to('shop/cartloader'))
			->with('ajaxdel',URL::to('shop/itemdel'))
			->with('postdata',$in)
			->with('products',$products)
			->with('shippingFee',$shippingFee)
			->with('cart',$cart)
			->with('form',$form);

	}

	public function post_commit()
	{
		//print_r(Input::get());
		$this->filter('before','auth');

		$form = new Formly();

		$in = Input::get();

		$shoppers = new Shopper();

		$active_cart = new MongoId($in['cartId']);

		$carts = new Cart();

		$cart = $carts->get(array('_id'=>$active_cart));

		$or = array();
		foreach($cart['items'] as $key=>$val){
			$or[] = array('_id'=>new MongoId($key));
		}

		$prods = new Product();

		$products = $prods->find(array('$or'=>$or));

		$shippingFee = 30000;

		Event::fire('commit.checkout',array(Auth::shopper()->id,$in['cartId']));

		$carts->update(array('_id'=>$active_cart),array('$set'=>array( 'cartStatus'=>'checkedout', 'lastUpdate'=>new MongoDate() )));

		$shoppers->update(array('_id'=>new MongoId(Auth::shopper()->id)),
			array('$set'=>array('activeCart'=>'','prevCart'=>$in['cartId'] )), 
			array('upsert'=>true) );

		return View::make('shop.commit')
			->with('postdata',$in)
			->with('products',$products)
			->with('shippingFee',$shippingFee)
			->with('cart',$cart)
			->with('form',$form);

	}

	public function get_confirm(){

		//$this->filter('before','auth');

		$form = new Formly();

		return View::make('shop.confirm')
			->with('form',$form);
	}

	public function post_confirm(){

		//$this->filter('before','auth');

		$form = new Formly();

		$in = Input::get();

		$shoppers = new Shopper();

		$active_cart = new MongoId($in['cartId']);

		$carts = new Cart();

		$cart = $carts->get(array('_id'=>$active_cart));

		$or = array();
		foreach($cart['items'] as $key=>$val){
			$or[] = array('_id'=>new MongoId($key));
		}

		$prods = new Product();

		$products = $prods->find(array('$or'=>$or));

		$shippingFee = 30000;

		Event::fire('commit.checkout',array(Auth::shopper()->id,$in['cartId']));

		$carts->update(array('_id'=>$active_cart),array('$set'=>array( 'cartStatus'=>'checkedout', 'lastUpdate'=>new MongoDate() )));

		$shoppers->update(array('_id'=>new MongoId(Auth::shopper()->id)),
			array('$set'=>array('activeCart'=>'','prevCart'=>$in['cartId'] )), 
			array('upsert'=>true) );

		return View::make('shop.confirm')
			->with('postdata',$in)
			->with('products',$products)
			->with('shippingFee',$shippingFee)
			->with('cart',$cart)
			->with('form',$form);
	}

}