<?php

use MzpoAmo\Course;
use MzpoAmo\CustomFields;
use MzpoAmo\Log;
use services\CoursesServise;

$secret_key = 'sdDF4sdfR';  //пароль для API
//$subdomain = 'mzpoeducationsale'; //Поддомен нужного аккаунта

require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/MzpoAmo.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/dict/CustomFields.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/Course.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/Log.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/services/CoursesServise.php';

#region псевдо-маршрутизация
$request =  $_SERVER['REQUEST_URI'];
$method = explode('?', $request)[1];
#endregion

if(!$_POST)
{
	file_put_contents(__DIR__.'/0.txt', file_get_contents('php://input'), FILE_APPEND);
	$_POST=json_decode(file_get_contents('php://input'), true);
}
Log::writeLine(Log::COURSE, $method.' - '.$_POST['short_name']);

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
	$courseBases = [new CoursesServise(), new CoursesServise(\MzpoAmo\MzpoAmo::SUBDOMAIN_CORP)];
	foreach ($courseBases as $courseBase)
	{
		$courseBase->deleteCourses($uid, $method);
	}

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
	if(!$name)
	{
		die('no name');
	}
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

	if(strpos($prefix, '-') === false or in_array($prefix, ['МАС-1', 'КОС-1', 'КЭС-1']))
	{
		$name.='('.$prefix.')';
	}
	$comment = $_POST['supplementary_info'] ?: '';
	$entities = [];
	foreach ($_POST['amo_ids'] as $amo_id)
	{
		$entities[$amo_id['account_id']] = $amo_id['entity_id'];
	}

	#endregion


	$courseBases = [new CoursesServise(\MzpoAmo\MzpoAmo::SUBDOMAIN_CORP), new CoursesServise()];
	$arr = [];

	foreach ($courseBases as $courseBase)
	{
		try{
			$course = $courseBase->getCourse($entities[$courseBase->getAccountId()]);  //пытаемся найти курс по id
		} catch (Exception $e)
		{

			try {
				$course = $courseBase->getCoursesByUid($uid);  //пытаемся найти курс по id_1c
//				if($course->count() > 1)
//				{
//					$courseBase->deleteDoubles($uid, $method); //если нашли, то проверим и почистим дубли
//				}
				$course = $courseBase->getCoursesByUid($uid)->first();

			}
			catch (Exception $e)
			{
				Log::writeLine(Log::COURSE, 'Курс '.$prefix.' не найден. Создаю новый.');
				$course = $courseBase->createCourse($name);  //если не нашли, то создаем новый
			}
		}
		$courseAmo = new Course($course, $courseBase->getSubdomain());
		try {
			$courseAmo->setName($name);
			$courseAmo->setCfValue(CustomFields::SKU[$courseAmo->getType()], $prefix);

			$courseAmo->setCfValue(CustomFields::STUDY_FORM[$courseAmo->getType()], $form);
			$courseAmo->setCfValue(CustomFields::PRICE[$courseAmo->getType()], $price);
			$courseAmo->setCfValue(CustomFields::COURSE_DESCR[$courseAmo->getType()], $comment);
			if($hours)
			{
				$courseAmo->setCfValue(CustomFields::DURATION[$courseAmo->getType()], $hours);
			}
			$courseAmo->setCfValue(CustomFields::COURSE_UID_1c[$courseAmo->getType()], $uid);
			$arr['amo_ids'][] = ['account_id'=> $courseBase->getAccountId(), 'entity_id'=>$courseAmo->save()];
		} catch(\AmoCRM\Exceptions\AmoCRMApiErrorResponseException $e)
		{
			print_r($courseAmo);
			print_r($e->getValidationErrors());
			http_response_code(500);
			Log::writeError(Log::COURSE, $e->getValidationErrors());
			dd('Error!');
		}
		http_response_code(200);
		Log::writeLine(Log::COURSE, 'курс '.$prefix.' обновлен!');
	}
	echo json_encode ($arr);

}
#endregion

