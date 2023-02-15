<?php

use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Models\CustomFieldsValues\DateTimeCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\DateCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\DateTimeCustomFieldValueModel;
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
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Users.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/amo/services/QueueService.php';
require $_SERVER['DOCUMENT_ROOT'] .'/amo/reports/BaseReport.php';
require $_SERVER['DOCUMENT_ROOT'] .'/amo/reports/EventsReport.php';
require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';

if($_POST)
{

}
$_POST=json_decode(file_get_contents('php://input'), true);

$mzpo = new MzpoAmo();

$lead = new \AmoCRM\Models\LeadModel();
$lead->setName($_POST['title']);
$cfvs = new \AmoCRM\Collections\CustomFieldsValuesCollection();
$cfvs->add(
	(new DateTimeCustomFieldValuesModel())
		->setFieldId(CustomFields::START_STUDY)
		->setValues(
			(new DateCustomFieldValueCollection())
				->add(
					(new DateTimeCustomFieldValueModel())
						->setValue($_POST['date_start'])
				)
		)
);
$lead->setCustomFieldsValues($cfvs);
$lead->setPipelineId(Pipelines::TEST_DANIL);
$lead = $mzpo->apiClient->leads()->addOne($lead);
$lc = new \AmoCRM\Collections\LinksCollection();
foreach($_POST['students'] as $ss)
{
	$lc->add($mzpo->apiClient->contacts()->getOne($ss));
}
$mzpo->apiClient->leads()->link($lead, $lc);


dd($lead);