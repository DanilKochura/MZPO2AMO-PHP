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
use MzpoAmo\Company;
use MzpoAmo\Company1C;
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
			Log::writeError(Log::C1, 'отсуствует контакт! в сделке');
			throw new \Exception('отсуствует контакт в сделке');
		}


		if($client = $contact->getCFValue(CustomFields::CLIENT_1C[$lead->getType()]))
		{
			$lead1c->client_id_1C = $client;
		} else
		{
			$client = Contact1C::fromAmo($contact);
//			Log::write(Log::C1, $client);

			$uid = $this->request->EditStudent_POST($client);
			$contact->setCFStringValue(CustomFields::CLIENT_1C[$lead->getType()], $uid->client_id_1C);
			$contact->save();
			$lead1c->client_id_1C = $uid->client_id_1C;
//			Log::writeLine(Log::C1, 'here');

		}
		if($c = $lead->getCompany())
		{
			$company = new Company($c);
			$lead1c->is_corporate = true;
			if($contragent = $company->getCFValue(CustomFields::COMPANY_ID_1C[$lead->getType()]))
			{
				$lead1c->company_id_1C = $contragent;
			} else
			{
				$contragent = Company1C::fromAmo($company);
				try {
						$uid = $this->request->EditPartner_POST($contragent);
						$company->setCFStringValue(CustomFields::COMPANY_ID_1C[$lead->getType()], $uid->company_id_1C);
						$company->save();
						$lead1c->company_id_1C = $uid->company_id_1C;
				} catch (Exception $e)
				{
					Log::writeError(Log::C1, $e);
					$lead->newNote("Не удалось перенести компанию в 1С");
				}

			}
		}
		else
		{
			$lead1c->company_id_1C = $lead1c->client_id_1C;
			$lead1c->is_corporate = false;
		}


//		$lead1c->product_id_1C = 'ac84a29c-a7f3-11eb-8921-20040ffb909d';


		try {
			$uid = $this->request->EditApplication_POST($lead1c);
			$lead->setCFStringValue(CustomFields::LEAD1C[$lead->getType()], $uid->lead_id_1C);
			$lead->newNote('Сделка перенесена в 1С: '.$uid->lead_id_1C);
		} catch (Exception $e)
		{
			Log::writeError(Log::C1, $e);
			$lead->newNote("Не удалось перенести сделку в 1С");
		}
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
        $client = Contact1C::fromAmo($contact);
//			Log::write(Log::C1, $client);

        $uid = $this->request->EditStudent_POST($client);
        $contact->setCFStringValue(CustomFields::CLIENT_1C[$contact->getType()], $uid->client_id_1C);
        $contact->save();


		return $uid->client_id_1C;
	}

	public function getContact($uid)
	{
		$resp = $this->request->request('GET', 'EditStudent?uid='.$uid);
		return Contact1C::from1C(json_decode($resp->getBody(), true));
	}






}