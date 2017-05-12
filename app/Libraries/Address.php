<?php 
namespace App\Libraries;

class Address {

	/**
	 * Country of the address
	 *
	 * @var string
	 */
	public $country;


	/**
	 * Shipping name
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Street Address of the address
	 *
	 * @var string
	 */
	public $address1;

	/**
	 * Second line of street Address of the address
	 *
	 * @var string
	 */
	public $address2;

	/**
	 * City of the address
	 *
	 * @var string
	 */
	public $city;

	/**
	 * State or Province of the address
	 *
	 * @var string
	 */
	public $state;

	/**
	 * Zip code of the address
	 */
	public $zipcode;

	public function __construct($param) {
		$this->address1 = $this->sanitize($param[2]);
		$this->address2 = $this->sanitize($param[3]);
		$this->city		= $this->sanitize($param[4]);
		$this->country	= $this->sanitize($param[0]);
		$this->name		= $this->sanitize($param[1]);
		$this->state	= $this->sanitize($param[5]);
		$this->zipcode	= $this->sanitize($param[6]);
	}
	
	protected function sanitize($str) {
		return strtoupper(preg_replace("/[^a-z0-9\ ]+/i", "", $str));
	}
}