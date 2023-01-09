<?php

use MzpoAmo\Contact;
use MzpoAmo\Leads;
use MzpoAmo\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use reports\LeadsReport;
use services\QueueService;

require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/MzpoAmo.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Leads.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Contact.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Log.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/CustomFields.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Pipelines.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Tags.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Statuses.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/amo/services/QueueService.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/amo/reports/BaseReport.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/amo/reports/LeadsReport.php';
require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';


try {
	$connection = new AMQPStreamConnection(QueueService::HOST, QueueService::PORT, QueueService::USER, QueueService::PASSWORD, QueueService::VHOST);

	$channel = $connection->channel();
	$channel->queue_declare(QueueService::LEADS, false, true, false, false);

	$callback = function ($msg){
		file_put_contents(__DIR__.'/log.txt', date('Y-m-d H:i:s').' : '.print_r($msg->body, 1).PHP_EOL, FILE_APPEND);

		try{
			$post = json_decode($msg->body, true);
			$contact = new Contact($post); //создаем контакт
			if($contact->hasMergableLead()) //если есть сделка, которую можно склеить
			{
				$base = new Leads($post, $contact->hasMergableLead());
			}
			else
			{
				$base = new Leads($post);
			}
			if($post['comment'])
			{
				$note = $base->newNote($post['comment']);
			}
			if($base and $contact and $contact->linkLead($base->getLead()))
			{
				echo 'ok';
			}
			$report = new LeadsReport();
			$report->add([$post['site'], date('Y-m-d H:i:s'), json_encode($post), $base->getLead()->getId(), $contact->getContact()->getId()]);

		} catch (Exception $e)
		{
			http_response_code(400);

			file_put_contents(__DIR__.'/errors.txt', print_r($e, 1), FILE_APPEND);
		}

		$msg->ack();
	};
	$channel->basic_consume(QueueService::LEADS, '', false, false, false, false, $callback);

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
