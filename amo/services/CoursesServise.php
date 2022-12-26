<?php

namespace services;

use AmoCRM\Collections\CatalogElementsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\CatalogElementsFilter;
use AmoCRM\Models\CatalogElementModel;
use MzpoAmo\Course;
use MzpoAmo\Log;
use MzpoAmo\MzpoAmo;

class CoursesServise extends MzpoAmo
{
	private const CATALOG = 12463;

	public function __construct()
	{
		parent::__construct();
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

	public function deleteCourses($uid, $method)
	{
		$access_Token = getToken();
		$link = 'https://' . $this::SUBDOMAIN . '.amocrm.ru/api/v2/catalog_elements' ;
		$headers = [
			'Authorization: Bearer ' . $access_Token
		];
		try {
			$courses = $this->getCoursesByUid($uid); // получение коллекции курсов
		} catch (AmoCRMApiException $e) {
			http_response_code(404);
			die('Course not found');
		}
		$i = 0;
		//Лимит на получение товаров из amo - 50 штук на один запрос, для этого нужен цикл. Некоторые курсы дублировались и хранятся по 200+ экземпляров.
		while(1) {
			$catalog_elements['delete'] = [];
			foreach ($courses as $course) {
				if ($course->getCustomFieldsValues()->getBy('fieldId', 710407)->getValues()->first()->getValue() == $uid) {
					$catalog_elements['delete'][] = $course->getId();
				}
			}
			$i+=count($catalog_elements['delete']);
			$curl = curl_init();
			/** Устанавливаем необходимые опции для сеанса cURL  */
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
			curl_setopt($curl, CURLOPT_URL, $link);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($catalog_elements));
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			$out = curl_exec($curl);

			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			try {
				$courses = $this->getCoursesByUid($uid);
			} catch (AmoCRMApiException $e) {
				Log::writeLine('Api', $method.' - '.'Deleted: '.$i);
				http_response_code(200);
				die('Success');
			}
		}
	}

	public function deleteDoubles($uid, $method)
	{
		#region обновление токена
		$base = new MzpoAmo();
		$access_Token = getToken();
		$link = 'https://' . self::SUBDOMAIN . '.amocrm.ru/api/v2/catalog_elements' ;
		$headers = [
			'Authorization: Bearer ' . $access_Token
		];
		#endregion

		#region получение и удаление
		try {
			$courses = $this->getCoursesByUid($uid);  // получение коллекции курсов
		} catch (AmoCRMApiException $e) {
			http_response_code(404);
			die('Course not found');
		}
		$i = 0;
		//Лимит на получение товаров из amo - 50 штук на один запрос, для этого нужен цикл. Некоторые курсы дублировались и хранятся по 200+ экземпляров.
		while(1) {
			if($courses->count() == 1)
			{
				Log::writeLine('Api', $method.' - '.'Deleted: '.$i);
				http_response_code(200);
				die('Success');
			}
			$catalog_elements['delete'] = [];
			foreach ($courses as $course) {
				if ($course->getCustomFieldsValues()->getBy('fieldId', 710407)->getValues()->first()->getValue() == $uid) {
					$catalog_elements['delete'][] = $course->getId();
				}
			}
			array_pop($catalog_elements['delete']);
			$i+=count($catalog_elements['delete']);
			$curl = curl_init();
			/** Устанавливаем необходимые опции для сеанса cURL  */
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
			curl_setopt($curl, CURLOPT_URL, $link);
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($catalog_elements));
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			$out = curl_exec($curl);

			$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			try {
				$courses = $this->getCoursesByUid($uid);
			} catch (AmoCRMApiException $e) {
				Log::writeLine('Api', $method.' - '.'Deleted: '.$i);
				http_response_code(200);
				die('Success');
			}
		}
		#endregion
	}

	public function getCoursesByUid($uid): ?CatalogElementsCollection
	{
		$catalog = $this->apiClient->catalogs()->getOne($this::CATALOG);
		$catalogElementsService = $this->apiClient->catalogElements($catalog->getId());
		$catalogElementsFilter = new CatalogElementsFilter();
		$catalogElementsFilter->setQuery($uid);
		return $catalogElementsService->get($catalogElementsFilter);
	}
	public function getCourse($id): ?CatalogElementModel
	{
		$catalog = $this->apiClient->catalogs()->getOne(12463);
		$catalogElementsService = $this->apiClient->catalogElements($catalog->getId());
		return $catalogElementsService->getOne($id);
	}

	public function createCourse($name)
	{
		$course = new CatalogElementModel();
		$course->setCatalogId($this::CATALOG);
		$course->setName($name);
		try{
			$course = $this->apiClient->catalogElements(self::CATALOG)->addOne($course);
		}catch(\AmoCRM\Exceptions\AmoCRMApiErrorResponseException $e)
		{
			http_response_code(500);
			dd($e->getValidationErrors());
		}
		return $course;
	}
}