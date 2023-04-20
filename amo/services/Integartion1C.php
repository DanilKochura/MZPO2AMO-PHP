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
use MzpoAmo\Log;
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
		if(!$contact)
		{
			$lead->setNoteSave('Не удалось перенести сделку: отсуствует контакт!');
			Log::writeError(Log::LEAD, 'отсуствует контакт! в сделке');
			throw new \Exception('отсуствует контакт в сделке');
		}

		if($client = $contact->getCFValue(CustomFields::CLIENT_1C[$lead->getType()]))
		{
			$lead1c->client_id_1c = $client;
		} else
		{
			$client = Contact1C::fromAmo($contact);
			$uid = $this->request->EditStudent_POST($client);
			$contact->setCFStringValue(CustomFields::CLIENT_1C[$lead->getType()], $uid);
			$contact->save();
			$lead1c->client_id_1c = $uid;
			dd($contact);
		}

		$uid = $this->request->EditApplication_POST($lead1c);
		$lead->setCFStringValue(CustomFields::LEAD1C[$lead->getType()], $uid);
		$lead->newNote('Сделка перенесеная в 1С: '.$uid);
		$lead->save();


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
		try {
			$contact->newNote('UID клиента в 1с: '.$body['client_id_1C']);
			$contact->save();

		} catch (AmoCRMApiErrorResponseException $e)
		{
			Log::writeError(Log::LEAD, $e->getValidationErrors());
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