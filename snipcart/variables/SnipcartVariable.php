<?php

namespace Craft;

class SnipcartVariable
{
	
	public function listOrders($pageNumber = 1)
	{		
		return craft()->snipcart->listOrders($pageNumber);
	}
	
	public function listCustomers($pageNumber = 1)
	{		
		return craft()->snipcart->listCustomers($pageNumber);
	}
	
	public function listDiscounts()
	{		
		return craft()->snipcart->listDiscounts();
	}
	
	public function getOrder($orderId)
	{
		return craft()->snipcart->getOrder($orderId);
	}

	public function getCustomer($customerId)
	{
		return craft()->snipcart->getCustomer($customerId);
	}
	
	public function getCustomerOrders($customerId)
	{
		return craft()->snipcart->getCustomerOrders($customerId);
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
