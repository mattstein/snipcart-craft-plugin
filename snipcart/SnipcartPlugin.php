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
		return '0.9.4';
	}

	public function getDeveloper()
	{
		return 'Matt Stein';
	}

	public function getDeveloperUrl()
	{
		return 'http://workingconcept.com';
	}

	public function getDescription()
	{
		return 'Browse Snipcart order details from the Craft control panel.';
	}

	public function getReleaseFeedUrl()
	{
		return 'https://raw.githubusercontent.com/mattstein/snipcart-craft-plugin/master/releases.json';
	}

	public function getDocumentationUrl()
	{
	    return 'https://github.com/mattstein/snipcart-craft-plugin/blob/master/README.md';
	}

	public function hasCpSection()
	{
		return true;
	}

	protected function defineSettings()
	{
		return array(
			'apiKey'             => array(AttributeType::String, 'required' => true),
			'notificationEmails' => array(AttributeType::String, 'required' => true),
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
			'snipcart\/orders\/(?P<pageNumber>\S+)'    => 'snipcart/index',
			'snipcart\/customers\/(?P<pageNumber>\S+)' => 'snipcart/customers',
			'snipcart\/customer\/(?P<customerId>\S+)'  => 'snipcart/customer',
			'snipcart\/discounts\/'                    => 'snipcart/discounts'
		);
	}
}
