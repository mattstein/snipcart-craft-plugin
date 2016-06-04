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
	
	public function getOrder($orderId)
	{
		return $this->_apiRequest('orders/'.$orderId);
	}


	/**
	 * List Snipcart orders by a range of dates supplied in $_POST (defaults to 30 days)
	 * 
	 * @param integer $page  page of results
	 * @param integer $limit number of results per page
	 * 
	 * @return stdClass or empty array
	 */
	
	public function listOrders($page = 1, $limit = 50)
	{
		$orders = $this->_apiRequest('orders', array(
			'offset' => ($page-1)*$limit,
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

	public function listCustomers($page = 1, $limit = 50)
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
	 * @param int $customerId Snipcart customer ID
	 * 
	 * @return stdClass
	 */
	
	public function getCustomer($customerId)
	{
		return $this->_apiRequest('customers/'.$customerId);
	}


	/**
	 * Get a given customer's order history
	 * 
	 * @param int $customerId Snipcart customer ID
	 * 
	 * @return stdClass
	 */
	
	public function getCustomerOrders($customerId)
	{
		return $this->_apiRequest('customers/'.$customerId.'/orders');
	}


	/**
	 * Ask Snipcart whether its provided token is genuine
	 * (We use this for webhook posts to be sure they came from Snipcart)
	 *
	 * Tokens are deleted after this call, so it can only be used once to verify,
	 * and tokens also expire in one hour—expect a 404 if the token is deleted
	 * or if it expires
	 * 
	 * @param string  $token  $_POST['HTTP_X_SNIPCART_REQUESTTOKEN']
	 * 
	 * @return stdClass
	 */

	public function validateToken($token)
	{
		return $this->_apiRequest('requestvalidation/'.$token, null, false);
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
	 * See whether we're linked up to Snipcart
	 * 
	 * @return stdClass
	 */

	public function isLinked()
	{
		return $this->_isLinked;
	}


	public function dateRangeStart()
	{
		$param     = craft()->request->getPost('startDate', FALSE);
		$default   = strtotime("-1 month");
		$stored    = craft()->httpSession->get('snipcartStartDate');
		$startDate = $default;

		if ($param)
			$startDate = strtotime($param['date'])+86400;
		else
			$startDate = $stored ? $stored : $default;

		craft()->httpSession->add('snipcartStartDate', $startDate);

		return $startDate;
	}


	public function dateRangeEnd()
	{
		$param     = craft()->request->getPost('endDate', FALSE);
		$default   = time();
		$stored    = craft()->httpSession->get('snipcartEndDate');
		$endDate = $default;

		if ($param)
			$endDate = strtotime($param['date'])+86400;
		else
			$endDate = $stored ? $stored : $default;

		craft()->httpSession->add('snipcartEndDate', $endDate);

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
	
	private function _apiRequest($query = '', $inData = array(), $useCache = true)
	{
		if ( ! $this->_isLinked) 
			return false;

		if ( ! empty($inData)) 
		{			
			$query .= '?';
			$query .= http_build_query($inData);
		}

		if ($useCache)
		{
			$cachedResponse = craft()->fileCache->get($query);

			if ($cachedResponse)
				return json_decode($cachedResponse);
		}

		try 
		{
			$client  = new \Guzzle\Http\Client($this->_snipcartUrl);
			$request = $client->get($query, array(), array(
				'auth'    => array($this->_settings->apiKey, 'password', 'Basic'),
				'headers' => array(
					'Content-Type' => 'applicaton/json; charset=utf-8',
					'Accept'       => 'application/json, text/javascript, */*; q=0.01',
				),
				'verify' => false,
				'debug'  => false
			));

			$response = $request->send();

			if ( ! $response->isSuccessful())
				return;

			if ($useCache)
				craft()->fileCache->set($query, $response->getBody(true), 600); // set to expire in 10 minutes

			return json_decode($response->getBody(true));
		} 
		catch(\Exception $e) 
		{
			return;
		}
	}

}
