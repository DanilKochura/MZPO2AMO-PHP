<?php

namespace MzpoAmo;

use AmoCRM\Collections\CatalogElementsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\CatalogElementsFilter;
use AmoCRM\Models\CatalogElementModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;

class Course extends MzpoAmo
{
	private CatalogElementModel $course;
	public const CATALOG = [12463, 5111];

	public function __construct(CatalogElementModel $course, $type = MzpoAmo::SUBDOMAIN)
	{
		parent::__construct($type);

		$this->course = $course;

	}

	/**
	 * Установка кастомных полей курса
	 * @param $id
	 * @param $value
	 * @return bool
	 */
	public function setCfValue($id, $value): bool
	{
			$cfvs = $this->course->getCustomFieldsValues();
			if(!$cfvs)
			{
				$cfvs = new CustomFieldsValuesCollection();
			}
			if($field = $cfvs->getBy('fieldId', $id))
			{
				$cfvs->getBy('fieldId', $id)->setValues((new TextCustomFieldValueCollection())
					->add(
						(new TextCustomFieldValueModel())
							->setValue($value)
					));
			}
			else
			{
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
			}

			$this->course->setCustomFieldsValues($cfvs);
			return true;
	}

	/**
	 * Получение кастомного поля курсов
	 * @param $id
	 * @return array|bool|int|object|string|null
	 */
	public function getCfValue($id)
	{
		$customFields = $this->course->getCustomFieldsValues();

		//Получим значение поля по его ID
		if (!empty($customFields)) {
			$textField = $customFields->getBy('fieldId', $id);
			if ($textField) {
				return $textField->getValues()->first()->getValue();
			}
		}
		return false;
	}

	/**
	 * Установка имени курса
	 * @param $name
	 * @return void
	 */
	public function setName($name)
	{
		$this->course->setName($name);
	}

	/**
	 * Сохранение курса
	 * @return int|null
	 * @throws AmoCRMApiException
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
	 * @throws \AmoCRM\Exceptions\InvalidArgumentException
	 */
	public function save(): ?int
	{
		return $this->apiClient->catalogElements($this::CATALOG[$this->getType()])->updateOne($this->course)->getId();
	}

}