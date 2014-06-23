<?php

namespace Craft;

class SnipcartPlugin extends BasePlugin
{
	public function getName()
	{
		return Craft::t('Snipcart');
	}

	public function getVersion()
	{
		return '0.9';
	}

	public function getDeveloper()
	{
		return 'Matt Stein';
	}

	public function getDeveloperUrl()
	{
		return 'http://workingconcept.com';
	}
	
	public function hasCpSection()
	{
		return true;
	}

	protected function defineSettings()
	{
		return array(
			'apiKey' => array(AttributeType::String, 'required' => true),
		);
	}
	
	public function getSettingsHtml()
	{
		return craft()->templates->render('snipcart/_settings', array(
			'settings' => $this->getSettings()
		));
	}
	
	public function registerCpRoutes()
	{
		return array(
			'snipcart\/order\/(?P<orderId>\S+)'        => 'snipcart/order',
			'snipcart\/customers\/(?P<pageNumber>\S+)' => 'snipcart/customers',
			'snipcart\/customer\/(?P<customerId>\S+)'  => 'snipcart/customer',
			'snipcart\/discounts\/'                    => 'snipcart/discounts'
		);
	}
}
