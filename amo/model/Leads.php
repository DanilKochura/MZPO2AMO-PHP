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
//		$pipeline = $post['pipeline'] ?: PIPELINE;

		if($this->type == self::SUBDOMAIN)
		{
			$price = (int)$post['price'] ?: 0;
			$webinar = (bool)$post['webinar'];
			$events = (bool)$post['events'];
			$demoLesson = (bool)(isset($post['form_name_site']) and strpos('Пробный урок', $post['form_name_site'] !== false));

			if ($price == 0 &&
				$webinar)
			{
				$pipeline = Pipelines::FREE_WEBINARS;
				$status = Statuses::NEW_LEAD_WEBINARS;
			}

			if ($demoLesson)
			{
				$pipeline = Pipelines::OPEN_LESSON;
				$status = Statuses::NEW_LEAD_OPEN_LESSON;
			}
		}

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
			$lead->setStatusId($post['status']);
		}
		$lead->setCustomFieldsValues($this->customLeadFileds($post));
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


	public function getCF($id)
	{
		try {
			$customFields = $this->lead->getCustomFieldsValues();

			//Получим значение поля по его ID
			if (!empty($customFields)) {
				return $customFields->getBy('fieldId', $id);
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
	public function getContact(): ?ContactModel
	{
		$links = $this->apiClient->leads()->getLinks($this->lead);
		foreach ($links as $link)
		{
			if($link->getToEntityType() == 'contacts')
			{
				return $this->apiClient->contacts()->getOne($link->getToEntityId());
			}
		}
		return null;
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
	public static function getCorpResponsible($responsible)
	{
		if($responsible == 8993890)
		{
			return 8603416;
		} elseif($responsible == 8993898)
			{
				return 8628763;
			}
		elseif($responsible == 5761144)
		{
			return 2375131;
		}
		else return 2375131;

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
				if($text = $lead->getNote())
				{
					$leadCorp->newNote($text);

				}
				return $leadCorp->lead;
			}
		#endregion

		#region если нет, создаем и заполняем новую заявку
		$leadCorp = new LeadModel();
		$leadCorp->setName($lead->lead->getName());
		if($resp = Leads::getCorpResponsible($lead->lead->getResponsibleUserId()))
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

		$tags = $lead->lead->getTags() ?: new TagsCollection();
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


	/**
	 * Получение поля бюджет
	 * @return int|null
	 */
	public function getPrice(): ?int
	{
		return $this->lead->getPrice();
	}

	/**
	 * Установка поля бюджет
	 * @param $price
	 * @return void
	 */
	public function setPrice($price)
	{
		$this->lead->setPrice($price);
	}

	/**
	 * Получение тега сделки по id
	 * @param $tag
	 * @return TagModel|null
	 */
	public function hasTag($tag): ?TagModel
	{
		return $this->lead->getTags()->getBy('fieldID', $tag);
	}


	/**
	 * Метод для клонирования лида в договорной отдел
	 * @param $id - id заявки
	 * @return void
	 * @throws AmoCRMApiException
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
	 */
	public static function cloneToAgr($id)
		{
			$apiClient = (new MzpoAmo(MzpoAmo::SUBDOMAIN_CORP))->apiClient; //в этом методе было проще отойти от системы моделей
			#region Получение заявке в корпорате
			$leadCorp  = $apiClient->leads()->getOne($id, [LeadModel::CONTACTS]);
			Log::writeLine(Log::WEBHOOKS, 'Сделка получена '.$id);
			#endregion

			$name = $apiClient->users()->getOne($leadCorp->getResponsibleUserId())->getName(); //ответственный старой заявки (корп)

			#region Клонирование заявки в договорной
			$lead = new LeadModel();
			$csvf = $leadCorp->getCustomFieldsValues();
			$csvf->add((new \AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel())->setFieldId(CustomFields::ID_LEAD_RET[1])->setValues((new \AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection())->add((new \AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel())->setValue($leadCorp->getId()))))
			->add((new TextCustomFieldValuesModel())->setFieldId(CustomFields::CORP_MAN[0])->setValues((new TextCustomFieldValueCollection())->add((new NumericCustomFieldValueModel())->setValue($name))));
			$lead->setName($leadCorp->getName())
				->setPrice($leadCorp->getPrice())
				->setTags($leadCorp->getTags())
				->setCompany($leadCorp->getCompany())
				->setContacts($leadCorp->getContacts())
				->setPipelineId(Pipelines::DOG)
				->setCustomFieldsValues($csvf)
				->setResponsibleUserId(Users::SIDOROVA);
			;
			$apiClient->leads()->addOne($lead);
			Log::writeLine(Log::WEBHOOKS, 'Сделка  склонирована: '.$lead->getId());
			#endregion

			#region Обновление заявке в корпорате
			$csvf->add((new \AmoCRM\Models\CustomFieldsValues\NumericCustomFieldValuesModel())->setFieldId(CustomFields::LEAD_DOG[1])->setValues((new \AmoCRM\Models\CustomFieldsValues\ValueCollections\NumericCustomFieldValueCollection())->add((new \AmoCRM\Models\CustomFieldsValues\ValueModels\NumericCustomFieldValueModel())->setValue($lead->getId()))));
			$apiClient->leads()->updateOne($leadCorp->setCustomFieldsValues($csvf));
			Log::writeLine(Log::WEBHOOKS, 'Старая сделка обновлена!');
			#endregion

			#region Сохранение ответственного (по просьбе Левана)
			$leadNotesService = $apiClient->notes(EntityTypesInterface::LEADS);
			$Note = new CommonNote();
			$Note->setText('Ответственный менеджер в корпорате: '.$name)
			->setEntityId($lead->getId());
			$note = $leadNotesService->addOne($Note);
			#endregion

			#region Перенос примечаний
			$notesCollection = $leadNotesService->getByParentId($leadCorp->getId(), (new NotesFilter())->setNoteTypes([NoteFactory::NOTE_TYPE_CODE_COMMON]));
			$nn = new NotesCollection();
			foreach ($notesCollection as $n)
			{
				$nn->add($n->setEntityId($lead->getId()));
			}
			$apiClient->notes(EntityTypesInterface::LEADS)->add($nn);
			#endregion



			Log::writeLine(Log::WEBHOOKS, 'Комментарии добавлены!');


	}


	public function getStatus()
	{
		return $this->lead->getStatusId();
	}

}
