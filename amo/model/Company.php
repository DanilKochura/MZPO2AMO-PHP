<?php

namespace MzpoAmo;

use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\CompanyModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\NoteType\CommonNote;
use Exception;

class Company extends MzpoAmo
{

	public CompanyModel $company;
	public function __construct($id = null, $type = MzpoAmo::SUBDOMAIN_CORP)
	{
		parent::__construct($type);
		if(is_a($id, 'AmoCRM\Models\CompanyModel'))
		{
			$this->company = $id;
		} elseif (is_int($id))
		{
			$this->company = $this->apiClient->companies()->getOne($id);
		} else
		{
			throw new \Exception('Not released!!');
		}
	}
	public function getCFValue($id)
	{
		try {
			$customFields = $this->company->getCustomFieldsValues();

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
	public function getName()
	{
		return $this->company->getName();
	}

	public function getId()
	{
		return $this->company->getId();
	}

	public function setCFStringValue($id, $value) : bool
	{
		try {
			$cfvs = $this->company->getCustomFieldsValues() ?: new CustomFieldsValuesCollection();
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
			$this->company->setCustomFieldsValues($cfvs);
			return true;
		}catch (AmoCRMApiException $e)
		{
			Log::writeError(Log::LEAD, $e);
			return false;
		}

	}


	public function save()
	{
		$this->apiClient->companies()->updateOne($this->company);
	}

	public function setNoteSave(string $message)
	{
		#region создание модели комментария
		try	{
			$leadNotesService = $this->apiClient->notes(EntityTypesInterface::COMPANIES);
			$Note = new CommonNote();
			$Note->setText($message)
				->setEntityId($this->company->getId());
		} catch (Exception $e)
		{
			Log::writeError(Log::CONTACT, $e);
		}
		#endregion

		#region сохранение
		try {
			$note = $leadNotesService->addOne($Note);
		} catch (AmoCRMApiException $e) {
			Log::writeError(Log::CONTACT, $e);
		}
		#endregion

		return $note;
	}

	public function setName($name)
	{
		$this->company->setName($name);
	}

}