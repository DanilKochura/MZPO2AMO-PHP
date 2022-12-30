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
require '../services/QueueService.php';

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
use reports\EventsReport;
use services\QueueService;

$request =  $_SERVER['REQUEST_URI'];
$method = explode('?', $request)[1];
#region обработка POST
$_POST['method'] = $method;
#endregion

$queue = new QueueService();
$queue->addToQueue(QueueService::WEBHOOKS, json_encode($_POST));
