<?php

use MzpoAmo\Contact;
use MzpoAmo\Leads;
use MzpoAmo\Log;
use MzpoAmo\MzpoAmo;
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
file_put_contents(__DIR__.'/0.txt', print_r($_POST, 1));

if($_POST['method'] == 'siteform')
{
	$_POST['site'] = ltrim($_POST['site'], 'www.');

	$_POST['pipeline'] = PIPELINE; ///
	///
	try{
		$connection = new AMQPStreamConnection('hawk.rmq.cloudamqp.com', 5672, 'rakadlur', 'UdRmPQ_HbpDYn7HDJqLzeYewd42sakW3', 'rakadlur');
		$channel = $connection->channel();
		$channel->queue_declare('amo-test', false, true, false, false);

		$msg = new AMQPMessage(json_encode($_POST));
		$channel->basic_publish($msg, '', 'amo-test');

		$channel->close();
		$connection->close();
	} catch (Exception $e) {
		dd($e);
	}
	die('Ok');

} else{
	http_response_code(404);
	die('no post');
}

?>


