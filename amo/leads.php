<?php

use MzpoAmo\Contact;
use MzpoAmo\Leads;
use MzpoAmo\Log;
use MzpoAmo\MzpoAmo;
use MzpoAmo\Pipelines;
use MzpoAmo\Tags;
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
$post = $_POST;
Log::writeLine(Log::LEAD, print_r($post,1));
$amo = $post['amo'] == 'corp' ? MzpoAmo::SUBDOMAIN_CORP : MzpoAmo::SUBDOMAIN;
Log::writeLine(Log::LEAD, '1');

$contact = new Contact($post, $amo); //создаем контакт #1

$events = (bool)$post['events'];



if($contact->hasMergableLead()) //если есть сделка, которую можно склеить #2
{
	$base = new Leads($post, $amo, $contact->hasMergableLead());
}
else
{
	$base = new Leads($post, $amo);

}
try {
	$base->setTags(Leads::getPostTags($post['form_name_site']));
	$base->save();
} catch (Exception $e)
{
	Log::writeError(Log::LEAD, $e);
}
Log::writeLine(Log::LEAD, 'Taged');

if($post['comment'])
{
	$note = $base->newNote($post['comment']); #6
}
Log::writeLine(Log::LEAD, '3');

if($base and $contact and $contact->linkLead($base->getLead()))
{
	echo 'ok';
}
Log::writeLine(Log::LEAD, '3');
