<?php

require '../services/QueueService.php';
require '../../vendor/autoload.php';
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
file_put_contents(__DIR__.'/0.txt', print_r($_POST, 1), FILE_APPEND);

$queue = new QueueService();
$queue->addToQueue(QueueService::WEBHOOKS, json_encode($_POST));
