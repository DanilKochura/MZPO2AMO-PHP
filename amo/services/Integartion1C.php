<?php

namespace services;

use AmoCRM\Exceptions\AmoCRMApiErrorResponseException;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMMissedTokenException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Exceptions\InvalidArgumentException;
use AmoCRM\Models\BaseApiModel;
use AmoCRM\Models\LeadModel;
use Exception;
use MzpoAmo\Contact;
use MzpoAmo\Contact1C;
use MzpoAmo\CustomFields;
use MzpoAmo\Lead1C;
use MzpoAmo\Leads;
use MzpoAmo\MzpoAmo;

/**
 * @method EditStudent_POST(Contact1C $client)
 */
class Integartion1C
{

	public Request1C $request;

	public function __construct()
	{
		$this->request = new Request1C();
	}

	public function sendLead(Leads $lead)
	{
		$lead1c = Lead1C::fromAMO($lead);

		$contact = new Contact([], $lead->getSubdomain(), $lead->getContact());

		if($client = $contact->getCFValue(CustomFields::CLIENT_1C[$lead->getType()]))
		{
			$lead1c->client_id_1c = $client;
		} else
		{
			$client = Contact1C::fromAmo($contact);
			$this->EditStudent_POST($client);
		}


	}

	private function updateLead($lead)
	{
		//TODO: Обнеовление лида по id
		return true;
	}


	private function createLead($lead)
	{

		return true;
	}


	/**
	 * Функция синронизации контакта с 1с (отправка)
	 * @param Contact $contact
	 * @return mixed
	 * @throws AmoCRMApiException
	 * @throws AmoCRMMissedTokenException
	 * @throws AmoCRMoAuthApiException
	 * @throws InvalidArgumentException
	 */
	public function sendContact(Contact $contact)
	{
		#region Заполнение JSON-объекта
		$json=[];

		if($json['dob'])
		{
			$json['dob'] = $json['dob']->toDateTimeString();
		}
		#endregion

		#region Отправка
		try {
			$r = $this->request->request('POST', 'EditStudent' , json_encode($json));
			$body = json_decode($r->getBody(), 1);
			$contact->setCFStringValue(CustomFields::CLIENT_1C[0], $body['client_id_1C']);
			$contact->save();
		} catch(Exception $e)
		{
			dd($e);
		}
		#endregion

		#region Сохранение
		$contact->newNote('UID клиента в 1с: '.$body['client_id_1C']);
		try {
			$contact->save();

		} catch (AmoCRMApiErrorResponseException $e)
		{
			dd($contact);
			dd($e->getValidationErrors());
		}
		#endregion

		return $body['client_id_1C'];
	}

	public function getContact($uid)
	{
		$resp = $this->request->request('GET', 'EditStudent?uid='.$uid);
		return Contact1C::from1C(json_decode($resp->getBody(), true));
	}






}