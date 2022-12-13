<?php

namespace MzpoAmo;

use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;
use Exception;

class Contact extends MzpoAmo
{
	private ContactModel $contact;
	private bool $new = true;
	private string $name;
	private string $phone;
	private string $email;
	private string $surname;
	private string $pipeline;
	private ?int $mergeId = null;

	public function __construct($array)
	{
			parent::__construct();
			$this->phone = $array['phone'] ?: '';
			$this->email = $array['email'] ?: '';
			$this->name = $array['name'] ?: '';
			$this->surname = $array['surname'] ?: '';
			$this->pipeline = $array['pipeline'] ?: PIPELINE;
			$this->contact = $this->findContact() ?: $this->createContact();
			$this->mergeId = $this->checkLeads();
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
		try{
			$filter->setQuery($this->phone);
			$contacts = $this->apiClient->contacts()->get($filter);
			return $contacts->first();
		} catch (Exception $exception)
		{
			if($exception->getCode() != 204)
			{
				die('fatal');
			}
		}
		#endregion

		#region поиск существующего контакта по телефону
		try{
			$filter->setQuery($this->email);
			$contacts = $this->apiClient->contacts()->get($filter);
			return $contacts->first();
		} catch (Exception $exception)
		{
			if($exception->getCode() != 204)
			{
				die('fatal');
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
				Log::write(LOG::ERROR, LOG::CONTACT, $e);
			}
			return null;
		}
	}
}