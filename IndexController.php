<?php
class Vplaza_Restapi_IndexController extends Mage_Core_Controller_Front_Action{
	
	/**
	* User Register 
 	*/
	public function signupAction()
	{
		$websiteId = Mage::app()->getWebsite()->getId();
		$store = Mage::app()->getStore();
		
		//Get post values
		$fname = 'VIP'; //$this->getRequest()->getPost('fname');
		$lname = 'Customer'; //$this->getRequest()->getPost('lname');
		$email = $this->getRequest()->getPost('email');
		$password = $this->getRequest()->getPost('password');
		$gender = $this->getRequest()->getPost('gender');
		
		//Save Address
		/*$address = Mage::getModel("customer/address");
		$address->setCustomerId($customer->getId())
				->setFirstname($customer->getFirstname())
				->setMiddleName($customer->getMiddlename())
				->setLastname($customer->getLastname())
				->setCountryId('HR')
				//->setRegionId('1') //state/province, only needed if the country is USA
				->setPostcode('31000')
				->setCity('Osijek')
				->setTelephone('0038511223344')
				->setFax('0038511223355')
				->setCompany('Inchoo')
				->setStreet('Kersov')
				->setIsDefaultBilling('1')
				->setIsDefaultShipping('1')
				->setSaveInAddressBook('1');*/
				
		//Save Customer Detail
		$customer = Mage::getModel("customer/customer");
		
		$customer->setWebsiteId($websiteId)
					->setStore($store);
		
		$customer->loadByEmail($email);
		
		if ($customer->getId()) {
			$response['msg'] = 'Email Already Exist';
			$response['status'] = 0;
			echo json_encode($response);
			exit;
	    }
		
		$customer->setEmail($email)
					->setFirstname($fname)
					->setLastname($lname)
					->setPassword($password)
					->setGender($gender);
		
		try {
			$customer->save();
			//$address->save();
			$response['msg'] = 'New User Registered Successfully';
			$response['status'] = 1;
			$response['user_id'] = $customer->getId();
		}
		catch (Exception $e) {
			$response['msg'] = $e->getMessage();
			$response['status'] = 0;
			//Zend_Debug::dump($e->getMessage());
		}
		echo json_encode($response);
		exit;
	}
	
	/**
	* Login 
 	*/
	public function loginAction()
	{
		require_once ("app/Mage.php");
		umask(0);
		ob_start();
		session_start();
		Mage::app('default');
		Mage::getSingleton("core/session", array("name" => "frontend"));
	
		$websiteId = Mage::app()->getWebsite()->getId();
		$store = Mage::app()->getStore();
		$customer = Mage::getModel("customer/customer");
		$customer->website_id = $websiteId;
		$customer->setStore($store);
		
		$email = $this->getRequest()->getPost('email');
		$password = $this->getRequest()->getPost('password');
		
		$response = array();
		
		if (!empty($email) && !empty($password )) {
			try {
				$customer->loadByEmail($email);
				$session = Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
				$ses = $session->login($email, $password);
				$ses = $session->getCustomer();
				$user_id = Mage::helper('customer')->getCustomer()->getData('entity_id');
				$response['msg'] = 'Login Successful';
				$response['user_id'] = $user_id;
				$response['status'] = 1;
			}catch(Exception $e){
				switch ($e->getCode()) {
					case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
						$value = Mage::helper('customer')->getEmailConfirmationUrl($email);
						$message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
						break;
					case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
						$message = $e->getMessage();
						break;
					default:
						$message = $e->getMessage();
				}
				
				$response['msg'] = $message;
				$response['status'] = 0;
				//$session->setUsername($email);
			}
		}
		else
		{
			$response['msg'] = 'Email and password are required';
			$response['status'] = 0;
		}
		
		echo json_encode($response);
	}
	
	/**
	* Social Login and Register 
 	*/
	public function socialAction()
	{
		require_once ("app/Mage.php");
		umask(0);
		ob_start();
		session_start();
		Mage::app('default');
		Mage::getSingleton("core/session", array("name" => "frontend"));
		
		$websiteId = Mage::app()->getWebsite()->getId();
		$store = Mage::app()->getStore();
		
		//Get post values
		$fname = $this->getRequest()->getPost('fname');
		$lname = $this->getRequest()->getPost('lname');
		$email = $this->getRequest()->getPost('email');
		$random = substr($fname,0,4).rand(10001,999999);
		$password = 'testing123';
		$gender = $this->getRequest()->getPost('gender');
		
		//Save Customer Detail
		$customer = Mage::getModel("customer/customer");
		
		$customer->setWebsiteId($websiteId)
					->setStore($store);
		
		$customer->loadByEmail($email);
		
		if ($customer->getId()) {
			if (!empty($email)) {
				
				$response['msg'] = 'Login Successful';
				$response['user_id'] = $customer->getId();
				$response['status'] = 1;
			}
			
			echo json_encode($response);
			exit;
	    }
		
		$customer->setEmail($email)
					->setFirstname($fname)
					->setLastname($lname)
					->setPassword($password)
					->setGender($gender);
		
		try {
			$customer->save();
			$customer->sendNewAccountEmail();
			if (!empty($email)) {
				try {
					$customer->loadByEmail($email);
					$session = Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);
					$ses = $session->login($email,$password);
					$ses = $session->getCustomer();
					$user_id = Mage::helper('customer')->getCustomer()->getData('entity_id');
					$response['msg'] = 'Login Successful';
					$response['user_id'] = $user_id;
					$response['status'] = 1;
				}catch(Exception $e){
					switch ($e->getCode()) {
						case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
							$value = Mage::helper('customer')->getEmailConfirmationUrl($email);
							$message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.', $value);
							break;
						case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
							$message = $e->getMessage();
							break;
						default:
							$message = $e->getMessage();
					}
					
					$response['msg'] = $message;
					$response['status'] = 0;
				}
			}
			
			$connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
			
			$connectionWrite->query("update customer_entity set social = '1' WHERE entity_id = ".$customer->getId());
			
			$response['msg'] = 'New User Registered Successfully';
			$response['status'] = 1;
			$response['user_id'] = $customer->getId();
		}
		catch (Exception $e) {
			$response['msg'] = $e->getMessage();
			$response['status'] = 0;
			//Zend_Debug::dump($e->getMessage());
		}
		echo json_encode($response);
		exit;
	}
	
	/**
	* Login User Detail 
 	*/
	public function getUserDetailAction()
	{
		$response = array();
		//By ID
		$id = $this->getRequest()->getPost('uid');
		$customerData = Mage::getModel('customer/customer')->load($id)->getData();
    	//Mage::log($customerData);
		if(!empty($customerData))
		{
			$response['data'] = $customerData;
			$response['msg'] = 'success';
			$response['status'] = 1;
		}
		else
		{
			$response['msg'] = 'error';
			$response['status'] = 1;	
		}
		echo json_encode($response);	
	}
	
	/**
	* User Profile 
 	*/
	public function editProfileAction()
	{
		$response = array();
		$uid = $this->getRequest()->getPost('uid');
		
		require_once './app/Mage.php';
		Mage::app('default');
		
		$fname = $this->getRequest()->getPost('fname');
		$lname = $this->getRequest()->getPost('lname');
		$email = $this->getRequest()->getPost('email');
		$gender = $this->getRequest()->getPost('gender');
		
		$connectionRead = Mage::getSingleton('core/resource')->getConnection('core_read');
		
		$select = $connectionRead->select()
				->from('customer_entity', array('*'))
				->where('email=?',$email);
		
		$row = $connectionRead->fetchRow($select);   //return rows
		
		if(!empty($row))
		{
			$select1 = $connectionRead->select()
				->from('customer_entity', array('*'))
				->where('email=?',$email)
				->where('entity_id =?', $uid);
			
			$row1 = $connectionRead->fetchRow($select1);   //return rows
			
			if(!empty($row1))
			{
				$connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
			
				//$connectionWrite->beginTransaction();
				
				$connectionWrite->query("update customer_entity_varchar set value = '".$fname."' WHERE entity_id = ".$uid. " AND attribute_id = '5'" );
				
				$connectionWrite->query("update customer_entity_varchar set value = '".$lname."' WHERE entity_id = ".$uid. " AND attribute_id = '7'" );
				
				$response['msg'] = 'Updated';
				$response['status'] = 1;
			}
			else
			{
				$response['msg'] = 'Sudah ada email pelanggan';
				$response['status'] = 0;	
			}
			
		}
		else
		{
			$connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write');
			
			$connectionWrite->beginTransaction();
			$data = array();
			$data['email'] = $email;
			
			//customer Entity for email
			$where = $connectionWrite->quoteInto('entity_id =?', $uid);
			$update = $connectionWrite->update('customer_entity', $data, $where);
			$connectionWrite->commit();
			
			$data1 = array();
			$data1['firstname'] = $fname;
			
			//customer varchar entity for name
			$where = $connectionWrite->quoteInto('entity_id =?', $uid,'attribute_id =?', '5');
			$connectionWrite->update('customer_entity_varchar', $data1, $where);
			$connectionWrite->commit();
			
			$data2 = array();
			$data2['lastname'] = $lname;
			$where = $connectionWrite->quoteInto('entity_id =?', $uid,'attribute_id =?', '7');
			$connectionWrite->update('customer_entity_varchar', $data2, $where);
			$connectionWrite->commit();
			
			$response['msg'] = 'Akun informasi tersimpan.';
			$response['status'] = 1;
		}
		
		echo json_encode($response);
	}
	
	/**
	* Reset Password
	* Not Using
 	*/
	public function resetpassAction()
	{
		$email='neerajbwr89@gmail.com';

		$customer = Mage::getModel('customer/customer')
                    ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                    ->loadByEmail($email);
		$customer->sendPasswordResetConfirmationEmail();
	}
	
	/**
	* Change password 
 	*/
	public function changePassAction()
	{
		/*$customerid = 285925;
		$newpassword = 123456;
		$customer = Mage::getModel('customer/customer')->load($customerid);
		$customer->setPassword($newpassword);
		$customer->save();
		exit;*/
		
		$response = array();
		$validate = 0; 
		$result = '';
		$customerid = $this->getRequest()->getPost('uid');
		$email = $this->getRequest()->getPost('email');
		$oldpassword = $this->getRequest()->getPost('oldpass');
		$newpassword = $this->getRequest()->getPost('newpass');
		$store = Mage::app()->getStore()->getId();
		
		$websiteId = Mage::getModel('core/store')->load($store)->getWebsiteId();
		try {
			 $login_customer_result = Mage::getModel('customer/customer')->setWebsiteId($websiteId)->authenticate($email, $oldpassword);
			 $validate = 1;
		}
		catch(Exception $ex) {
			 $validate = 0;
		}
		
		if($validate == 1) {
			try {
				$customer = Mage::getModel('customer/customer')->load($customerid);
				$customer->setPassword($newpassword);
				$customer->save();
				$response['msg'] = 'Your Password has been Changed Successfully';
				$response['status'] = 1;
			}
			catch(Exception $ex) {
				$response['msg'] = 'Error : '.$ex->getMessage();
				$response['status'] = 0;
			}
		}
		else {
			$response['msg'] = 'Incorrect Old Password.';
			$response['status'] = 0;
		}
		echo json_encode($response);
		exit;
	}
	
	/**
	* Main Cateogries 
 	*/
	public function categoriesAction()
	{
		$response = array();
		$Menu = $this->getLayout()->createBlock('cms/block')->setBlockId('homepage_menu')->toHtml();
		$MENU = json_decode($Menu);
		$eventCategoryIDS = array();
		foreach($MENU[1] as $ev){
			$eventCategoryIDS[]=$ev->event_category;
		}
		$idEvent = explode(',',implode(',',$eventCategoryIDS));
		$i=0;
		foreach($MENU[0] as $index=>$menu) {
			
			$response[$i]['id'] = $menu->ec_id;
			$response[$i]['name'] = $menu->ec_name;
			$response[$i]['path'] = $menu->ec_url;
			
			//Sub Cateogry
			/*if(in_array($menu->ec_id,$idEvent)){
				$j=0;
				$c_all = 1;
				foreach($MENU[1] as $key => $event){
					$event_ids = explode(",",$event->event_category);
					if(in_array($menu->ec_id,$event_ids)) {
						
						$category = Mage::getResourceModel('catalog/category_collection')
							->addFieldToFilter('name', $event->name)
							->getFirstItem();
						
						$response[$i][0][$j]['id'] = $category->getId();
						$response[$i][0][$j]['name'] = $event->name;
						$response[$i][0][$j]['url'] = $event->url;
						
						$j++;
						$c_all++;
					}
				}	
			}*/
			$i++;
		}
		
		echo json_encode($response);
	}
	
	/**
	* Category Events
 	*/
	public function catEventsAction()
	{
		require_once './app/Mage.php';
		Mage::app('default');
		
		$response = array();
		
		$now            = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
        $dateEvent      = explode(" ", $now);
		$allEvents      = Mage::getModel('vipevent/vipevent')->getCollection()
                            ->addFieldToFilter('event_start', array('lteq' => $now ))
                            ->addFieldToFilter('event_end', array('gteq' => $now ))
                            ->setCurPage(1);
		
		$limit  = 10;
		$eventId = strtoupper($this->getRequest()->getPost('cat_name'));
		
		$eventCategory = Mage::getModel('vipevent/eventcategory')->getCollection()
						->addFieldToFilter('ec_name', array('eq' => $eventId ))
						->setCurPage(1);
		if ($eventCategory) {
			foreach ($eventCategory as $key => $val) {
				$ec_id  = $val->getEcId();
			}
			$like = "%-".$ec_id."-%";
			$allEvents ->addFieldToFilter('event_category', array('like' => $like));                
		}
		
		$entityAttr = Mage::getSingleton('core/resource')->getTableName('catalog_category_entity');
        $allEvents->getSelect()->join( array('cc'   => $entityAttr), 'cc.entity_id = main_table.category_id', array('cc.position'));
        $allEvents->setOrder('cc.position', 'ASC');
		
		if (count($allEvents) > 0 ) {
			$i=0;
			foreach ($allEvents as $key => $event) { 
                $response[$i]['category_id'] = $event->getCategoryId();
                $response[$i]['event_name'] = $event->getEventName();
                $response[$i]['event_img'] = ($event->getEventImage()  == "") ? "" : Mage::getBaseUrl('media').$event->getEventImage();
                $response[$i]['event_logo'] = ($event->getEventLogo()   == "") ? "" : Mage::getBaseUrl('media').$event->getEventLogo();
                $response[$i]['event_promo'] = ($event->getEventPromo()  == "") ? "" : $event->getEventPromo();
                $response[$i]['event_disc'] = ($event->getDiscAmount()  == "") ? "&nbsp;" : $event->getDiscAmount();
                $response[$i]['discount_info'] = ($event->getDiscInfo()    == "") ? "&nbsp;" : $event->getDiscInfo();
                $response[$i]['event_start'] = strtotime($event->getEventStart());
                $response[$i]['event_end'] = strtotime($event->getEventEnd());
                $response[$i]['category_url'] = Mage::getModel("catalog/category")->load($categoryId)->getUrl();
				//$response[$i]['event_start'] = strtotime(date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time())));
                //$response[$i]['event_end'] = strtotime($eventEnd);
                $response[$i]['date_diff'] = floor( ($nowEvent - $newEnd) /(60*60*24));
                if ($response[$i]['date_diff'] >= -1) 
                    $response[$i]['last_day'] = Mage::getBaseUrl('media'). DS ."wysiwyg" . DS . "last.png";

                $newEventEnd    = str_replace(" ", "-", $eventEnd); 
                $newEventEnd    = explode("-", $newEventEnd);
				
				$i++;
			}
			
			$data['msg'] = '';
			$data['status'] = 1;
		}
		else
		{
			$data['msg'] = 'No products found';
			$data['status'] = 0;
		}
		
		/*$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
		
		$cat_id = $this->getRequest()->getPost('cat_id');
		
		$select = $connection->select()
			->from('event_list', array('*')) // select * from tablename or use array('id','title') selected values
			->where('event_id=?',$cat_id);   // where id =1
		
		$rowsArray = $connection->fetchAll($select); // return all rows
		
		
		if(!empty($rowsArray))
		{
			$i=0;
			foreach($rowsArray as $row)
			{
				$response[$i] = $row;
				$response[$i]['event_logo'] = 'http://www.vipplaza.co.id/media/events/'.$row['event_logo'];
				$response[$i]['event_image'] = 'http://www.vipplaza.co.id/media/events/'.$row['event_image'];
				$response[$i]['event_start'] = strtotime($row['event_start']);
				$response[$i]['event_end'] = strtotime($row['event_end']);
				
				$i++;
			}
			
			$data['status'] = 1;
			$data['msg'] = '';
		}
		else
		{
			$data['msg'] = 'No products found';
			$data['status'] = 0;
		}*/
		
		$data['data']= $response;
		
		echo json_encode($data);
	}
	
	/**
	* Category Products 
 	*/
	public function catProductsAction()
	{
		require_once './app/Mage.php';
		Mage::app('default');
		
		$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
		
		$response = array();
		$catid = $this->getRequest()->getPost('cat_id');
		$order_price = $this->getRequest()->getPost('order_price');
		$order_name = $this->getRequest()->getPost('order_name');
		
		$select = $connection->select()
			->from('event_list', array('*')) // select * from tablename or use array('id','title') selected values
			->where('category_id=?',$catid);   // where id =1
			//->group('title');               // group by title

		$rowsArray = $connection->fetchrow($select);
		
		$category = Mage::getModel('catalog/category')->load($catid);
		$products = Mage::getResourceModel('catalog/product_collection')
        	->setStoreId(Mage::app()->getStore()->getId())
			->addCategoryFilter($category)
			->addAttributeToSort('name', $order_name)
			->addAttributeToSort('price', $order_price);
		
		//$products->getSelect()->order('name',$order_name);
		
		$productmodel = Mage::getModel('catalog/product');
		
		if(!empty($products))
		{
			$i=0;
			foreach($products as $product)
			{
				$_product = $productmodel->load($product->getId());
				$response[$i]['id'] = $product->getId();
				$response[$i]['name'] = $_product->getName();
				$response[$i]['short_description'] = $_product->getShortDescription();
				$response[$i]['long_description'] = $_product->getDescription();
				$response[$i]['price'] = number_format($_product->getPrice(),0,",",".");
				$response[$i]['speprice'] = number_format($_product->getSpecialPrice(),0,",",".");
				$response[$i]['img'] = $_product->getImageUrl();
				$response[$i]['url'] = $_product->getProductUrl();
				
				$i++;
			}
			$data['data']= $response;
			$data['event_start'] = strtotime($rowsArray['event_start']);
			$data['event_end'] = strtotime($rowsArray['event_end']);
			$data['status'] = 1;
			$data['msg'] = '';
		}
		else
		{
			$data['msg'] = 'No products found';
			$data['status'] = 0;
		}
		
		echo json_encode($data);
	}
	
	/**
	* Product Detail 
 	*/
	public function productDetailAction()
	{
		$response = array();
		$pid = $this->getRequest()->getPost('pid');
		
		$_product = Mage::getModel('catalog/product')->load($pid);
		
		$response[0]['id'] = $_product->getId();
		$response[0]['name'] = $_product->getName();
		$response[0]['short_description'] = $_product->getShortDescription();
		$response[0]['long_description'] = $_product->getDescription();
		$response[0]['price'] = number_format($_product->getPrice(),0,",",".");
		$response[0]['speprice'] = number_format($_product->getSpecialPrice(),0,",",".");
		$response[0]['weight'] = $_product->getWeight();
		$response[0]['qty'] = $_product->getStockItem()->getQty();
		//$response[$i]['img'] = $_product->getImageUrl();
		$response[0]['url'] = $_product->getProductUrl();
		
		$_images = Mage::getModel('catalog/product')->load($_product->getId())->getMediaGalleryImages();
		
		if($_images){
			$j=0; 
			foreach($_images as $_image){ 
				$response[0]['img'][$j] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$_image->getFile();
				$j++;
    		}
		}
		
		$data['data'] = $response;
		$data['msg'] = '';
		$data['status'] = 1;
		
		echo json_encode($response);
	}
	
	/**
	* Home Page Slider 
 	*/
	public function homesliderAction()
	{
		$response = array();
		
		$response[0][0]	= 'http://www.vipplaza.co.id/media/banner/sliding-ARIES-GOLD-mobile.jpg';
		$response[0][1]	= 'http://www.vipplaza.co.id/media/banner/sliding-FRAGANCE-mobile.jpg';
		$response[0][2]	= 'http://www.vipplaza.co.id/media/banner/POLICE-SLIDING-MOBILE.jpg';
		$response[0][3]	= 'http://www.vipplaza.co.id/media/banner/ADIKUSUMA-sliding-mobile.jpg';
		
		echo json_encode($response);
		exit;
	}
	
	/**
	* Home Page Events 
 	*/
	public function homeeventsAction()
	{
		require_once './app/Mage.php';
		Mage::app('default');
		
		$connection = Mage::getSingleton('core/resource')->getConnection('core_read');
		
		$curtym = now();
		$select = $connection->select()
			->from('event_list', array('*')) // select * from tablename or use array('id','title') selected values
			->where('event_end>?',$curtym);   // where id =1
			//->group('title');               // group by title
 
		$rowsArray = $connection->fetchAll($select); // return all rows
		
		$response = array();
		if(!empty($rowsArray))
		{
			$i=0;
			foreach($rowsArray as $row)
			{
				$response[$i] = $row;
				$response[$i]['event_logo'] = 'http://www.vipplaza.co.id/media/events/'.$row['event_logo'];
				$response[$i]['event_image'] = 'http://www.vipplaza.co.id/media/events/'.$row['event_image'];
				$response[$i]['event_start'] = strtotime($row['event_start']);
				$response[$i]['event_end'] = strtotime($row['event_end']);
				
				$i++;
			}
			
			$data['status'] = 1;
			$data['msg'] = '';
		}
		else
		{
			$data['msg'] = 'No products found';
			$data['status'] = 0;
		}
		
		$data['data']= $response;
		
		echo json_encode($data);
	}
}