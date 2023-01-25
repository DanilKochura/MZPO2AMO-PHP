<?php

namespace MzpoAmo;

use AmoCRM\Collections\ContactsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Exceptions\AmoCRMApiErrorResponseException;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;
use Exception;

class Contact extends MzpoAmo
{
	private ContactModel $contact;
	private bool $new = true;
	public string $name;
	public string $phone;
	public string $email;
	public string $surname;
	private string $pipeline;
	private ?int $mergeId = null;

	public function __construct($array, $amo, $id = null)
	{
			parent::__construct($amo);
			if(!$id)
			{
				$this->phone = $array['phone'] ?: '';
				$this->email = $array['email'] ?: '';
				$this->name = $array['name'] ?: '';
				$this->surname = $array['surname'] ?: '';
				$this->pipeline = $array['pipeline'];

				$this->contact = $this->findContact() ?: $this->createContact();

				$this->mergeId = $this->checkLeads();

			} else
			{

				if(is_int($id) or is_string($id))
				{
					$this->contact = $this->apiClient->contacts()->getOne($id);
				} else
				{
					$this->contact = $id;
				}
				Log::writeLine(Log::CONTACT, 'Сделка с существующим контактом '.$this->contact->getId().'-'.$this->type);
				$this->name = $this->contact->getName();
				$this->surname = $this->contact->getLastName();
				$fields = $this->contact->getCustomFieldsValues();
				try {
					$phoneField = $fields->getBy('fieldCode','PHONE');
					if(!$phoneField)
					{
						throw new Exception();
					}
					$this->phone = $phoneField->getValues()->first()->getValue();
				} catch (Exception $e)
				{
					$this->phone = '';
				}
				try {
					$emailField = $fields->getBy('fieldCode','EMAIL');
					if(!$emailField)
					{
						throw new Exception();
					}
					$this->email = $emailField->getValues()->first()->getValue();
				} catch (Exception $e)
				{
					$this->email = '';
				}

			}

	}

	/**
	 *  Получение модели контакта
	 * @return ContactModel
	 */
	public function getContact() : ContactModel
	{
		return $this->contact;
	}

	/**
	 *  Проверка на существование контакта
	 * @return ContactModel|null
	 */
	public function findContact() : ?ContactModel
	{
		$filter = new ContactsFilter();

		#region поиск существующего контакта по телефону
		if($this->phone and strlen($this->phone) > 5 )
		{
			try{
				$filter->setQuery($this->phone);
				$contacts = $this->apiClient->contacts()->get($filter);
				Log::writeLine(Log::CONTACT, 'Сделка с существующим контактом '.$contacts->first()->getId().'-'.$this->type);
				return $contacts->first();
			} catch (Exception $exception)
			{

				if($exception->getCode() != 204)
				{
					Log::writeError(Log::CONTACT, print_r($exception));
					die('fatal');
				}
			}
		}
		#endregion

		#region поиск существующего контакта по email
		if($this->email)
		{
			try{
				$filter->setQuery($this->email);
				$contacts = $this->apiClient->contacts()->get($filter);
				Log::writeLine(Log::CONTACT, 'Сделка с существующим контактом '.$contacts->first()->getId().'-'.$this->type);

				return $contacts->first();
			} catch (Exception $exception)
			{
				if($exception->getCode() != 204)
				{
					Log::writeError(Log::CONTACT, $exception);

					die('fatal');
				}
			}
		}
		#endregion

		return null;

	}

	/**
	 * Создание нового контакта в амо
	 * @return ContactModel
	 * @throws \AmoCRM\Exceptions\AmoCRMApiException
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
	 */
	public function createContact() : ContactModel
	{
		#region создание модели контакта
		$contact = new ContactModel();
		if($this->name)
		{
			$contact->setFirstName($this->name);
		}
		if($this->surname)
		{
			$contact->setLastName($this->surname);
		}
		if($this->phone or $this->email)
		{
			$fields = new CustomFieldsValuesCollection();
			if($this->phone)
			{
				$fields->add(
					(new MultitextCustomFieldValuesModel())
						->setFieldCode('PHONE')
						->setValues(
							(new MultitextCustomFieldValueCollection())
								->add(
									(new MultitextCustomFieldValueModel())
										->setValue($this->phone)
								)
						)
				);
			}
			if($this->email)
			{
				$fields->add(
					(new MultitextCustomFieldValuesModel())
						->setFieldCode('EMAIL')
						->setValues(
							(new MultitextCustomFieldValueCollection())
								->add(
									(new MultitextCustomFieldValueModel())
										->setValue($this->email)
								)
						)
				);
			}

			$contact->setCustomFieldsValues($fields);
		}
		#endregion

		#region сохранение
		try {
			$contact = $this->apiClient->contacts()->addOne($contact);
		} catch (AmoCRMException $e)
		{
			die($e->getValidationErrors());
		}
		$this->new = false;
		#endregion
		Log::writeLine(Log::CONTACT, 'Создан новый контакт '.$contact->getId().'-'.$this->type);
		return $contact;
	}


	/**
	 * Привязка сделки к контакту
	 * @param LeadModel $lead
	 * @return bool
	 */
	public function linkLead(LeadModel $lead) : bool
	{
		#region создание модели связи
		$links = new LinksCollection();
		$links->add($lead);
		#endregion

		#region сохранение
		try {
			$this->apiClient->contacts()->link($this->contact, $links);
		} catch (AmoCRMApiException $e) {
			printError($e);
			die;
		}
		#endregion

		return true;
	}

	/**
	 * @return bool
	 */
	public function isNew()
	{
		return $this->new;
	}

	/**
	 * @return string|null
	 */
	public function hasMergableLead()
	{
		Log::writeLine(Log::CONTACT, 'Entered');
		return $this->mergeId;
	}

	/**
	 * Поиск заявки для склеивания
	 * @return string|null id
	 */
	public function checkLeads(): ?int
	{
		try{
			$id = null;
			$contacts = $this->apiClient->contacts()->getOne($this->contact->getId(),[ContactModel::LEADS]);
			$leads = $contacts->getLeads();
			if($leads)
			{
				foreach($leads as $item)
				{
					$lead = $this->apiClient->leads()->getOne($item->id);
					if($lead->getStatusId() == 142 or $lead->getStatusId()==143 or $lead->getPipelineId() != $this->pipeline)
					{
						continue;
					}
					$id = $lead->getId();
				}
			}
			return $id;

		} catch (Exception $e)
		{
			$code = $e->getErrorCode();
			if($code == 204){
			}
			else{
				Log::writeError(LOG::CONTACT, $e);
			}
			return null;
		}
	}

	/**
	 * Получение телефона из карточки
	 * @return array|bool|int|object|string|null
	 */
	public function getPhone()
	{
		try {
			$phoneField = $this->contact->getCustomFieldsValues();
			if(!$phoneField)
			{
				throw new Exception();
			}
			if(!$phoneField->getBy('fieldCode','PHONE'))
			{
				throw new Exception();
			}
			return $this->contact->getCustomFieldsValues()->getBy('fieldCode', 'PHONE')->getValues()->first()->getValue();
		} catch (Exception $e)
		{
			return null;
		}
	}

	/**
	 * Установка телефона
	 * @param $phone
	 * @return bool
	 */
	public function setPhone($phone): bool
	{
		try {
			$cfvs = $this->contact->getCustomFieldsValues();
			if($cfvs)
			{
				$cfvs->removeBy('fieldCode', 'PHONE');
			} else
			{
				$cfvs = new CustomFieldsValuesCollection();
			}
			$cfvs
				->add(
					(new TextCustomFieldValuesModel())
						->setFieldCode('PHONE')
						->setValues(
							(new TextCustomFieldValueCollection())
								->add(
									(new TextCustomFieldValueModel())
										->setValue($phone)
								)
						)
				);
			$this->contact->setCustomFieldsValues($cfvs);
			$this->phone = $phone;
			return true;
		}catch (AmoCRMApiException $e)
		{
			return false;
		}
	}

	/**
	 * Сохранение контакта
	 * @return void
	 * @throws AmoCRMApiException
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
	 */
	public function save()
	{
		$this->apiClient->contacts()->updateOne($this->contact);
	}

	/**
	 * Обертка над LeadModel->toArray()
	 * @return array
	 */
	public function toArray()
	{
		return $this->contact->toArray();
	}

	/**
	 * Клонирование контакта в амо корпората с проверкой на дубли
	 * @param Contact $contact
	 * @return Contact
	 * @throws AmoCRMApiException
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
	 */
	public static function clone(Contact $contact)
	{
		$array = [
			 'phone' => $contact->phone,
			 'email' => $contact->email,
		   'name' => $contact->name,
			'pipeline' => Pipelines::NEW
		];
		$contactCorp = new self($array, MzpoAmo::SUBDOMAIN_CORP);
		$contactCorp->setCreatedByCorp();
		$contactCorp->setUpdatedByCorp();
		$contactCorp->contact->setResponsibleUserId(Leads::getCorpResponsible($contact->contact->getResponsibleUserId()));
		$contactCorp->save();
		return $contactCorp;
	}

	/**
	 * Поиск контактов по номеру телефона
	 * @param $phone
	 * @return ContactsCollection|string|void|null
	 */
	public static function findByPhone($phone)
	{
		$mzpoAmo = new MzpoAmo();
		$filter = new ContactsFilter();
		$ret = new ContactsCollection();
		#region поиск существующего контакта по телефону

		try{
				$phone = ltrim($phone, '8');
				$phone = ltrim($phone, '7');
				$phone = ltrim($phone, '+7');
				$filter->setQuery($phone);
				$contacts = $mzpoAmo->apiClient->contacts()->get($filter);
				return $contacts;
			} catch (Exception $exception)
			{
				if($exception->getCode() != 204)
				{
					die('fatal');
				}
			}
		#endregion
			return 'not found';
	}


	public function setCreatedByCorp($id=9081002)
	{
		$this->contact->setCreatedBy($id);
	}

	public function setUpdatedByCorp($id=9081002)
	{
		$this->contact->setUpdatedBy($id);
	}

}