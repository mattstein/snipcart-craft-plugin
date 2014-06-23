<?php

namespace Craft;

class SnipcartController extends BaseController
{
	
	public function actionSaveAccount()
	{
		$this->requirePostRequest();

		if (craft()->snipcart->saveAccount()) {
			craft()->userSession->setNotice(Craft::t('Account settings saved.'));
			$this->redirectToPostedUrl();
		}
	}

}
