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
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Company.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Log.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/MzposApiEvent.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/CustomFields.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Pipelines.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Tags.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Statuses.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/dict/Users.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/amo/services/QueueService.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/amo/services/Request1C.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/amo/services/Integartion1C.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/amo/services/UserService.php';
require $_SERVER['DOCUMENT_ROOT'] .'/amo/reports/BaseReport.php';
require $_SERVER['DOCUMENT_ROOT'] .'/amo/reports/EventsReport.php';
require $_SERVER['DOCUMENT_ROOT'].'/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/1C/Base1CInterface.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/1C/Contact1C.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/1C/Company1C.php';
require $_SERVER['DOCUMENT_ROOT'].'/amo/model/1C/Lead1C.php';


try {
	#region создание соединения и слушателя очереди
	$connection = new AMQPStreamConnection(QueueService::HOST, QueueService::PORT, QueueService::USER, QueueService::PASSWORD, QueueService::VHOST);

	$channel = $connection->channel();
	$channel->queue_declare(QueueService::INTEGRATION_1C, false, true, false, false);
	#endregion

	#region главный callback-бработчик
	$callback = function ($msg){
		$post = json_decode($msg->body, true);
        file_put_contents(__DIR__.'/LLOGLOG.txt', print_r($post, 1), FILE_APPEND);
		$method = $post['method'];
//		Log::writeLine(Log::WEBHOOKS, print_r($post, 1));
//		Log::writeLine(Log::C1, print_r($post, 1));
//		file_put_contents(__DIR__.'/0.txt', print_r($post, 1), FILE_APPEND);
		#region Инициализация мероприятий
		if ($method == 'physics_corp') {
			#region обработка запроса
			$id = $post['leads']['add'][0]['id'] ?: $post['leads']['status'][0]['id'];
			$status = $post['leads']['add'][0]['status_id'];
			$pipeline = $post['leads']['add'][0]['pipeline_id'];
			if (!$id) {
				Log::writeError(Log::WEBHOOKS, new Exception('No id!'));
			}
			#endregion

			$lead = new Leads([], MzpoAmo::SUBDOMAIN_CORP, $id, [\AmoCRM\Models\LeadModel::CATALOG_ELEMENTS, \AmoCRM\Models\LeadModel::CONTACTS]);
//			Log::write(Log::C1, $lead->getLead());

			$c1 = new \services\Integartion1C();
			$c1->sendLead($lead);

		}
		#endregion

        elseif ($method == 'savelead') {
            try {
                $id = $post['leads']['add'][0]['id'] ?: ( $post['leads']['update'][0]['id'] ?: $post['leads']['status'][0]['id']);
                $lead = new Leads([], MzpoAmo::SUBDOMAIN, $id);
                $lead->setNoteSave("Производится перевод сделки. Пожалуйста подождите.");
                $is = new \services\Integartion1C();
                $is->sendLead($lead);
            } catch (Exception $e)
            {
                file_put_contents(__DIR__.'/errors.txt', print_r($e, 1), FILE_APPEND);
                $msg->ack();
                $lead->setNoteSave("Произошла ошибка");
                $lead->addAdminCheckTask();
                die();

//                throw $e;
            }
        }

        elseif ($method == 'change_resp') {
            $id = $post['id'];
            $uid = $post['uid'];
            $resp = $post['resp'];
            file_put_contents(__DIR__.'/positduid.txt', print_r([$id, $uid, $resp], 1), FILE_APPEND);
            try {
                $lead = new Leads([], MzpoAmo::SUBDOMAIN, $id);
                $lead->setNoteSave("Производится смена ответственного в 1с. Пожалуйста подождите.");
                $req = new \services\Request1C();

                file_put_contents(__DIR__.'/log.json', json_encode(['data' => [ 'contract' => [
                    'action' => 'changeotv',
                    'contract_uid' => $uid,
                    'otv' => $resp
                ]]]));

                $data = $req->request('POST', 'lk_editdata', ['data' => [ 'contract' => [
                    'action' => 'changeotv',
                    'contract_uid' => $uid,
                    'otv' => $resp
                ]]]);
                $lead->setNoteSave("Ответственный в сделке успешно изменен!");
                file_put_contents(__DIR__.'/data.txt', print_r($data, 1), FILE_APPEND);
            } catch (Exception $e)
            {
                $lead->setNoteSave("Произошла ошибка");
                $lead->addAdminCheckTask();
                file_put_contents(__DIR__.'/errors.txt', print_r($e, 1), FILE_APPEND);
                $msg->ack();
                die();
            }
        }



		$msg->ack();
        die();
	};
	#endregion

	$channel->basic_consume(QueueService::INTEGRATION_1C, '', false, false, false, false, $callback);

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