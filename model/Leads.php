<?php

namespace MzpoAmo;

use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Collections\TagsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\NoteModel;
use AmoCRM\Models\NoteType\CommonNote;
use AmoCRM\Models\TagModel;
use AmoCRM\OAuth2\Client\Provider\AmoCRMException;

class Leads extends MzpoAmo
{

	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * массив связей "поле POST - поле заявки AMO
	 * @var array
	 */
	private $post_to_amo = [
		'city_name' => 639087,
		'result' => 644675,
		'roistat_marker' => 639085,
		'site' => 639081,
		'form_name_site' => TYPE,
		'roistat_visit'=> 639073,
		'page_url'=> 639083,

	];


	/**
	 * Добавление в амо нового лида
	 * @param $post
	 * @return void
	 */
	public function newLead(array $post) : LeadModel
	{
		#region создание модели лида
		$lead = (new LeadModel())
			->setName('Новая заявка с сайта '.$post['site'])
			->setPipelineId(PIPELINE)
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
		$lead->setCustomFieldsValues($this->customLeadFileds($post));
		#endregion

		#region установка тегов
		if(!$post['tags'])
		{
			$matches = [];
			preg_match_all('~([\w-]*)(\.[ru|com|education]+)~', $post['site'], $matches);
			$post['tags'][0] = $matches[1][0];
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
	 * Добавление в АМО нового контакта с проверкой входных данных
	 * @param $array
	 * @return ContactModel
	 * @throws \AmoCRM\Exceptions\AmoCRMApiException
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
	 */
	public function newContact(array $array) : ContactModel
	{
		#region создание модели контакта
		$contact = new ContactModel();
		if($array['name'])
		{
			$contact->setFirstName($array['name']);
		}
		if($array['surname'])
		{
			$contact->setLastName($array['surname']);
		}
		if($array['phone'] or $array['email'])
		{
			$fields = new CustomFieldsValuesCollection();
			if($array['phone'])
			{
				$fields->add(
					(new MultitextCustomFieldValuesModel())
						->setFieldCode('PHONE')
						->setValues(
							(new MultitextCustomFieldValueCollection())
								->add(
									(new MultitextCustomFieldValueModel())
										->setValue($array['phone'])
								)
						)
				);
			}
			if($array['email'])
			{
				$fields->add(
					(new MultitextCustomFieldValuesModel())
						->setFieldCode('EMAIL')
						->setValues(
							(new MultitextCustomFieldValueCollection())
								->add(
									(new MultitextCustomFieldValueModel())
										->setValue($array['email'])
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
		#endregion

		return $contact;
	}

	/**
	 *  Создание комментария для сделки (поле comment)
	 * @param $message
	 * @param $lead
	 * @return \AmoCRM\Models\NoteModel|void
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\InvalidArgumentException
	 */
	public function newNote(string $message, LeadModel $lead) : NoteModel
	{
		#region создание модели комментария
		$leadNotesService = $this->apiClient->notes(EntityTypesInterface::LEADS);
		$Note = new CommonNote();
		$Note->setText($message)
			->setEntityId($lead->getId());
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
	 * Привязка контакта к заявке
	 * @param ContactModel $contact
	 * @param LeadModel $lead
	 * @return bool
	 */
	public function linkContact(ContactModel $contact, LeadModel $lead) : bool
	{
		#region создание модели связи
		$links = new LinksCollection();
		$links->add($lead);
		#endregion

		#region сохранение
		try {
			$this->apiClient->contacts()->link($contact, $links);
		} catch (AmoCRMApiException $e) {
			printError($e);
			die;
		}
		#endregion

		return true;
	}


	/**
	 * Заполнение кастомных полей заявки
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

	public function findContact($array)
	{
		$filter = new ContactsFilter();
		$collection = new CustomFieldsValuesCollection();
		if($array['phone'])
		{
			$collection->add(
				(new MultitextCustomFieldValuesModel())
					->setFieldCode('PHONE')
					->setValues(
						(new MultitextCustomFieldValueCollection())
							->add(
								(new MultitextCustomFieldValueModel())
									->setValue($array['phone'])
							)
					)
			);
		}
		if($array['email'])
		{
			$collection->add(
				(new MultitextCustomFieldValuesModel())
					->setFieldCode('EMAIL')
					->setValues(
						(new MultitextCustomFieldValueCollection())
							->add(
								(new MultitextCustomFieldValueModel())
									->setValue($array['email'])
							)
					)
			);
		}
		$filter->setCustomFieldsValues($collection);

//Получим сделки по фильтру
		try {
			$contacts = $this->apiClient->contacts()->get($filter);
		} catch (AmoCRMApiException $e) {
			printError($e);
			die;
		}
		dd($contacts);

	}
}