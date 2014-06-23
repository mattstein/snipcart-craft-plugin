<?php

namespace Craft;

class SnipcartVariable
{
	
	public function listOrders()
	{		
		return craft()->snipcart->listOrders();
	}
	
	public function listCustomers($pageId = 1)
	{		
		return craft()->snipcart->listCustomers($pageId);
	}
	
	public function listDiscounts()
	{		
		return craft()->snipcart->listDiscounts();
	}
	
	public function getOrder($token)
	{
		return craft()->snipcart->getOrder($token);
	}

	public function getCustomer($token)
	{
		return craft()->snipcart->getCustomer($token);
	}
	
	public function getCustomerOrders($token)
	{
		return craft()->snipcart->getCustomerOrders($token);
	}
	
	public function snipcartUrl()
	{
		return craft()->snipcart->snipcartUrl();
	}

	public function startDate()
	{
		return DateTime::createFromString(craft()->snipcart->dateRangeStart());
	}
	
	public function endDate()
	{
		return DateTime::createFromString(craft()->snipcart->dateRangeEnd());
	}
	
	public function isLinked()
	{
		return craft()->snipcart->isLinked();
	}
	
}
