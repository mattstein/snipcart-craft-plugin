<?php

namespace Craft;

class Snipcart_WebhooksController extends BaseController
{
	protected $allowAnonymous = true;

	public function actionHandle() 
	{
		$this->requirePostRequest();

		$json = craft()->request->getRawBody();
		$body = json_decode($json);

		if (is_null($body) or !isset($body->eventName)) {
			$this->returnBadRequest(array('reason' => 'NULL response body or missing eventName.'));
			return;
		}

		switch ($body->eventName)
		{
			case 'order.completed':
				$this->_processOrderCompletedEvent($body);
				break;
			default:
				$this->_returnBadRequest(array('reason' => 'Unsupported event'));
				break;
		}
	}

	private function _returnBadRequest($errors = array())
	{
		header('HTTP/1.1 400 Bad Request');
		$this->returnJson(array('success' => false, 'errors' => $errors));
	}

	private function _processOrderCompletedEvent($data)
	{
		$content = $data->content;
		$updated = array();

		/**
		 * TODO: add web hook options and email templates to control panel
		 */

		$email = new EmailModel();
		$email->toEmail = 'matt@workingconcept.com';
		$email->subject = $content->cardHolderName.' just placed an order';

		$body = $content->cardHolderName." ({$content->email}) just placed order {$content->invoiceNumber} for $".$content->finalGrandTotal.":<br>\n";

		foreach($content->items as $item)
		{
			$body .= "- ".$item->quantity." ".$item->name." @ $".$item->price;

			if (sizeof($item->customFields) > 0)
			{
				$customFields = "";

				foreach($item->customFields as $field)
				{
					$customFields .= $field->name.": ".$field->value.", ";
				}

				$customFields = substr($customFields, 0, -2);

				$body .= " ($customFields)";
			}
		}

		$body .= "<br>\n\n";

		if ($content->shippingAddressSameAsBilling)
		{
			$body .= "### Billing + Shipping Address\n";

			$body .= "{$content->billingAddressName}<br>";
			$body .= "{$content->billingAddressAddress1}<br>";
			if ( ! empty($content->billingAddressAddress2)) { $body .= "{$content->billingAddressAddress2}<br>"; }
			$body .= "{$content->billingAddressCity}, {$content->billingAddressProvince} {$content->billingAddressPostalCode}<br>";
			$body .= "{$content->billingAddressCountry}<br><br>";
			$body .= "{$content->billingAddressPhone}<br>";
		}
		else
		{
			$body .= "### Billing Address\n";

			$body .= "{$content->billingAddressName}<br>";
			$body .= "{$content->billingAddressAddress1}<br>";
			if ( ! empty($content->billingAddressAddress2)) { $body .= "{$content->billingAddressAddress2}<br>"; }
			$body .= "{$content->billingAddressCity}, {$content->billingAddressProvince} {$content->billingAddressPostalCode}<br>";
			$body .= "{$content->billingAddressCountry}<br><br>";
			$body .= "{$content->billingAddressPhone}<br>";

			$body .= "### Shipping Address\n";

			$body .= "{$content->shippingAddressName}<br>";
			$body .= "{$content->shippingAddressAddress1}<br>";
			if ( ! empty($content->shippingAddressAddress2)) { $body .= "{$content->shippingAddressAddress2}<br>"; }
			$body .= "{$content->shippingAddressCity}, {$content->shippingAddressProvince} {$content->shippingAddressPostalCode}<br>";
			$body .= "{$content->shippingAddressCountry}<br><br>";
			$body .= "{$content->shippingAddressPhone}<br>";
		}

		$body .= "<br><br>\n\n";

		$body .= "### Shipping Method: {$content->shippingMethod} ($".$content->shippingFees.")<br>";

		$email->body = $body;

		try
		{
			craft()->email->sendEmail($email);
			$this->returnJson(array('success' => true));
		}
		catch (ErrorException $e)
		{
			$this->returnJson(array('success' => false, 'errors' => $e));
		}
	}

}