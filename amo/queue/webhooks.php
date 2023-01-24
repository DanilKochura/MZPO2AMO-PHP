<?php

use AmoCRM\Exceptions\AmoCRMApiException;
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
	#region создание соединения и слушателя очереди
	$connection = new AMQPStreamConnection(QueueService::HOST, QueueService::PORT, QueueService::USER, QueueService::PASSWORD, QueueService::VHOST);

	$channel = $connection->channel();
	$channel->queue_declare(QueueService::WEBHOOKS, false, true, false, false);
	#endregion

	#region главный callback-бработчик
	$callback = function ($msg){
		$post = json_decode($msg->body, true);

		$method = $post['method'];
		Log::writeLine(Log::WEBHOOKS, $method);

		#region Инициализация мероприятий
		if ($method == 'processevents') {
			#region обработка запроса
			$id = $post['leads']['add'][0]['id'];
			$status = $post['leads']['add'][0]['status_id'];
			$pipeline = $post['leads']['add'][0]['pipeline_id'];
			if (!$id) {
				Log::writeError(Log::WEBHOOKS, new Exception('No id!'));
			}
			#endregion

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

		#region Удаление тега "Семинар РОМС" - костыль на время
		elseif ($method == 'deleteroms') {
			$msg->ack();
			DIE();
			#region обработка запроса
			$id = $post['leads']['add'][0]['id'];
			$status = $post['leads']['add'][0]['status_id'];
			$pipeline = $post['leads']['add'][0]['pipeline_id'];
			if (!$id) {
				die('Incorrect Lead Number');
			}
			#endregion


			$lead = new Leads($post, $id);
			$lead->deleteTag(Tags::SEMINAR_ROMS);
			$lead->save();
		}
		#endregion

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

		#https://www.mzpo-s.ru/amo/webhooks/?ret2corp
		#region перевод из розницы в корпорат
		elseif ($method == 'ret2corp')
			{
				#region
				$id = $post['leads']['add'][0]['id'] ?: ( $post['leads']['update'][0]['id'] ?: $post['leads']['status'][0]['id']);
				Log::writeLine(Log::WEBHOOKS, 'Сделка: '.$id);

				$lead = new Leads([], MzpoAmo::SUBDOMAIN, $id);
				file_put_contents(__DIR__.'/0.txt', print_r($lead->getLead(), 1), FILE_APPEND);
				if(!$lead->getLead())
				{
					$msg->ack();
					return;
				}
				$contact = new Contact([], MzpoAmo::SUBDOMAIN, $lead->getContact());

				$contact = Contact::clone($contact);

				Log::writeLine(Log::WEBHOOKS, 'Контакт склонирован: '.$contact->getContact()->getId());
				$leadCorp = Leads::clone($lead, $contact->hasMergableLead());
				Log::writeLine(Log::WEBHOOKS, 'Лид склонирован: '.$leadCorp->getId());

				$lead->setStatus(Statuses::SEND_TO_CORP);
				$lead->save();

				Log::writeLine(Log::WEBHOOKS, 'Этап изменен');

				$contact->linkLead($leadCorp);
				#endregion

		}
		#endregion

		# https://www.mzpo-s.ru/amo/webhooks/?corp2ret
		#region успешно реализовано - корпорат
		elseif ($method == 'corp2ret')
		{
			$id = $post['leads']['add'][0]['id'] ?: ( $post['leads']['update'][0]['id'] ?: $post['leads']['status'][0]['id']);
			Log::writeLine(Log::WEBHOOKS, 'Сделка: '.$id);
			$leadCorp = new Leads([], MzpoAmo::SUBDOMAIN_CORP, $id);
			$id_ret = $leadCorp->getCFValue(CustomFields::RET_ID[1]);
			$id_ret1 = $leadCorp->getCFValue(CustomFields::ID_LEAD_RET[1]);
			if(!$id_ret)
			{
				Log::writeLine(Log::WEBHOOKS, 'Сделка в рознице не найдена!');
				$id_ret = $id_ret1;
			}
			Log::writeLine(Log::WEBHOOKS, 'Сделка в рознице: '.$id_ret);

			$lead = new Leads([], MzpoAmo::SUBDOMAIN, $id_ret);
			if(!$lead->getLead())
			{
				$id_ret = $leadCorp->getCFValue(CustomFields::ID_LEAD_RET[1]);
				Log::writeLine(Log::WEBHOOKS, 'Сделка в рознице (по второму полю): '.$id_ret);
				$lead = new Leads([], MzpoAmo::SUBDOMAIN, $id_ret);
				if(!$lead->getLead()) {
					Log::writeLine(Log::WEBHOOKS, 'Сделка в рознице не существует!');
					$msg->ack();
					die();
				}
			}
			if($price = $leadCorp->getPrice())
			{
				$lead->setPrice($price);
			}
			$lead->setStatus(Statuses::SUCCESS_CORP_PIPE);
			$lead->save();
			Log::writeLine(Log::WEBHOOKS, 'Сделка сохранена!');

		}
		#endregion

		# https://www.mzpo-s.ru/amo/webhooks/?corp2ret_fail
		#region закрыто и не реализовано - корпорат
		elseif ($method == 'corp2ret_fail')
		{
			$id = $post['leads']['add'][0]['id'] ?: ( $post['leads']['update'][0]['id'] ?: $post['leads']['status'][0]['id']);
			Log::writeLine(Log::WEBHOOKS, 'Сделка: '.$id);
			$leadCorp = new Leads([], MzpoAmo::SUBDOMAIN_CORP, $id);
			$id_ret = $leadCorp->getCFValue(CustomFields::RET_ID[1]);
			$id_ret1 = $leadCorp->getCFValue(CustomFields::ID_LEAD_RET[1]);

				if(!$id_ret)
				{
					Log::writeLine(Log::WEBHOOKS, 'Сделка в рознице не найдена!');
					$id_ret = $id_ret1;
				}


			Log::writeLine(Log::WEBHOOKS, 'Сделка в рознице: '.$id_ret);

			$lead = new Leads([], MzpoAmo::SUBDOMAIN, $id_ret);
			if(!$lead->getLead())
			{
				if(!$id_ret1)
				{
					Log::writeLine(Log::WEBHOOKS, 'Сделка в рознице не найдена');
					$msg->ack();
					die();
				}
				Log::writeLine(Log::WEBHOOKS, 'Сделка в рознице (по второму полю): '.$id_ret);
				$lead = new Leads([], MzpoAmo::SUBDOMAIN, $id_ret);
				if(!$lead->getLead()) {
					Log::writeLine(Log::WEBHOOKS, 'Сделка в рознице не существует!');
					$msg->ack();
					die();
				}
			}
			if($price = $leadCorp->getPrice())
			{
				$lead->setPrice($price);
			}

			$lead->setStatus(Statuses::FAIL_CORP_PIPE);
			$lead->save();
			Log::writeLine(Log::WEBHOOKS, 'Сделка сохранена!');

		}
		#endregion


		$msg->ack();
	};
	#endregion

	$channel->basic_consume(QueueService::WEBHOOKS, '', false, false, false, false, $callback);

	while($channel->is_open())
	{
		$channel->wait();
	}
	$channel->close();
	$connection->close();
} catch (Exception $e) {
	http_response_code(400);

	file_put_contents(__DIR__.'/cons1.txt', print_r($e, 1).PHP_EOL);
}

//https://www.mzpo-s.ru/amo/webhooks/?ret2corp
//https://mzpo2amo.ru/wh/ret2corp/send