<?php

namespace services;

use AmoCRM\Collections\CatalogElementsCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\CatalogElementsFilter;
use AmoCRM\Models\CatalogElementModel;
use MzpoAmo\Course;
use MzpoAmo\CustomFields;
use MzpoAmo\Log;
use MzpoAmo\MzpoAmo;

class CoursesServise extends MzpoAmo
{

	public const CATALOG = [12463, 5111];

	public function __construct($type = MzpoAmo::SUBDOMAIN)
	{
		parent::__construct($type);
	}

	/**
	 * Удаление всех сущностей курса по uid
	 * @param $uid
	 * @param $method
	 * @return void
	 */
	public function deleteCourses($uid, $method)
	{
		$access_Token = getToken($this->type);
		$link = 'https://' . $this->type . '.amocrm.ru/api/v2/catalog_elements' ;
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
				if ($course->getCustomFieldsValues()->getBy('fieldId', CustomFields::COURSE_UID_1c[$this->type])->getValues()->first()->getValue() == $uid) {
					$catalog_elements['delete'][] = $course->getId();
				}
			}
			$i+=count($catalog_elements['delete']);

			#region отправка запроса к API amoCRM
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
			#endregion

			try {
				$courses = $this->getCoursesByUid($uid);
			} catch (AmoCRMApiException $e) {
				Log::writeLine('Api', $method.' - '.'Deleted: '.$i);
				http_response_code(200);
				die('Success');
			}
		}
	}

	/** Удаление дублей курса
	 * @param $uid
	 * @param $method
	 * @return void
	 */
	public function deleteDoubles($uid, $method)
	{
		#region обновление токена
		$base = new MzpoAmo($this->type);
		$access_Token = getToken($this->type);
		$link = 'https://' . $this->type . '.amocrm.ru/api/v2/catalog_elements' ;
		$headers = [
			'Authorization: Bearer ' . $access_Token
		];
		#endregion

		#region получение и удаление
		try {
			$courses = $this->getCoursesByUid($uid);  // получение коллекции курсов
		} catch (AmoCRMApiException $e) {
			http_response_code(404);
			echo 'Course not found';
			return;
		}
		$i = 0;
		//Лимит на получение товаров из amo - 50 штук на один запрос, для этого нужен цикл. Некоторые курсы дублировались и хранятся по 200+ экземпляров.
		while(1) {
			if($courses->count() == 1)
			{
				Log::writeLine('Api', $method.' - '.$uid.'Deleted: '.$i);
				http_response_code(200);
				echo 'Success';
				return;
			}
			$catalog_elements['delete'] = [];
			foreach ($courses as $course) {
				if ($course->getCustomFieldsValues()->getBy('fieldId', CustomFields::COURSE_UID_1c[$this->getType()])->getValues()->first()->getValue() == $uid) {
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
				echo 'Success';
				return;
			}
		}
		#endregion
	}


	/**
	 * Получение курса по uid
	 * @param $uid
	 * @return CatalogElementsCollection|null
	 * @throws AmoCRMApiException
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
	 * @throws \AmoCRM\Exceptions\InvalidArgumentException
	 */
	public function getCoursesByUid($uid): ?CatalogElementsCollection
	{
		$catalog = $this->apiClient->catalogs()->getOne($this::CATALOG[$this->getType()]);
		$catalogElementsService = $this->apiClient->catalogElements($catalog->getId());
		$catalogElementsFilter = new CatalogElementsFilter();
		$catalogElementsFilter->setQuery($uid);
		return $catalogElementsService->get($catalogElementsFilter);
	}

	/**
	 * Получение курса по id сущности
	 * @param $id
	 * @return CatalogElementModel|null
	 * @throws AmoCRMApiException
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
	 * @throws \AmoCRM\Exceptions\InvalidArgumentException
	 */
	public function getCourse($id): ?CatalogElementModel
	{
		$catalog = $this->apiClient->catalogs()->getOne($this::CATALOG[$this->getType()]);
		$catalogElementsService = $this->apiClient->catalogElements($catalog->getId());
		return $catalogElementsService->getOne($id);
	}

	/**
	 * Создание пустого курса  с названием
	 * @param $name
	 * @return CatalogElementModel
	 * @throws AmoCRMApiException
	 * @throws \AmoCRM\Exceptions\AmoCRMMissedTokenException
	 * @throws \AmoCRM\Exceptions\AmoCRMoAuthApiException
	 * @throws \AmoCRM\Exceptions\InvalidArgumentException
	 */
	public function createCourse($name)
	{
		$course = new CatalogElementModel();
		$course->setCatalogId($this::CATALOG[$this->getType()]);
		$course->setName($name);
		try{
			$course = $this->apiClient->catalogElements(self::CATALOG[$this->getType()])->addOne($course);
		}catch(\AmoCRM\Exceptions\AmoCRMApiErrorResponseException $e)
		{
			http_response_code(500);
			dd($e->getValidationErrors());
		}
		return $course;
	}
}