<?php

namespace MzpoAmo;

use AmoCRM\Collections\CatalogElementsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\CatalogElementsFilter;
use AmoCRM\Models\CatalogElementModel;

class Course extends MzpoAmo
{
	private const CATALOG = 12463;
	public function __construct()
	{
		parent::__construct();
	}
	public function getCFvalue(CatalogElementModel $course)
	{
		return $course->getCustomFieldsValues()->getBy('fieldId', 710407)->getValues()->first()->getValue();
	}

	public function getCourses($uid)
	{
		$catalog = $this->apiClient->catalogs()->getOne(12463);
		$catalogElementsService = $this->apiClient->catalogElements($catalog->getId());
		$catalogElementsFilter = new CatalogElementsFilter();
		$catalogElementsFilter->setQuery($uid);
		$catalogElementsCollection = new CatalogElementsCollection();
		$catalogElementsCollection = $catalogElementsService->get($catalogElementsFilter)->all();
		return $catalogElementsCollection;

	}
}