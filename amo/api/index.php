<?php

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Filters\CatalogElementsFilter;
use MzpoAmo\Course;
use MzpoAmo\Log;
use MzpoAmo\MzpoAmo;
$secret_key = 'sdDF4sdfR';
require '../../vendor/autoload.php';
require '../model/MzpoAmo.php';
require '../model/Course.php';
require '../model/Log.php';
$request =  $_SERVER['REQUEST_URI'];
$method = explode('?', $request)[1];
$subdomain = 'mzpoeducationsale'; //Поддомен нужного аккаунта

#region удаление архивного курса
//https://mzpo-s.ru/amo/api?delete_course
if($method=='delete_course')
{
	Log::writeLine('Api', $method.' - '.$_POST['uid']);

	#region обработка POST запроса
	if($_POST['secret_key'] != $secret_key)
	{
		http_response_code(401);
		die('Access denied!');
	}
	if(!$uid = $_POST['uid'])
	{
		http_response_code(405);
		die('Incorrect course uid');
	}
	#endregion

	#region обновление токена
	$base = new MzpoAmo();
	$access_Token = getToken();
	$link = 'https://' . $subdomain . '.amocrm.ru/api/v2/catalog_elements' ;
	$headers = [
		'Authorization: Bearer ' . $access_Token
	];
	#endregion

	#region получение и удаление
	$courseBase = new Course();
	try {
		$courses = $courseBase->getCourses($uid); // получение коллекции курсов
	} catch (AmoCRMApiException $e) {
		http_response_code(405);
		die('Course not found');
	}

	//Лимит на получение товаров из amo - 50 штук на один запрос, для этого нужен цикл. Некоторые курсы дублировались и хранятся по 200+ экземпляров.
	while(1) {
		$catalog_elements['delete'] = [];
		foreach ($courses as $course) {
			if ($course->getCustomFieldsValues()->getBy('fieldId', 710407)->getValues()->first()->getValue() == $uid) {
				$catalog_elements['delete'][] = $course->getId();
			}
		}
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
			$courses = $courseBase->getCourses($uid);
		} catch (AmoCRMApiException $e) {
			http_response_code(200);
			die('Success');
		}
	}
	#endregion

}
#endregion