<?php

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
use AmoCRM\Models\NoteType\CommonNote;
use AmoCRM\Models\TagModel;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;

require_once '../vendor/autoload.php';
require_once 'config/db.php';
require_once 'config/tokenActions.php';
$post = [
	'phone' => '+74985454234',
	'name' => 'Данил',
	'surname' => 'Кочура',
	'email' => 'kochura2017@yandex.ru',
	'form_name_site' => 'Заявка с какой-то там формы',
	'site' => 'mzpo-s.ru',
	'course' => 'Курсы чего-то там',
	'comment' => 'Курсы чего-то там',
];
$subdomain = 'mzpoeducationsale'; //Поддомен нужного аккаунта
$clientSecret = 'KAHuESf38NuVHQ6TxpzaN5eWnbe8TutYO5eo9olYoXAe7xUoYXlHwuYlh4WnFg3R';
$clientId = 'a4fbd30b-b5ae-4ebd-91b1-427f58f0d709';
$redirectUri = 'https://mzpo-s.ru/amo/';
/** Соберем данные для запроса */
$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);
//var_dump($apiClient); exit;
$accessToken = getToken();
$apiClient->setAccessToken($accessToken)
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
if($post)
{
	$lead = (new LeadModel())
		->setName('Новая заявка с сайта '.$post['site'])
		->setPipelineId(PIPELINE)
		->setTags((new TagsCollection())
			->add(
				(new TagModel())
					->setName($post['site'])
			)->add(
				(new TagModel())
					->setName('test')
			))
		->setCustomFieldsValues(
			(new CustomFieldsValuesCollection())
				->add(
					(new TextCustomFieldValuesModel())
						->setFieldId(TYPE)
						->setValues(
							(new TextCustomFieldValueCollection())
								->add(
									(new TextCustomFieldValueModel())
										->setValue($post['form_name_site'])
								)
						)
				)
//				->add(
//					(new TextCustomFieldValuesModel())
//						->setFieldCode(639075)
//						->setValues(
//							(new TextCustomFieldValueCollection())
//								->add(
//									(new TextCustomFieldValueModel())
//										->setValue('a')
//								)
//						)
//				)

		);
		$contact = (new ContactModel())
		->setFirstName($post['name'])
		->setLastName($post['surname'])
		->setCustomFieldsValues(
			(new CustomFieldsValuesCollection())
				->add(
					(new MultitextCustomFieldValuesModel())
						->setFieldCode('PHONE')
						->setValues(
							(new MultitextCustomFieldValueCollection())
								->add(
									(new MultitextCustomFieldValueModel())
										->setValue($post['phone'])
								)
						)
				)
				->add(
					(new MultitextCustomFieldValuesModel())
						->setFieldCode('EMAIL')
						->setValues(
							(new MultitextCustomFieldValueCollection())
								->add(
									(new MultitextCustomFieldValueModel())
										->setValue($post['email'])
								)
						)
				)

		);

	try {
		$apiClient->contacts()->addOne($contact);
	} catch (AmoCRMException $e)
	{
		die($e->getValidationErrors());
	}
	$newLead = $apiClient->leads()->addOne( $lead );
	$id = $newLead->getId();
	$links = new LinksCollection();
	$links->add($newLead);
	try {
		$apiClient->contacts()->link($contact, $links);
	} catch (AmoCRMApiException $e) {
		printError($e);
		die;
	}
	$leadNotesService = $apiClient->notes(EntityTypesInterface::LEADS);
	$serviceMessageNote = new CommonNote();
	$serviceMessageNote->setEntityId(1)
		->setText($post['comment'])
		->setEntityId($id);
	try {
		$notesCollection = $leadNotesService->addOne($serviceMessageNote);
	} catch (AmoCRMApiException $e) {
		PrintError($e);
		die;
	}
}

