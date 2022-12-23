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
	private const CATALOG = 12463;
	public function __construct(CatalogElementModel $course)
	{
		parent::__construct();

		$this->course = $course;

	}

	public function setCfValue($id, $value): bool
	{
			$cfvs = $this->course->getCustomFieldsValues();
			if(!$cfvs)
			{
				$cfvs = new CustomFieldsValuesCollection();
			}
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
			$this->course->setCustomFieldsValues($cfvs);
			return true;
	}

	public function setName($name)
	{
		$this->course->setName($name);
	}

	public function save(): ?int
	{
		return $this->apiClient->catalogElements($this::CATALOG)->updateOne($this->course)->getId();
	}

}