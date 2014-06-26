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
		$order = $this->_apiRequest('orders/'.$token);

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
		$orders = $this->_apiRequest('orders', array(
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
		$customers = $this->_apiRequest('customers', array(
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
		$discounts = $this->_apiRequest('discounts');

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
		$customer = $this->_apiRequest('customers/'.$token);

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
		$customerOrders = $this->_apiRequest('customers/'.$token.'/orders');

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
	 * Query the Snipcart API via Guzzle
	 * 
	 * @param  string $query    Snipcart API method (segment) to query
	 * @param  array  $inData   any data that should be sent with the request; will be formatted as URL parameters or POST data
	 * 
	 * @return stdClass query response
	 */
	
	private function _apiRequest($query = '', $inData = array())
	{
		if ( ! $this->_isLinked) 
			return FALSE;

		if ( ! empty($inData)) {			
			$query .= '?';
			$query .= http_build_query($inData);
		}
		
		$cachedResponse = craft()->fileCache->get($query);

		if ($cachedResponse) {
			return json_decode($cachedResponse);
		}

		try {
			$client  = new \Guzzle\Http\Client($this->_snipcartUrl);
			$request = $client->get($query, array(), array(
				'auth'    => array($this->_settings->apiKey, 'password', 'Basic'),
				'headers' => array(
					'Content-Type' => 'applicaton/json; charset=utf-8',
					'Accept'       => 'application/json, text/javascript, */*; q=0.01',
				),
				'verify'  => false,
				'debug'   => false
			));

			$response = $request->send();

			if ( ! $response->isSuccessful()) {
				return;
			}

			craft()->fileCache->set($query, $response->getBody(true), 600); // set to expire in 10 minutes

			return json_decode($response->getBody(true));
		} catch(\Exception $e) {
			return;
		}
	}

}