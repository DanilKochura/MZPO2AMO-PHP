<?php

use MzpoAmo\Course;
use MzpoAmo\CustomFields;
use MzpoAmo\Log;
use services\CoursesServise;

$secret_key = 'sdDF4sdfR';  //пароль для API
$subdomain = 'mzpoeducationsale'; //Поддомен нужного аккаунта

require '../../vendor/autoload.php';
require '../model/MzpoAmo.php';
require '../dict/CustomFields.php';
require '../model/Course.php';
require '../model/Log.php';
require '../services/CoursesServise.php';

#region псевдо-маршрутизация
$request =  $_SERVER['REQUEST_URI'];
$method = explode('?', $request)[1];
#endregion

if(!$_POST)
{
	$_POST=json_decode(file_get_contents('php://input'), true);
}
Log::writeLine('Api', $method.' - '.$_POST['uid'].print_r($_POST,1));

#region удаление архивного курса
//https://mzpo-s.ru/amo/api?delete_course
if($method=='delete_course')
{
	#region обработка POST запроса
	if($_POST['secret_key'] != $secret_key)
	{
		http_response_code(401);
		die('Access denied!');
	}
	if(!$uid = $_POST['uid'])
	{
		http_response_code(404);
		die('Incorrect course uid');
	}
	#endregion
	$courseBase = new CoursesServise();
	$courseBase->deleteCourses($uid, $method);

}
#endregion

#region удаление дублей курса
//https://mzpo-s.ru/amo/api?delete_course
elseif($method=='delete_doubles')
{


	#region обработка POST запроса
	if($_POST['secret_key'] != $secret_key)
	{
		http_response_code(401);
		die('Access denied!');
	}
	if(!$uid = $_POST['uid'])
	{
		http_response_code(404);
		die('Incorrect course uid');
	}
	#endregion

	#region получение и удаление
	$courseBase = new CoursesServise();
	$courseBase->deleteDoubles($uid, $method);
	#endregion
}
#endregion

#region создание/обновление курса из 1с
elseif($method == 'update_course')
{
	#region обработка POST запроса
	if($_POST['secret_key'] != $secret_key)
	{
		http_response_code(401);
		die('Access denied!');
	}
	if(!$uid = $_POST['product_id_1C'])
	{
		http_response_code(404);
		die('Incorrect course uid');
	}
	$name = $_POST['name'];  //название
	$prefix = $_POST['short_name'] ?: ''; //префикс
	$hours = $_POST['duration'] ?: '';  //часы
	$form = $_POST['format'] ?: '';  //форма обучения
	$comment = $_POST['supplementary_info'] ?: ''; //примечания
	$prices = [];

	#region Цены
	foreach ($_POST['ItemPrices'] as $price)
	{
		$prices[$price['UID']] = $price['Price']; //
	}
	if(strpos($prefix, '-Д'))
	{
		$price = $prices['803fbc13-580c-11eb-86f0-82172a65f31e'];
	}elseif(strpos($prefix, '-И'))
	{
		$price = $prices['c0a6311b-8d63-11eb-891f-20040ffb909d'];
	}else
	{
		$price = $prices['5bba5dc4-580c-11eb-86f0-82172a65f31e'];
	}

	if(!$price)
	{
		$price = $prices['9e633e6d-3d2d-11eb-86e1-82172a65f31e'];
	}
	unset($prices); //чистим памятьэ
	#endregion

	if(strpos($prefix, '-') !== false)
	{
		$name.='('.$prefix.')';
	}
	$comment = $_POST['supplementary_info'] ?: '';
	$entity = '0000000';
	foreach ($_POST['amo_ids'] as $amo_id)
	{
		if($amo_id['account_id'] == 28395871)
		{
			$entity = $amo_id['entity_id'];
		}
	}

	#endregion
	
	
	$courseBase = new CoursesServise();
	try{
		$course = $courseBase->getCourse($entity);  //пытаемся найти курс по id
	} catch (Exception $e)
	{
		try {
			$course = $courseBase->getCoursesByUid($uid);  //пытаемся найти курс по id_1c
			if($course->count() > 1)
			{
				$courseBase->deleteDoubles($uid, $method); //если нашли, то проверим и почистим дубли
			}
			$course = $courseBase->getCoursesByUid($uid)->first();
		}
		catch (Exception $e)
		{
			Log::writeLine('Api', 'Курс '.$prefix.' не найден. Создаю новый.');
			$course = $courseBase->createCourse($name);  //если не нашли, то создаем новый
		}
	}
	$courseAmo = new Course($course);
	try {
		$courseAmo->setName($name);
		$courseAmo->setCfValue(CustomFields::SKU, $prefix);
		$courseAmo->setCfValue(CustomFields::STUDY_FORM, $form);
		$courseAmo->setCfValue(CustomFields::PRICE, $price);
		$courseAmo->setCfValue(CustomFields::COURSE_DESCR, $comment);
		$courseAmo->setCfValue(CustomFields::DURATION, $hours);
		$courseAmo->setCfValue(CustomFields::COURSE_UID_1c, $uid);
	} catch(\AmoCRM\Exceptions\AmoCRMApiErrorResponseException $e)
	{
		http_response_code(500);
		Log::writeError('Api', $e->getValidationErrors());
		dd('Error!');
	}
	http_response_code(200);
	echo json_encode(['amo_ids'=>[['account_id'=> 28395871, 'entity_id'=>$courseAmo->save()]]]);

}
#endregion

