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


	#region responsible users
	public const  ADMINISTRATOR = 2576764;
	public const  DANIL = 8348113;
	#endregion
	protected string $type;
	protected int $int_type;
	#region retail
 	public const SUBDOMAIN = 'mzpoeducationsale'; //Поддомен нужного аккаунта
	protected const SECRET = 'KAHuESf38NuVHQ6TxpzaN5eWnbe8TutYO5eo9olYoXAe7xUoYXlHwuYlh4WnFg3R';
	protected const ID = 'a4fbd30b-b5ae-4ebd-91b1-427f58f0d709';
	protected const REDIRECT = 'https://mzpo-s.ru/amo/';
	#endregion

	#region corp
	public const SUBDOMAIN_CORP = 'mzpoeducation'; //Поддомен нужного аккаунта
	protected const SECRET_CORP = 'PpfKPVKEoND3MBHh7fjLSQIcYBNaZetCmVkUXU9VMLI02ynoGJl1SJ4e4YStYust';
	protected const ID_CORP = 'e48269b8-aca1-4ebd-8809-420d71f57522';
	protected const REDIRECT_CORP = 'https://mzpo-s.ru/amo/mainhook.php';

	#endregion

	public AmoCRMApiClient $apiClient;
	public const ACCOUNTS_IDS = [
		self::SUBDOMAIN => 28395871,
		self::SUBDOMAIN_CORP => 28395871
	];

	public function __construct($type = self::SUBDOMAIN)
	{
		if($type == self::SUBDOMAIN)
		{
			$this->apiClient = new AmoCRMApiClient($this::ID, $this::SECRET, $this::REDIRECT);
		}
		else{
			$this->apiClient = new AmoCRMApiClient($this::ID_CORP, self::SECRET_CORP, self::REDIRECT_CORP);
		}
		$this->type = $type;
		$this->int_type = $type == self::SUBDOMAIN ? 0 : 1;

		#region установка и смена токенов
		$accessToken = getToken($type);
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

	public function getAccountId(): int
	{
		return self::ACCOUNTS_IDS[$this->type];
	}


	public function getType(): int
	{
		return $this->int_type;
	}

	public function getSubdomain()
	{
		return$this->type;
	}

}