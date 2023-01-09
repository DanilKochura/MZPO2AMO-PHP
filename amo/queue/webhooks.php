<?php

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
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use reports\EventsReport;
use services\QueueService;

require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/MzpoAmo.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Leads.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Contact.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Log.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/MzposApiEvent.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/CustomFields.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Pipelines.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Tags.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Statuses.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/amo/services/QueueService.php';
require $_SERVER['DOCUMENT_ROOT'] .'/amo/reports/BaseReport.php';
require $_SERVER['DOCUMENT_ROOT'] .'/amo/reports/EventsReport.php';
require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

try {
	$connection = new AMQPStreamConnection(QueueService::HOST, QueueService::PORT, QueueService::USER, QueueService::PASSWORD, QueueService::VHOST);

	$channel = $connection->channel();
	$channel->queue_declare(QueueService::WEBHOOKS, false, true, false, false);

	$callback = function ($msg){
		file_put_contents(__DIR__.'/log2.txt', date('Y-m-d H:i:s').' : '.QueueService::WEBHOOKS.' '.print_r($msg->body, 1).PHP_EOL, FILE_APPEND);
		$post = json_decode($msg->body, true);
		$method = $post['method'];
#region инициализация мероприятий
		if ($method == 'processevents') {
			$id = $post['leads']['add'][0]['id'];
			$status = $post['leads']['add'][0]['status_id'];
			$pipeline = $post['leads']['add'][0]['pipeline_id'];
			#endregion
			if (!$id) {
				Log::writeError(Log::WEBHOOKS, new Exception('No id!'));
			}

			#region получение заявки
			$lead = new Leads([], $id);

			$text = $lead->getCFValue(CustomFields::EVENT_NAME);
			if (!$text) {
				Log::writeError(Log::WEBHOOKS, new Exception('Lead has no Event_name'));

			}
			#endregion

			#region получение данных о мероприятии и перераспределение заявок
			$event = new MzposApiEvent($text);
			$lead->setCFStringValue(CustomFields::EVENT_ADRESS, $event->adress);
			$lead->setCFStringValue(CustomFields::EVENT_NAME, $event->page_name);
			if (!strlen($lead->getCFValue(CustomFields::EVENT_DATETIME))) {
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

			if ($type and $properties[$type]) {
				$lead->setTags($properties[$type]['tags']);
				$lead->setName($properties[$type]['name']);
				$lead->setPipeline($properties[$type]['pipeline']);
				$lead->setStatus($properties[$type]['status']);
			} else {
				Log::writeError(Log::WEBHOOKS, new Exception('No event!'));

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
			$contact = new Contact([], $lead->getContact());
			$report = new EventsReport();
			$report->add(['', '', '', $contact->name, $contact->email, $contact->phone, $lead->getCFValue(CustomFields::TYPE), '', '', $event->alias, date('Y-m-d H:i:s', strtotime($event->datetime)), $contact->getContact()->getId(), $lead->getLead()->getId()]);
			#endregion
			}
			#endregion
			elseif ($method == 'deleteroms') {
			$id = $post['leads']['add'][0]['id'];
			$status = $post['leads']['add'][0]['status_id'];
			$pipeline = $post['leads']['add'][0]['pipeline_id'];
			#endregion
			if (!$id) {
				die('Incorrect Lead Number');
			}
			file_put_contents(__DIR__ . '/0.txt', print_r($post, 1), FILE_APPEND);

			$lead = new Leads($post, $id);
			$lead->deleteTag(Tags::SEMINAR_ROMS);
			$lead->save();
			}
	#region автозамена номера телефона
	elseif ($method == 'editcontact')
	{
		$id = $post['contacts']['add'][0]['id'] ?: $post['contacts']['update'][0]['id'];
		$contact = new \MzpoAmo\Contact([], $id);
		$tel = $contact->getPhone();
		if ($tel)
			if ($tel and ($tel[0] == 8 or $tel[0] == 7)) {
				$tel = ltrim($tel, '8');
				$tel = ltrim($tel, '7');
				$tel = ltrim($tel, '+7');
				$tel = '+7' . $tel;
			} else {
				die();
			}
		$contact->setPhone($tel);
		$contact->save();

	}
	#endregion
		$msg->ack();
	};
	$channel->basic_consume(QueueService::WEBHOOKS, '', false, false, false, false, $callback);

	while($channel->is_open())
	{
		$channel->wait();
	}
	$channel->close();
	$connection->close();
} catch (Exception $e) {
	http_response_code(400);

	file_put_contents(__DIR__.'/cons1.txt', print_r($e, 1).PHP_EOL, FILE_APPEND);
}
