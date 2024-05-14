<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header("Access-Control-Allow-Headers: X-Requested-With");

if(!isset($_GET['id']) or $_GET['key'] != 'fsgF35@ve3')
{
    die('no access');
}
$id = $_GET['id'];
use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\Leads\Pipelines\Statuses\StatusesCollection;
use AmoCRM\Collections\TasksCollection;
use AmoCRM\Filters\BaseRangeFilter;
use AmoCRM\Filters\NotesFilter;
use AmoCRM\Helpers\EntityTypesInterface;
use AmoCRM\Models\Factories\NoteFactory;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\Leads\Pipelines\Statuses\StatusModel;
use AmoCRM\Models\TaskModel;
use GuzzleHttp\Exception\RequestException;
use MzpoAmo\Contact;
use MzpoAmo\CustomFields;
use MzpoAmo\Lead1C;
use MzpoAmo\Leads;
use MzpoAmo\Log;
use MzpoAmo\MzpoAmo;
use MzpoAmo\Statuses;
use Psr\Http\Message\ResponseInterface;

//use services\Integration1C;

require_once '../model/MzpoAmo.php';
require '../services/CoursesServise.php';
require '../services/QueueService.php';
require '../services/UserService.php';
require '../services/Request1C.php';
require '../services/Integartion1C.php';
require '../model/Course.php';
require '../dict/CustomFields.php';
require '../dict/Statuses.php';
require '../dict/Users.php';
require '../model/Log.php';
require '../model/Contact.php';
require '../model/Leads.php';
//require '../config/db.php';
require '../../vendor/autoload.php';
require '../reports/BaseReport.php';
require '../reports/LeadsReport.php';
require '../dict/Pipelines.php';
require '../dict/Tags.php';
require_once '../model/Leads.php';
require_once '../model/Company.php';
require '../model/MzposApiEvent.php';
require '../model/1C/include.php';


$subdomain = 'mzpoeducationsale'; //Поддомен нужного аккаунта
$clientSecret = 'KAHuESf38NuVHQ6TxpzaN5eWnbe8TutYO5eo9olYoXAe7xUoYXlHwuYlh4WnFg3R';
$clientId = 'a4fbd30b-b5ae-4ebd-91b1-427f58f0d709';
$redirectUri = 'https://mzpo-s.ru/amo/';


//$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);
//////var_dump($apiClient); exit;
//$accessToken = getToken($subdomain);
//$apiClient->setAccessToken($accessToken)
//    ->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
//    ->onAccessTokenRefresh(
//        function (\League\OAuth2\Client\Token\AccessTokenInterface $accessToken, $baseDomain) {
//            saveToken(
//                [
//                    'accessToken' => $accessToken->getToken(),
//                    'refreshToken' => $accessToken->getRefreshToken(),
//                    'expires' => $accessToken->getExpires(),
//                    'baseDomain' => $baseDomain,
//                ]
//            );
//        });

$lead = new Leads([], MzpoAmo::SUBDOMAIN, $id);


$lead->setCFStringValue(CustomFields::REMOTE_MAN[0], $_GET['man']);
$lead->save();
file_get_contents('https://api.sensei.plus/start/1IkBv/bmif861?lead_id='.$id);
die($id);


