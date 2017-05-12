<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Libraries\Address;
use App\Libraries\Artgun;
use App\Libraries\Item;
use App\Libraries\Order;

class ManageController extends Controller
{
    public $artGunConfig;
	public function __construct() {
		$this->artGunConfig = array(
			'URL'				=> 'apptest.tscmiami.com/api/order/',
			'APIKEY'			=> '31e154364e4148a59ec02ac0189ac42d',
			'SECRET'			=> 'ca4d70fbe7264a65990511b8985bbf1a',
			'SHIPPING_CARRIER'	=> 'MI',
			'SHIPPING_PRIORITY' => '4272',
			'SHIPPING_ACCOUNT'	=> '',
			'MODE'				=> 'debug'
		);
	}

	public function sendOrder(Request $request)
	{
		$post = $request->all();
		//print_r($post);exit;
		$params[] = $post['order_id'];
		$order = new Order($params);
		//$this->load->library('order', $params);
			
		//echo json_encode($post);
		$params=[$post['shipping']['country_code'],$post['shipping']['first_name'].$post['shipping']['last_name'],$post['shipping']['address1'],$post['shipping']['address2'],$post['shipping']['city'],$post['shipping']['state_code'],$post['shipping']['zip_code']];
		//$this->load->library('address', $params);
		$address = new Address($params);
		$order->setShippingAddress($address);
		
		for( $i = 0 ; $i <count($post['order_items']); $i++ )
		{
			$params = array($post['order_items'][$i]['product_id'], $post['order_items'][$i]['quantity'], $post['order_items'][$i]['name']);
			//$this->load->library('item', $params);
			$item = new Item($params);
			$item->addAttribute(
				$post['order_items'][$i]['attachment']['thumbnail'],
				$post['order_items'][$i]['attachment']['preview'],
				$post['order_items'][$i]['attachment']['fileUrl'],
				$post['order_items'][$i]['attachment']['fileExtension']
			);
			
			$order->addItem($item);
		}

		//$this->load->library('artgun', $this->artGunConfig);
		$artgun = new Artgun($this->artGunConfig);
		$response = $artgun->sendOrder($order, $post['shipping']['phone_number'], $post['shipping']['email']);

		$returnValue= [];
		$returnValue['status'] = "received";
		$returnValue['orderid'] = $response->xid;
		if($response->res == "error")
		{
			$returnValue['status'] = $response->res;
			$returnValue['message'] = $response->message;
		}

		echo json_encode($returnValue);
	}

	public function getStatus($xid)
	{
		$artgun= new Artgun($this->artGunConfig);
		$response = $artgun->getStatus($xid);
		$returnValue= [];
		$returnValue['status'] = 'error';
		http_response_code(400);
		$returnValue['orderid'] = $response->xid;
		if(isset($response->receipt_id))
		{
			http_response_code(200);
			if($response->events->received->occurred)
				$returnValue['status'] = 'received';
			if($response->events->committed->occurred)
				$returnValue['status'] = 'committed';
			if($response->events->onhold->occurred)
				$returnValue['status'] = 'onhold';
			if($response->events->released->occurred)
				$returnValue['status'] = 'released';
			if($response->events->shipped->occurred)
				$returnValue['status'] = 'shipped';
			if($response->events->cancelled->occurred)
				$returnValue['status'] = 'cancelled';
		}
		else
			$returnValue['message'] = $response->message;
		echo json_encode($returnValue);
	}
	public function getPrice()
	{
		// echo 1;exit;
		return json_encode($this->_import_csv());
	}

	private function _import_csv()
    {
        $csv_file = url('/').'/assets/data.csv';
        if (($handle = fopen($csv_file, "r")) !== FALSE) {
            fgetcsv($handle);   
            $index = 0;
            $result = array();
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) 
            {
                if($data[0] == "" || count($data) != 9) continue;

                $result[$index]['sku'] = $data[1];
                $result[$index]['category'] = '';
                $result[$index]['name'] = $data[2];
                $result[$index]['price'] = $data[8];
                $index++;
            }
            return $result;
            // return $csv_file;
            fclose($handle);
        }
        else
        {
            return false;
        }
    }
}
