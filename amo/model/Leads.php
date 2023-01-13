<?php

namespace MzpoAmo;

use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\NotesCollection;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Exceptions\AmoCRMApiErrorResponseException;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMApiNoContentException;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Filters\NotesFilter;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFields\DateTimeCustomFieldModel;
use AmoCRM\Models\CustomFieldsValues\DateTimeCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\DateCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\DateTimeCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\Factories\NoteFactory;
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
	private ?LeadModel $lead;
	public function 	__construct($post, $type = self::SUBDOMAIN, $id = null)
	{
		parent::__construct($type);

		#region если нужно склеить со старой заявкой
		if($id != null)
		{
			Log::writeLine(Log::LEAD, 'Слияние сделки со сделкой '.$id);
			try{
				$this->lead = $this->apiClient->leads()->getOne($id);

			} catch (AmoCRMApiNoContentException $ex )
			{
				$this->lead = null;
			} catch (Exception $e)
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
	public function getLead() : ?LeadModel
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
							->setFieldId(CustomFields::TYPE[0])
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
			$this->setStatusId($post['status']);
		}
		$this->setCustomFieldsValues($this->customLeadFileds($post));
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
		$this->setTags($tags);
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

	public function getNote($type = NoteFactory::NOTE_TYPE_CODE_COMMON)
	{
		try {
			$leadService = $this->apiClient->notes(EntityTypesInterface::LEADS);
			$notesCollection = $leadService->getByParentId($this->lead->getId(), (new NotesFilter())->setNoteTypes([$type]));
			return $notesCollection->last()->text;
		} catch (Exception $e)
		{
			return null;
		}

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
		$i = $this->type == self::SUBDOMAIN_CORP ? 1 : 2;
		foreach ($this->post_to_amo as $post => $id)
		{
			if ($POST[$post] and $id[$i])
			{
				$fileds->add(
					(new TextCustomFieldValuesModel())
						->setFieldId($id[$i])
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


	/**
	 * Добавление системного сообщения к заявке
	 * @param $text
	 * @return void
	 */
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

	/** Установка ответственного для заявки
	 * @param $id
	 * @return void
	 */
	public function setResponsibleUser($id)
	{
		$this->lead->setResponsibleUserId($id);
	}

	/**
	 * Получение ответственного для заявки
	 * @return int|null
	 */
	public function getResponsible(): ?int
	{
		return $this->lead->getResponsibleUserId();
	}

	/**
	 * Получение ответственного для корп
	 * @param $responsible
	 * @return false|int
	 */
	public function getCorpResponsible($responsible)
	{
		if($responsible == 8993890)
		{
			return 8603416;
		} else if($responsible == 8993898 )
			{
				return 8628763;
			}
		else return false;
	}

	/**
	 * Клонирование заявки в амо корпората
	 * @param Leads $lead
	 * @param $id
	 * @return LeadModel|null
	 * @throws AmoCRMApiException
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
	 * @throws \AmoCRM\Exceptions\InvalidArgumentException
	 */
	public static function clone(Leads $lead, $id = null): ?LeadModel
	{
		$post = [];

		#region если есть сделка, с которой можно склеить
		if($id)
			{
				$leadCorp = new self([], MzpoAmo::SUBDOMAIN_CORP, $id);
				$leadCorp->newNote($lead->getNote());
				return $leadCorp->lead;
			}
		#endregion

		#region если нет, создаем и заполняем новую заявку
		$leadCorp = new LeadModel();
		$leadCorp->setName($lead->lead->getName());
		if($resp = $lead->getCorpResponsible($lead->lead->getResponsibleUserId()))
		{
			$leadCorp->setResponsibleUserId($resp);
		}
		$cfvs = new CustomFieldsValuesCollection();
		foreach (CustomFields::CORP_FIELDS as $field)
		{
			if($value = $lead->getCFValue($field[0]))
				$cfvs->add(
					(new TextCustomFieldValuesModel())
						->setFieldId($field[1])
						->setValues(
							(new TextCustomFieldValueCollection())
								->add(
									(new TextCustomFieldValueModel())
										->setValue($value)
								)
						)
				);
		}
		$cfvs->add((new NumericCustomFieldValuesModel())->setFieldId(CustomFields::RET_ID[1])->setValues((new NumericCustomFieldValueCollection())->add((new NumericCustomFieldValueModel())->setValue($lead->lead->getId()))));

		$tags = $lead->lead->getTags();
		$leadCorp->setTags($tags
			->add(
				(new TagModel())
					->setId(Tags::RETAIL_LEAD['id'])
					->setName(Tags::RETAIL_LEAD['name'])
			)->add(
				(new TagModel())
					->setId(Tags::EDUCATION_CORP['id'])
					->setName(Tags::EDUCATION_CORP['name'])
			)
		);
		$leadCorp->setCustomFieldsValues($cfvs);
		$leadCorp->setPipelineId(Pipelines::NEW);
		#endregion

		$mzpoAmo = new MzpoAmo(MzpoAmo::SUBDOMAIN_CORP);
	try {
		#region сохранение
		$newlead = $mzpoAmo->apiClient->leads()->addOne($leadCorp);
		#endregion

		#region комментарий
		if($text = $lead->getNote())
		{
			$leadNotesService = $mzpoAmo->apiClient->notes(EntityTypesInterface::LEADS);
			$Note = new CommonNote();
			$Note->setText($text)
				->setEntityId($newlead->getId());
			$note = $leadNotesService->addOne($Note);
		}

		#endregion

	}	catch (AmoCRMApiErrorResponseException $e)
	{
		dd($e->getValidationErrors());
	}
		return $leadCorp;
	}


}
