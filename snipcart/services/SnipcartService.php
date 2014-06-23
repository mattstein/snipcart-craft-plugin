<?php

namespace Craft;

class SnipcartService extends BaseApplicationComponent
{

	protected $_snipcartUrl;
	protected $_settings;
	protected $_isLinked;

	/**
	 * Constructor
	 */
	
	public function init()
	{
		parent::init();
		
		$this->_settings    = craft()->plugins->getPlugin('snipcart')->getSettings();
		$this->_snipcartUrl = 'https://app.snipcart.com/api/';
		$this->_isLinked    = isset($this->_settings->apiKey) && ! empty($this->_snipcartUrl);
	}


	// ------------------------------------------------------------------------
	// Public Methods
	// ------------------------------------------------------------------------


	/**
	 * Get an order from Snipcart
	 * 
	 * @param int $orderId Snipcart issue ID
	 * 
	 * @return stdClass
	 */
	
	public function getOrder($token)
	{
		$order = $this->_curlRequest('orders/'.$token);

		return $order;
	}


	/**
	 * List Snipcart orders by a range of dates supplied in $_POST (defaults to 30 days)
	 * 
	 * @param integer $page  page of results
	 * @param integer $limit number of results per page
	 * 
	 * @return stdClass or empty array
	 */
	
	public function listOrders($page = 1, $limit = 250)
	{
		$orders = $this->_curlRequest('orders', array(
			'offset' => $page-1,
			'limit'  => $limit,
			'from'   => date('c', $this->dateRangeStart()),
			'to'     => date('c', $this->dateRangeEnd())
		));

		return ! empty($orders) ? $orders : array();
	}

	/**
	 * List Snipcart customers
	 * 
	 * @param integer $page  page of results
	 * @param integer $limit number of results per page
	 * 
	 * @return stdClass or empty array
	 */

	public function listCustomers($page = 1, $limit = 250)
	{
		$customers = $this->_curlRequest('customers', array(
			'offset' => ($page-1)*$limit,
			'limit'  => $limit
		));

		return ! empty($customers) ? $customers : array();
	}


	/**
	 * List available coupons (not implemented)
	 * 
	 * @return stdClass
	 */

	public function listDiscounts()
	{
		$discounts = $this->_curlRequest('discounts');

		return ! empty($discounts) ? $discounts : array();
	}


	/**
	 * Get a customer from Snipcart
	 * 
	 * @param int $token Snipcart customer ID
	 * 
	 * @return stdClass
	 */
	
	public function getCustomer($token)
	{
		$customer = $this->_curlRequest('customers/'.$token);

		return $customer;
	}


	/**
	 * Get a given customer's order history
	 * 
	 * @param int $token Snipcart customer ID
	 * 
	 * @return stdClass
	 */
	
	public function getCustomerOrders($token)
	{
		$customerOrders = $this->_curlRequest('customers/'.$token.'/orders');

		return $customerOrders;
	}


	/**
	 * Get the Snipcart URL
	 * 
	 * @return stdClass
	 */

	public function snipcartUrl()
	{
		return $this->_snipcartUrl;
	}

	/**
	 * Get the Snipcart URL
	 * 
	 * @return stdClass
	 */

	public function isLinked()
	{
		return $this->_isLinked;
	}

	public function dateRangeStart()
	{
		if (craft()->request->getPost('startDate', FALSE)) {
			$startDate = strtotime(craft()->request->getPost('startDate')['date'])+86400;
		} else {
			$startDate = strtotime("-1 month");
		}

		return $startDate;
	}

	public function dateRangeEnd()
	{
		if (craft()->request->getPost('endDate', FALSE)) {
			$endDate = strtotime(craft()->request->getPost('endDate')['date'])+86400;
		} else {
			$endDate = time();
		}

		return $endDate;
	}


	// ------------------------------------------------------------------------
	// Private Methods
	// ------------------------------------------------------------------------


	/**
	 * Query the snipcart API via cURL
	 * 
	 * @param  string $endpoint Snipcart API method (segment) to query
	 * @param  array  $inData   any data that should be sent with the request; will be formatted as URL parameters or POST data
	 * @param  string $method   'get' (default) or 'post'
	 * 
	 * @return stdClass query response
	 */
	
	private function _curlRequest($endpoint = '', $inData = array(), $method = 'get')
	{
		if ( ! $this->_isLinked) 
			return FALSE;

		$baseUrl = $this->_snipcartUrl;
		$apiUrl  = $baseUrl . $endpoint;
		$isPost  = strtolower($method) != 'get';
		
		$data = array();

		foreach ($inData as $key => $value)
			$data[$key] = $value;

		$ch = curl_init();

		if ( ! empty($data) AND $isPost) { 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			$apiUrl .= '?api_key='.$data['api_key'];	
		} else if ( ! empty($data)) {			
			$apiUrl .= '?';
			$apiUrl .= http_build_query($data);
		}

		curl_setopt($ch, CURLOPT_USERPWD, $this->_settings->apiKey . ":");  
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8","Accept:application/json, text/javascript, */*; q=0.01")); 
		curl_setopt($ch, CURLOPT_URL, $apiUrl);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

		$response = trim(curl_exec($ch));
		curl_close($ch);

		return json_decode($response);
	}

}