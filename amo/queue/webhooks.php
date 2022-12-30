<?php

use MzpoAmo\Contact;
use MzpoAmo\Leads;
use MzpoAmo\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
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
require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

try {
	$connection = new AMQPStreamConnection(QueueService::HOST, QueueService::PORT, QueueService::USER, QueueService::PASSWORD, QueueService::VHOST);

	$channel = $connection->channel();
	$channel->queue_declare(QueueService::WEBHOOKS, false, true, false, false);

	$callback = function ($msg){
		file_put_contents(__DIR__.'/log2.txt', date('Y-m-d H:i:s').' : '.QueueService::WEBHOOKS.' '.print_r($msg->body, 1).PHP_EOL, FILE_APPEND);
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
