<?php

use MzpoAmo\Contact;
use MzpoAmo\Leads;
use MzpoAmo\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/MzpoAmo.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Leads.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Contact.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Log.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/CustomFields.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Pipelines.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Tags.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Statuses.php';
require '../vendor/autoload.php';


try {
	$connection = new AMQPStreamConnection('hawk.rmq.cloudamqp.com', 5672, 'rakadlur', 'UdRmPQ_HbpDYn7HDJqLzeYewd42sakW3', 'rakadlur');

	$channel = $connection->channel();
	$channel->queue_declare('amo-test', false, true, false, false);

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
		} catch (Exception $e)
		{
			http_response_code(400);

			file_put_contents(__DIR__.'/errors.txt', print_r($e, 1), FILE_APPEND);
		}

		$msg->ack();
	};
	$channel->basic_consume('amo-test', '', false, false, false, false, $callback);

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
http_response_code(400);

echo 'ended';