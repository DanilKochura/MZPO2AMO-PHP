<?php
use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Models\LeadModel;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use MzpoAmo\Contact;
use MzpoAmo\CustomFields;
use MzpoAmo\Leads;
use MzpoAmo\MzpoAmo;
use Psr\Http\Message\ResponseInterface;

//use services\Integration1C;

require '../model/MzpoAmo.php';
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
//require 'config/db.php';
require '../../vendor/autoload.php';
require '../reports/BaseReport.php';
require '../reports/LeadsReport.php';
require '../dict/Pipelines.php';
require '../dict/Tags.php';




require_once '../model/Leads.php';
require_once '../model/Company.php';
require '../model/MzposApiEvent.php';
require '../model/1C/include.php';

//for($i = 0; $i<60; $i++)
//{
//	file_put_contents(__DIR__.'/0.txt', date('H-i-s').': '.$i.PHP_EOL, FILE_APPEND);
//	sleep(10);
//}
//$subdomain = 'mzpoeducationsale'; //Поддомен нужного аккаунта
//$clientSecret = 'KAHuESf38NuVHQ6TxpzaN5eWnbe8TutYO5eo9olYoXAe7xUoYXlHwuYlh4WnFg3R';
//$clientId = 'a4fbd30b-b5ae-4ebd-91b1-427f58f0d709';
//$redirectUri = 'https://mzpo-s.ru/amo/';
/////** Соберем данные для запроса */
////
//
//
//$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);
//////var_dump($apiClient); exit;
//$accessToken = getToken($subdomain);
//$apiClient->setAccessToken($accessToken)
//	->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
//	->onAccessTokenRefresh(
//		function (\League\OAuth2\Client\Token\AccessTokenInterface $accessToken, $baseDomain) {
//			saveToken(
//				[
//					'accessToken' => $accessToken->getToken(),
//					'refreshToken' => $accessToken->getRefreshToken(),
//					'expires' => $accessToken->getExpires(),
//					'baseDomain' => $baseDomain,
//				]
//			);
//		});

$curl = new CurlMultiHandler;
$handler = HandlerStack::create($curl);
$subdomain = 'mzpoeducation'; //Поддомен нужного аккаунта
$clientSecret = 'PpfKPVKEoND3MBHh7fjLSQIcYBNaZetCmVkUXU9VMLI02ynoGJl1SJ4e4YStYust';
$clientId = 'e48269b8-aca1-4ebd-8809-420d71f57522';
$redirectUri = 'https://mzpo-s.ru/amo/mainhook.php';
$apiClient1 = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);
////var_dump($apiClient); exit;
$accessToken = getToken($subdomain);
$apiClient1->setAccessToken($accessToken)
	->setAccountBaseDomain($accessToken->getValues()['baseDomain'])
	->onAccessTokenRefresh(
		function (\League\OAuth2\Client\Token\AccessTokenInterface $accessToken, $baseDomain) {
			saveToken(
				[
					'accessToken' => $accessToken->getToken(),
					'refreshToken' => $accessToken->getRefreshToken(),
					'expires' => $accessToken->getExpires(),
					'baseDomain' => $baseDomain,
				]
			);
		});
#region
echo 'sucs';
if(!$_POST)
{
	$_POST=json_decode(file_get_contents('php://input'), true);
}

//file_put_contents(__DIR__.'/0.txt', print_r($_POST, 1), FILE_APPEND);
$id = $_POST['id'];
$inn = $_POST['inn'];

$cl = new \GuzzleHttp\Client();
$promise = $cl->post('https://egrul.itsoft.ru/'.$inn.'.json');
$resp = json_decode($promise->getBody(), 1);
file_put_contents(__DIR__.'/0.txt', print_r($resp, 1), FILE_APPEND);
if($resp['СвЮЛ']) $resp = $resp['СвЮЛ'];
elseif ($resp['СвИП']) $resp = $resp['СвИП'];
$tmp = $resp['СвАдресЮЛ'];

$index = $tmp['АдресРФ']['@attributes']['Индекс'] ?: $tmp['СвАдрЮЛФИАС']['@attributes']['Индекс'];
$city = $tmp['АдресРФ']['Регион']['@attributes']['НаимРегион'] ?: $tmp['СвАдрЮЛФИАС']['НаимРегион'];
$str =  $tmp['АдресРФ']['Улица']['@attributes']['НаимУлица'] ?: $tmp['СвАдрЮЛФИАС']['ЭлУлДорСети']['@attributes']['Наим'];
$house = $tmp['АдресРФ']['@attributes']['Дом'] ?: $tmp['СвАдрЮЛФИАС']['Здание']['@attributes']['Номер'];

$address = $index.', '.$city.', '.$str.' '.$house;

$name = $resp['СвНаимЮЛ']['СвНаимЮЛСокр']['@attributes']['НаимСокр'] ?: $resp['СвНаимЮЛ']['@attributes']['НаимЮЛСокр'];

$arr = [
	CustomFields::OGRN[1] => $resp['@attributes']['ОГРН'],
	CustomFields::KPP[1] => $resp['@attributes']['КПП'],
	CustomFields::EMAIL[1] =>  $resp['СвАдрЭлПочты']['@attributes']['E-mail'],
	CustomFields::ADDRESS[1] => $address,
];
global $id;
$lead = new \MzpoAmo\Company((int)$id, MzpoAmo::SUBDOMAIN_CORP);
$lead->setName($name);
foreach ($arr as $key=>$item) {
	if(!$lead->getCFValue($key))
	{
		$lead->setCFStringValue($key, $item);
	}
}
$lead->save();



//fun/$promise->then(
//	function (ResponseInterface $res) {
//
//		foreach ($arr as $key=>$item) {
//			if(!$lead->getCFValue($key))
//			{
//				$lead->setCFStringValue($key, $item);
//			}
//		}
//		$lead->save();
//	},
//	function (RequestException $e) {
//		echo $e->getMessage() . "\n";
//		echo $e->getRequest()->getMethod();
//	}
//);