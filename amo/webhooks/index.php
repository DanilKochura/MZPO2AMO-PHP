<?php
require '../model/MzpoAmo.php';
require '../model/Leads.php';
require '../reports/ReportBase.php';
require '../reports/EventsReport.php';
require '../model/Contact.php';
require '../dict/CustomFields.php';
require '../model/MzposApiEvent.php';
require '../dict/Tags.php';
require '../dict/Pipelines.php';
require '../dict/Statuses.php';
require '../model/Log.php';

use AmoCRM\Exceptions\AmoCRMApiException;
use Carbon\Carbon;
use MzpoAmo\Contact;
use MzpoAmo\CustomFields;
use MzpoAmo\Leads;
use MzpoAmo\Log;
use MzpoAmo\MzposApiEvent;
use MzpoAmo\Pipelines;
use MzpoAmo\Statuses;
use MzpoAmo\Tags;
use reports\EventsReport;

$request =  $_SERVER['REQUEST_URI'];
$method = explode('?', $request)[1];
#region обработка POST


#region инициализация мероприятий
if($method=='processevents')
{
	$id = $_POST['leads']['add'][0]['id'];
	$status = $_POST['leads']['add'][0]['status_id'];
	$pipeline = $_POST['leads']['add'][0]['pipeline_id'];
#endregion
	if(!$id)
	{
		die('Incorrect Lead Number');
	}

	#region получение заявки
	$lead = new Leads($_POST, $id);

	$text = $lead->getCFValue(CustomFields::EVENT_NAME);
	if(!$text)
	{
		die('Lead has no Event_name');
	}
	#endregion

	#region получение данных о мероприятии и перераспределение заявок
	$event = new MzposApiEvent($text);
	$lead->setCFStringValue(CustomFields::EVENT_ADRESS, $event->adress);
	$lead->setCFStringValue(CustomFields::EVENT_NAME, $event->page_name);
	if(!strlen($lead->getCFValue(CustomFields::EVENT_DATETIME)))
	{
		$lead->setCFDateTimeValue(CustomFields::EVENT_DATETIME, Carbon::parse($event->datetime));
	}

	$type = $event->getType();

	$properties = [
		MzposApiEvent::DOD =>
		[
			'name' => 'dod',
			'tags' => [Tags::DOD],
			'pipeline' => Pipelines::FREE_EVENTS,
			'status' => Statuses::SENT_NOTIFICATION_EVENTS_FREE
		],
		MzposApiEvent::MKV =>
			[
				'name' => "МКБ",
				'tags' => [Tags::MKV],
				'pipeline' => Pipelines::FREE_EVENTS,
				'status' => Statuses::SENT_NOTIFICATION_EVENTS_FREE
			],
		MzposApiEvent::OPEN_LESSON =>
			[
				'name' => "Пробный урок",
				'tags' => [Tags::OPEN_LESSON],
				'pipeline' => Pipelines::OPEN_LESSON,
				'status' => Statuses::SIGN_UP_OPEN_LESSON
			],
			MzposApiEvent::MORIZO =>
			[
				'name' => "Morizo",
				'tags' => [Tags::MORIZO],
				'pipeline' => Pipelines::FREE_EVENTS,
				'status' => Statuses::SENT_NOTIFICATION_EVENTS_FREE
			],
		MzposApiEvent::STYX =>
			[
				'name' => "STYX",
				'tags' => [Tags::STYX],
				'pipeline' => Pipelines::FREE_EVENTS,
				'status' => Statuses::SENT_NOTIFICATION_EVENTS_FREE
			],
	]; // поля в зависимости от типа мероприятия
	if($type and $properties[$type])
	{
		$lead->setTags($properties[$type]['tags']);
		$lead->setName($properties[$type]['name']);
		$lead->setPipeline($properties[$type]['pipeline']);
		$lead->setStatus($properties[$type]['status']);
	} else{
		die('No eventt');
	}
	$lead->newSystemMessage('Worked!');
	#endregion
	#region сохранение заявки
	try {
		$lead->save();
	} catch (AmoCRMApiException $e) {
		Log::writeError($e);
	}
	#endregion

	#region запись в таблицу с отчетом
	$contact = new Contact([],$lead->getContact());
	$report = new EventsReport();
	$report->add(['','','', $contact->name, $contact->email, $contact->phone, $lead->getCFValue(CustomFields::TYPE), '', '', '']);
	#endregion

}
#endregion
elseif ($method == 'deleteroms')
{
	$id = $_POST['leads']['add'][0]['id'];
	$status = $_POST['leads']['add'][0]['status_id'];
	$pipeline = $_POST['leads']['add'][0]['pipeline_id'];
#endregion
	if(!$id)
	{
		die('Incorrect Lead Number');
	}
	file_put_contents(__DIR__.'/0.txt', print_r($_POST, 1), FILE_APPEND);

	$lead = new Leads($_POST, $id);
	$lead->deleteTag(Tags::SEMINAR_ROMS);
	$lead->save();
	die('success');
}
#region автозамена номера телефона
elseif ($method == 'editcontact')
{
	$id = $_POST['contacts']['add'][0]['id'] ?: $_POST['contacts']['update'][0]['id'];
	$contact = new \MzpoAmo\Contact([], $id);
	$tel = $contact->getPhone();
	if($tel and ($tel[0] == 8 or $tel[0] == 7))
	{
		$tel = ltrim($tel, '8');
		$tel = ltrim($tel, '7');
		$tel = '+7'.$tel;
	} else
	{
		die();
	}
	$contact->setPhone($tel);
	$contact->save();

}
#endregion