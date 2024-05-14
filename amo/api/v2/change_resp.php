<?php

use MzpoAmo\Leads;
use MzpoAmo\MzpoAmo;

require_once '../../model/MzpoAmo.php';
require '../../services/CoursesServise.php';
require '../../services/QueueService.php';
require '../../services/UserService.php';
require '../../services/Request1C.php';
require '../../services/Integartion1C.php';
require '../../model/Course.php';
require '../../dict/CustomFields.php';
require '../../dict/Statuses.php';
require '../../dict/Users.php';
require '../../model/Log.php';
require '../../model/Contact.php';
require '../../model/Leads.php';
//require '../../config/db.php';
require '../../../vendor/autoload.php';
require '../../reports/BaseReport.php';
require '../../reports/LeadsReport.php';
require '../../dict/Pipelines.php';
require '../../dict/Tags.php';
require_once '../../model/Leads.php';
require_once '../../model/Company.php';
require '../../model/MzposApiEvent.php';
require '../../model/1C/include.php';




file_put_contents(__DIR__.'/request.txt', print_r($_REQUEST, 1), FILE_APPEND);
file_put_contents(__DIR__.'/post.txt', print_r($_POST, 1), FILE_APPEND);


$id = $_POST['id'];
$uid = $_POST['uid'];
$resp = $_POST['resp'];
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
    die('error');
}

file_get_contents('https://api.sensei.plus/webhook?result=changed&hash='.$_REQUEST['sensei_hash']);


die('changed');