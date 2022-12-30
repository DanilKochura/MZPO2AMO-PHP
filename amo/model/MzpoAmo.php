<?php

namespace MzpoAmo;
use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\NoteModel;
use AmoCRM\Models\NoteType\CommonNote;
use AmoCRM\Models\TagModel;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;

require_once $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/config/db.php';
require_once  $_SERVER['DOCUMENT_ROOT'].'/amo/config/helpers.php';

class MzpoAmo
{
	public const  ADMINISTRATOR = 2576764;
	public const  DANIL = 8348113;
 	protected const SUBDOMAIN = 'mzpoeducationsale'; //Поддомен нужного аккаунта
	protected const SECRET = 'KAHuESf38NuVHQ6TxpzaN5eWnbe8TutYO5eo9olYoXAe7xUoYXlHwuYlh4WnFg3R';
	protected const ID = 'a4fbd30b-b5ae-4ebd-91b1-427f58f0d709';
	protected const REDIRECT = 'https://mzpo-s.ru/amo/';


	protected $apiClient;


	public function __construct()
	{

		$this->apiClient = new AmoCRMApiClient($this::ID, $this::SECRET, $this::REDIRECT);

		#region установка и смена токенов
		$accessToken = getToken();
		$this->apiClient->setAccessToken($accessToken)
			->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
			->onAccessTokenRefresh(
				function (\League\OAuth2\Client\Token\AccessTokenInterface $accessToken, string $baseDomain) {
					saveToken(
						[
							'accessToken' => $accessToken->getToken(),
							'refreshToken' => $accessToken->getRefreshToken(),
							'expires' => $accessToken->getExpires(),
							'baseDomain' => $baseDomain,
						]
					);
				});
		#endregion

	}


}