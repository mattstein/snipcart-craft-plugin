<?php

namespace Craft;

class Snipcart_WebhooksController extends BaseController
{
	protected $allowAnonymous = true;
	protected $_settings;


	/**
	 * Handle the $_POST data that Snipcart sent, which is a raw body of JSON
	 * 
	 * @return void
	 */
	
	public function actionHandle() 
	{
		$this->requirePostRequest();
		$this->_settings = craft()->plugins->getPlugin('snipcart')->getSettings();

		$json = craft()->request->getRawBody();
		$body = json_decode($json);

		if (is_null($body) or !isset($body->eventName))
		{
			/*
			 * every Snipcart post should have an eventName property, so we've got empty data or a bad format
			 */

			$this->returnBadRequest(array('reason' => 'NULL response body or missing eventName.'));
			return;
		}
		
		/*
		 * respond to different types of Snipcart events—in this case only one
		 */
		
		switch ($body->eventName)
		{
			case 'order.completed':
				$this->processOrderCompletedEvent($body);
				break;
			default:
				$this->returnBadRequest(array('reason' => 'Unsupported event'));
				break;
		}
	}


	/**
	 * Return a Craft entry whose `productSku` field matches the supplied string
	 * 
	 * @param  string $sku The string we'll use for matching the SKU
	 * @return mixed       Craft entry or FALSE
	 */
	
	private function getMatchingEntryBySku($sku)
	{
		// TODO: make channel/match criteria a plugin setting

		$criteria         = craft()->elements->getCriteria(ElementType::Entry);
		$criteria->search = 'productSku:'.$sku;
		$entries          = $criteria->find();

		if (count($entries) > 0)
		{
			return $entries[0];
		}
		else
		{
			return FALSE;
		}
	}


	/**
	 * Reduce the inventory value for an entry, by the supplied quantity
	 * 
	 * @param  EntryModel $entry    a Craft entry
	 * @param  int        $quantity the value to subtract from the current inventory
	 * @return int                  the new adjusted inventory value
	 */
	
	private function reduceEntryInventory($entry, $quantity)
	{
		$currentValue = $entry->getContent()->productInventory;
		$newValue = 0;

		if ($currentValue > $quantity)
		{
			$newValue = $currentValue - $quantity;
		}

		// TODO: make field a plugin setting

		$entry->getContent()->productInventory = $newValue;
		craft()->entries->saveEntry($entry);

		return $newValue;
	}


	/**
	 * Output a 400 response with an optional JSON error array
	 * 
	 * @param  array  $errors Array of errors that explain the 400 response
	 * @return void
	 */
	
	private function returnBadRequest($errors = array())
	{
		header('HTTP/1.1 400 Bad Request');
		$this->returnJson(array('success' => false, 'errors' => $errors));
	}


	/**
	 * Process the Snipcart completed order event by adjusting quantities and sending a notification email
	 * 
	 * @param  object $data The decoded Snipcart post
	 * @return void
	 */
	
	private function processOrderCompletedEvent($data)
	{
		$content = $data->content;
		$entries = array();

		foreach ($content->items as $item)
		{
			if ($entry = $this->getMatchingEntryBySku($item->id))
			{
				$entries[] = $entry;
				$this->reduceEntryInventory($entry, $item->quantity);
			}
		}

		if (isset($this->_settings->notificationEmails))
		{
			$emailChunks = explode(',', $this->_settings->notificationEmails);
			$emails = array();

			foreach ($emailChunks as $email)
			{
				$email = trim($email);

				if (filter_var($email, FILTER_VALIDATE_EMAIL))
				{
					$emails[] = $email;
				}
			}

			$errors = array();
			$email = new EmailModel();

			foreach ($emails as $address)
			{
				$email->toEmail = $address;
				$email->subject = $content->cardHolderName.' just placed an order';
				$email->body    = craft()->templates->render('snipcart/email', array('order' => $content, 'entries' => $entries));
				
				try
				{
					craft()->email->sendEmail($email);
				}
				catch (ErrorException $e)
				{
					$errors[] = $e;
				}
			}

			if (sizeof($errors) > 0)
			{
				$this->returnJson(array('success' => true));
			}
			else
			{
				$this->returnJson(array('success' => false, 'errors' => $errors));
			}
		}
		else
		{
			$this->returnJson(array('success' => true));
		}
	}
}