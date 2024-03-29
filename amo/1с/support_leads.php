<?php

use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\CustomFieldsValues\DateTimeCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\SelectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\DateCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\SelectCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\DateTimeCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\SelectCustomFieldValueModel;
use AmoCRM\Models\TagModel;
use Carbon\Carbon;
use MzpoAmo\Contact;
use MzpoAmo\CustomFields;
use MzpoAmo\Leads;
use MzpoAmo\Log;
use MzpoAmo\MzpoAmo;
use MzpoAmo\MzposApiEvent;
use MzpoAmo\Pipelines;
use MzpoAmo\Statuses;
use MzpoAmo\Tags;
use MzpoAmo\Users;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use reports\EventsReport;
use services\QueueService;
use services\UserService;

require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/MzpoAmo.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Leads.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Contact.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Log.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/MzposApiEvent.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/CustomFields.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Pipelines.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Tags.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/services/UserService.php';

require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Statuses.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Users.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/amo/services/QueueService.php';
require $_SERVER['DOCUMENT_ROOT'] .'/amo/reports/BaseReport.php';
require $_SERVER['DOCUMENT_ROOT'] .'/amo/reports/EventsReport.php';
require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

#region Словарь пользователей

#endregion

#region Словарь организаций
$orgs = [
	'НОЧУ ДПО МЦПО' => 'МЦПО',
	'ООО «МЦПО»' => 'ООО «МЦПО»',
	'ООО «МИРК»' =>'ООО «МИРК»'
];
#endregion

$_POST=json_decode(file_get_contents('php://input'), true);
file_put_contents(__DIR__.'/0.txt', json_encode($_POST), FILE_APPEND);
$mzpo = new MzpoAmo();

$t = new Leads([], MzpoAmo::SUBDOMAIN, $_POST['lead_id']);

#region Новая заявка
if(!$_POST['lead_id'] or !$t->getLead()) {


	$lead = [];
	#region Парсинг запроса
	$_POST['title'] = $_POST['group'] . ' ' . date('d.m', strtotime($_POST['date_start'])) . ' ' . explode(' ', $_POST['teacher'])[0];
    $userService  = new UserService();
    $_POST['resp'] = $userService->getUser($_POST['responsible_user']) ?: Users::ADMIN;
    $resp = $_POST['resp'];
    #endregion

	$lead = new \AmoCRM\Models\LeadModel();

	#region Заполнение заявки
	$lead->setName($_POST['title']);
	$cfvs = new \AmoCRM\Collections\CustomFieldsValuesCollection();
	$cfvs->add(
		(new DateTimeCustomFieldValuesModel())
			->setFieldId(CustomFields::START_STUDY[0])
			->setValues(
				(new DateCustomFieldValueCollection())
					->add(
						(new DateTimeCustomFieldValueModel())
							->setValue(Carbon::parse($_POST['date_start']))
					)
			)
	)->add(
		(new DateTimeCustomFieldValuesModel())
			->setFieldId(CustomFields::END_STUDY[0])
			->setValues(
				(new DateCustomFieldValueCollection())
					->add(
						(new DateTimeCustomFieldValueModel())
							->setValue(Carbon::parse($_POST['date_end']))
					)
			)
	)->add(
		(new DateTimeCustomFieldValuesModel())
			->setFieldId(CustomFields::EXAM_DATE[0])
			->setValues(
				(new DateCustomFieldValueCollection())
					->add(
						(new DateTimeCustomFieldValueModel())
							->setValue(Carbon::parse($_POST['date_exam']))
					)
			)
	)->add(
		(new SelectCustomFieldValuesModel())
			->setFieldId(CustomFields::OFICIAL_NAME[0])
			->setValues(
				(new SelectCustomFieldValueCollection())
					->add(
						(new SelectCustomFieldValueModel())
							->setValue($orgs[$_POST['organization']])
					)
			)
	)->add(
		(new SelectCustomFieldValuesModel())
			->setFieldId(CustomFields::STUDY_FORM_RET[0])
			->setValues(
				(new SelectCustomFieldValueCollection())
					->add(
						(new SelectCustomFieldValueModel())
							->setValue('Очная')
					)
			)
	)->add(
		(new SelectCustomFieldValuesModel())
			->setFieldId(CustomFields::STUDY_TYPE[0])
			->setValues(
				(new SelectCustomFieldValueCollection())
					->add(
						(new SelectCustomFieldValueModel())
							->setValue('Групповой')
					)
			)
	)->add(
		(new \AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel())
			->setFieldId(CustomFields::PREPODAVATEL[0])
			->setValues((new \AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection())
				->add((new \AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel())->setValue($_POST['teacher'])))
	)->add(
		(new \AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel())
			->setFieldId(CustomFields::AUDITORY[0])
			->setValues((new \AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection())
				->add((new \AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel())->setValue($_POST['auditory'])))
	)->add(
		(new \AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel())
			->setFieldId(CustomFields::TYPE[0])
			->setValues((new \AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection())
				->add((new \AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel())->setValue($_POST['title']))));

	$lead->setResponsibleUserId($resp);
	$lead->setCustomFieldsValues($cfvs);
	$pip = $_POST['responsible_user'] == 'Пищаева Екатерина' ? Pipelines::STUDY_DIST : Pipelines::STUDY_OCHNO;
	$lead->setPipelineId($pip);
    $lead->setTags((new \AmoCRM\Collections\TagsCollection())->add((new TagModel())
        ->setName(Tags::C1_GROUP['name'])
        ->setId(Tags::C1_GROUP['id'])));
	#endregion
	$lead = $mzpo->apiClient->leads()->addOne($lead);
	file_put_contents(__DIR__.'/1.txt', print_r([$pip, $lead], 1), FILE_APPEND);

}
#endregion
#region Обновление существужющей заявки (только контакты)
else
{

//	$lf = new \AmoCRM\Filters\LinksFilter();
//	$links = $lead->getContacts();
//	foreach ($links as $linc)
//	{
//		$link = $mzpo->apiClient->contacts()->syncOne($linc);
//		dd($link); exit;
//	}


}
#endregion

#region Привязка контактов
$lc = new \AmoCRM\Collections\LinksCollection();
$ar = [];
foreach ($_POST['students'] as $student)
{
	$ids = null;
	$res = null;

	if(count($student['amo_ids']))
	{
		foreach ($student['amo_ids'] as $amo_id)
		{
			if($amo_id['account_id'] == 28395871)
			{
				$ids = $amo_id['entity_id'];
				break;
			}
		}
	}
	if($ids)
	{
		try {
			$res = $mzpo->apiClient->contacts()->getOne($ids);
		}
		catch(Exception $e)
		{

		}

		if($res)
		{
			$res->setUpdatedBy(Users::ADMIN);
			$ar[] = $res;
			$lc->add($res);
			continue;
		}
	}

	if ($student['1c_id'])
	{
		$cf = new \AmoCRM\Filters\ContactsFilter();
		$res = null;
		$cf->setQuery($student['1c_id']);
		try {
			$res = $mzpo->apiClient->contacts()->get($cf);
		}
		catch(Exception $e)
		{
		}

		if ($res)
		{
			$res->first()->setUpdatedBy(Users::ADMIN);

			$ar[] = $res;
			$lc->add($res->first());
			continue;
		}
	}

	$cf = new \AmoCRM\Filters\ContactsFilter();
	$phone = $student['phone'];
	$phone = safePhone($phone);
	$cf->setQuery($phone);
	$res = null;
	try {
		$res = $mzpo->apiClient->contacts()->get($cf);
	}
	catch(Exception $e)
	{

	}
	if($res)
	{
		$res->first()->setUpdatedBy(Users::ADMIN);

		$ar[] = $res;
		$lc->add($res->first());
		continue;
	}


}
try {
	$mzpo->apiClient->leads()->link($lead, $lc);
} catch (\AmoCRM\Exceptions\AmoCRMApiErrorResponseException $e)
{
	file_put_contents(__DIR__.'/er.txt', print_r($e, 1), FILE_APPEND);
	dd($e);
	dd($e->getValidationErrors());

} catch (Exception $e)
{
	dd($e);
}
#endregion


echo $lead->getId(); //возврат Lead_id



