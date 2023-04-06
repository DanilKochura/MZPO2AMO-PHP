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
use MzpoAmo\CustomFields;
use MzpoAmo\Leads;
use MzpoAmo\MzpoAmo;

class Integartion1C extends \MzpoAmo\MzpoAmo
{
	public $client_to_1c = [
		"client_id_1C" => CustomFields::CLIENT_1C[0],
		"name" => "",
		"email" => CustomFields::EMAIL[0],
		"phone" => CustomFields::PHONE[0],
		"second_phone" => CustomFields::PHONE[0],
		"dob" => CustomFields::BIRTHDAY[0],
		"pass_serie" => CustomFields::PASS_SERIE[0],
		"pass_number" => CustomFields::PASS_NUMBER[0],
		"pass_dpt_code" => CustomFields::PASS_CODE[0],
		"snils" => CustomFields::SNILS[0],
		"address" => CustomFields::PASS_ADDRESS[0]
	];

	public Request1C $request;

	public function __construct($type = self::SUBDOMAIN)
	{
		parent::__construct($type);
		$this->request = new Request1C();
	}

	public function syncLead(Leads $lead)
	{
		$id_1c = $lead->getCFValue(CustomFields::LEAD1C);
		if($id_1c != null)
		{
			return $this->updateLead($lead);
		}
		$id = $this->createLead($lead);
		$lead->newNote('UID сделки в 1с: '.$id);
		$lead->setCFStringValue(CustomFields::LEAD1C, $id);
		$lead->save();
		return $id;
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
		foreach ($this->client_to_1c as $key => $id)
		{
			try {
				$json[$key] = $contact->getCFValue($id);
			} catch (Exception $e)
			{
				$json[$key] = '';
			}
		}
		$json["name"] = $contact->getName();
		$json['amo_ids'] = [
			'account_id' =>28395871,
			'entity_id' => $contact->getId()
		];
		$json['dob'] = $json['dob']->toDateTimeString();
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






}