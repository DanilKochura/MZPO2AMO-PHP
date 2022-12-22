<?php

namespace MzpoAmo;

use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\NotesCollection;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFields\DateTimeCustomFieldModel;
use AmoCRM\Models\CustomFieldsValues\DateTimeCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\DateCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\DateTimeCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\NoteModel;
use AmoCRM\Models\NoteType\CommonNote;
use AmoCRM\Models\NoteType\ServiceMessageNote;
use AmoCRM\Models\TagModel;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;
use Exception;
use MzpoAmo\CustomFields;

class Leads extends MzpoAmo
{
	private LeadModel $lead;
	public function __construct($post, $id = null)
	{
		parent::__construct();

		#region если нужно склеить со старой заявкой
		if($id != null)
		{
			Log::writeLine(Log::LEAD, 'Слияние сделки со сделкой '.$id);
			try{
				$this->lead = $this->apiClient->leads()->getOne($id);
			}catch (Exception $e)
			{
				die($e);
			}
		}
		#endregion

		#region если нужно создать новый лид
		else
		{
			$this->lead = $this->newLead($post);
			Log::writeLine(Log::LEAD, 'Добавлена новая сделка '.$this->lead->getId());
		}
		#endregion

	}

	/**
	 * Получение модели заявки
	 * @return LeadModel
	 */
	public function getLead() : LeadModel
	{
		return $this->lead;
	}

	/**
	 * массив связей поле POST - поле заявки AMO
	 * @var array
	 */
	private $post_to_amo = [
		'city_name' => CustomFields::CITY,
		'result' => CustomFields::RESULT,
		'roistat_marker' => CustomFields::ROISTAT_MARKER,
		'site' => CustomFields::SITE,
		'form_name_site' => CustomFields::TYPE,
		'roistat_visit'=> CustomFields::ROISTAT,
		'page_url'=> CustomFields::PAGE,
		'formId' => CustomFields::ID_FORM,
		'event' => CustomFields::EVENT_NAME,
		'clid' => CustomFields::ANALYTIC_ID,
		'_ym_uid' => CustomFields::YM_UID,
	];

	/**
	 * Добавление в амо нового лида
	 * @param $post
	 * @return void
	 */
	public function newLead(array $post) : LeadModel
	{
		$pipeline = $post['pipeline'] ?: PIPELINE;

		#region создание модели лида
		$lead = (new LeadModel());

		$lead->setName('Новая заявка с сайта '.$post['site'])
			->setPipelineId($pipeline)
			->setCustomFieldsValues(
				(new CustomFieldsValuesCollection())
					->add(
						(new TextCustomFieldValuesModel())
							->setFieldId(639075)
							->setValues(
								(new TextCustomFieldValueCollection())
									->add(
										(new TextCustomFieldValueModel())
											->setValue($post['form_name_site'])
									)
							)
					));

		if($post['status'])
		{
			$lead->setStatusId($post['status']);
		}
		$lead->setCustomFieldsValues($this->customLeadFileds($post));
		#endregion

		#region установка тегов
		if(!$post['tags'])
		{
			$post['tags'][0] =$post['site'];
		}
		$tags = new TagsCollection();
		foreach($post['tags'] as $tag)
		{
			$tags->add(
				(new TagModel())
					->setName($tag));
		}
		$lead->setTags($tags);
		#endregion

		#region сохранение
		try {
			$lead = $this->apiClient->leads()->addOne($lead);
		}
		catch (AmoCRMApiException $e) {
			printError($e);
			die;
		}
		#endregion

		return $lead;

	}

	/**
	 *  Создание комментария для сделки (поле comment)
	 * @param $message
	 * @param $lead
	 * @return \AmoCRM\Models\NoteModel|void
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\InvalidArgumentException
	 */
	public function newNote(string $message) : NoteModel
	{
		#region создание модели комментария
		$leadNotesService = $this->apiClient->notes(EntityTypesInterface::LEADS);
		$Note = new CommonNote();
		$Note->setText($message)
			->setEntityId($this->lead->getId());
		#endregion

		#region сохранение
		try {
			$note = $leadNotesService->addOne($Note);
		} catch (AmoCRMApiException $e) {
			PrintError($e);
			die;
		}
		#endregion

		return $note;
	}

	/**
	 * Заполнение стандартных кастомных полей заявки с формы
	 * @param $POST
	 * @return CustomFieldsValuesCollection
	 */
	private function customLeadFileds($POST) : CustomFieldsValuesCollection
	{

		$fileds = new CustomFieldsValuesCollection();
		foreach ($this->post_to_amo as $post => $id)
		{
			if ($POST[$post])
			{
				$fileds->add(
					(new TextCustomFieldValuesModel())
						->setFieldId($id)
						->setValues(
							(new TextCustomFieldValueCollection())
								->add(
									(new TextCustomFieldValueModel())
										->setValue($POST[$post])
								)
						)
				);
			}
		}

		return $fileds;

	}

	/**
	 * Получение значения поля заявки по id
	 * @param $id
	 * @return array|bool|int|object|string|null
	 */
	public function getCFValue($id)
	{
		try {
			$customFields = $this->lead->getCustomFieldsValues();

			//Получим значение поля по его ID
			if (!empty($customFields)) {
				$textField = $customFields->getBy('fieldId', $id);
				if ($textField) {
					return $textField->getValues()->first()->getValue();
				}
			}
		} catch (Exception $e)
		{
			Log::writeError(Log::LEAD, $e);
		}
		return null;
	}

	/**
	 * Заполнение одного поля заявки по его id
	 * @param $id
	 * @param $value
	 * @return bool
	 */
	public function setCFStringValue($id, $value) : bool
	{
		try {
			$cfvs = $this->lead->getCustomFieldsValues();

				$cfvs
					->add(
						(new TextCustomFieldValuesModel())
							->setFieldId($id)
							->setValues(
								(new TextCustomFieldValueCollection())
									->add(
										(new TextCustomFieldValueModel())
											->setValue($value)
									)
							)
					);
				$this->lead->setCustomFieldsValues($cfvs);
			return true;
		}catch (AmoCRMApiException $e)
		{
			Log::writeError(Log::LEAD, $e);
			return false;
		}

	}

	/**
	 * Заполнение DateTime поля заявки по id
	 * @param $id
	 * @param $value
	 * @return bool
	 */
	public function setCFDateTimeValue($id, $value) : bool
	{
		try {
			$cfvs = $this->lead->getCustomFieldsValues();

			$cfvs
				->add(
					(new DateTimeCustomFieldValuesModel())
						->setFieldId($id)
						->setValues(
							(new DateCustomFieldValueCollection())
								->add(
									(new DateTimeCustomFieldValueModel())
										->setValue($value)
								)
						)
				);
			$this->lead->setCustomFieldsValues($cfvs);
			return true;
		}catch (AmoCRMApiException $e)
		{
			Log::writeError(Log::LEAD, $e);
			return false;
		}
	}

	/**
	 * Сохранение лида
	 * @return void
	 * @throws AmoCRMApiException
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
	 */
	public function save()
	{
		$this->apiClient->leads()->updateOne($this->lead);

	}

	/**
	 * Задание имени заявки
	 * @param $name
	 * @return bool
	 */
	public function setName($name)
	{
		try{
			$this->lead->setName($name);
			return true;
		}catch (Exception $e)
		{
			Log::writeError(Log::LEAD, $e);
			return false;
		}
	}

	/**
	 * Задание воронки заявки
	 * @param $name
	 * @return bool
	 */
	public function setPipeline($id)
	{
			$this->lead->setPipelineId($id);
			return true;
	}

	/**
	 * Задание этапа заявки в воронке
	 * @param $name
	 * @return bool
	 */
	public function setStatus($id)
	{
		try{
			$this->lead->setStatusId($id);
			return true;
		}catch (Exception $e)
		{
			Log::writeError(Log::LEAD, $e);
			return false;
		}
	}

	/**
	 * Установка тегов по тегам из Tags.php
	 * @param array $newtags
	 * @return void
	 */
	public function setTags(array $newtags)
	{
		$tags = $this->lead->getTags();
		foreach ($newtags as $tag)
		{
			try {
				$tags->add((new TagModel())
					->setName($tag['name'])
					->setId($tag['id']));
			}catch (Exception $e)
			{
				Log::writeError(Log::LEAD, $e);
			}
		}
		$this->lead->setTags($tags);
	}

	/**
	 * Получение id прикрепленного контакта
	 * @return ContactModel
	 * @throws AmoCRMApiException
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
	 */
	public function getContact()
	{
		return $this->apiClient->contacts()->getOne($this->apiClient->leads()->getLinks($this->lead)->first()->getToEntityId());
	}

	/**
	 * Удаление тега сделаки по id
	 * @param $id
	 * @return void
	 */
	public function deleteTag($id)
	{
		$this->lead->getTags()->removeBy('id', $id['id']);

	}

	public function newSystemMessage($text)
	{
		$notesCollection = new NotesCollection();
		$serviceMessageNote = new ServiceMessageNote();
		$serviceMessageNote	->setText($text)
			->setService('PHP-TEST')
			->setEntityId($this->lead->getId());



		$notesCollection->add($serviceMessageNote);


		try {
			$leadNotesService = $this->apiClient->notes(EntityTypesInterface::LEADS);
			$notesCollection = $leadNotesService->add($notesCollection);
		} catch (AmoCRMApiException $e) {
			printError($e);
			die;
		}
	}


}
