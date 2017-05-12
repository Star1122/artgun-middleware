<?php 
namespace App\Libraries;

class Artgun {
	/**
	 * Endpoint for the ArtGun api
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Apikey for the ArtGun api
	 *
	 * @var string
	 */
	private $apikey;

	/**
	 * Secret for the ArtGun api
	 *
	 * @var string
	 */
	private $secret;

	/**
	 * The shipping provider. e.g. UPS, DHL, etc.
	 *
	 * @var string
	 */
	private $shipping_carrier;

	/**
	 * The shipping priority. e.g. Express, 2Day, etc.
	 *
	 * @var string
	 */
	private $shipping_priority;

	/**
	 * Account number associated with your company's shipping provider.
	 *
	 * @var string
	 */
	private $shipping_account;

	/**
	 * Controls if the api is operating in production or debug mode.
	 *
	 * @var string
	 */
	public $mode;

	public function __construct($config) {

		$this->url					= $config['URL'];
		$this->apikey				= $config['APIKEY'];
		$this->secret				= $config['SECRET'];
		$this->shipping_account		= $config['SHIPPING_ACCOUNT'];
		$this->shipping_carrier		= $config['SHIPPING_CARRIER'];
		$this->shipping_priority 	= $config['SHIPPING_PRIORITY'];
		if ($config['MODE'] == 'auto') $this->mode = $config['MODE'];
		else $this->mode = 'debug';
	}


	public function sendOrder($order, $phoneNumber, $email = '') {
		$data = $this->getBaseData($order->xid);
		// status 'debug' or 'in production'
		$data['status']					= 'In Production';
		// 6 for sending order
		$data['status_code']			= '6';
		$data['method']					= 'create';
		// shipping info
		$data['shipping_carrier']		= $this->shipping_carrier;
		$data['shipping_priority']		= $this->shipping_priority;
		$data['shipping_account']		= $this->shipping_account;
		// shipping address info
		if (!empty($order->shipping_address)) {
			$data['shipping_name']		= $order->shipping_address->name;
			$data['shipping_state']		= $order->shipping_address->state;
			$data['shipping_city']		= $order->shipping_address->city;
			$data['shipping_country']	= $order->shipping_address->country;
			$data['shipping_address1'] 	= $order->shipping_address->address1;
			$data['shipping_address2'] 	= $order->shipping_address->address2;
			$data['shipping_Zipcode']	= $order->shipping_address->zipcode;
		}
		// billing info
		if (!empty($order->billing_address)) {
			$data['billing_name'] 		= $order->billing_address->name;
			$data['billing_address1'] 	= $order->billing_address->address1;
			$data['billing_address2'] 	= $order->billing_address->address2;
			$data['billing_city'] 		= $order->billing_address->city;
			$data['billing_state'] 		= $order->billing_address->state;
			$data['billing_country'] 	= $order->billing_address->country;
			$data['billing_Zipcode'] 	= $order->billing_address->zipcode;
		}
		$data['items_quantity']			= $order->items_quantity;
		$data['items']					= $order->items;

		// may be temporary
		$data['shipping_phone'] = $phoneNumber;
		$data['shipping_email'] = $email;

		return $this->call($data);
		//return $data;
	}
	
	public function getStatus($xid) {
		$timestamp = time();
		$url = $this->url . "GetOrderStatus?xid=" . $xid . "&timestamp=" . $timestamp;

		$fields = array(
			'apikey :'.$this->apikey,
			'signature :'.sha1( $this->apikey . $timestamp . $this->secret )
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $fields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);

		return json_decode($response);
	}

	public function cancelOrder($xid) {
		$data = $this->getBaseData($xid);

		$event['name'] = "cancel_order";
		$event['description'] =  "cancel order request";
		$data['event'] = $event;
		$data['method'] = 'Cancelled';
		
		return $this->call($data);
	}
	
	protected function getBaseData($xid) {
		$data = array();
		$data['type'] = 'ORDER';
		$data['xid']	= $xid;
		$data['mode'] = $this->mode;
		$data['time'] = date_format(date_create(), DATE_RFC822);
		return $data;
	}

	public function call($data) {
		$url = $this->url;
		if($data['method'] == 'create')
			$url .= "create/";
		else if($data['method'] == 'Cancelled')
			$url .= "cancelorder/";

		$jsonData = json_encode($data);
		
		$fields = array(
			'key'		=> $this->apikey,
			'data'		=> $jsonData,
			'signature' => $this->getSignature($jsonData)
		);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, count($fields));
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);

		return json_decode($response);
	}

	public function getSignature($jsonData) {
		return sha1($this->secret . $this->apikey . $jsonData);
	}

	public function keyMatches($key) {
		return $key == $this->apikey;
	}
}