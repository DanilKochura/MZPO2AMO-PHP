<?php

use AmoCRM\Client\AmoCRMApiClient;

require_once '../vendor/autoload.php';
require_once 'config/db.php';

$subdomain = 'mzpoeducationsale'; //Поддомен нужного аккаунта
$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса
$clientSecret = 'KAHuESf38NuVHQ6TxpzaN5eWnbe8TutYO5eo9olYoXAe7xUoYXlHwuYlh4WnFg3R';
$clientId = 'a4fbd30b-b5ae-4ebd-91b1-427f58f0d709';
$redirectUri = 'https://mzpo-s.ru/amo/';
/** Соберем данные для запроса */
$apiClient = new AmoCRMApiClient($clientId, $clientSecret, $redirectUri);



//require_once '../index.php';
session_start();
//require_once 'tokenActions.php';
if (isset($_GET['referer'])) {
	$apiClient->setAccountBaseDomain($_GET['referer']);
}



if (!isset($_GET['code'])) {
	$state = bin2hex(random_bytes(16));
	$_SESSION['oauth2state'] = $state;
	{
		$authorizationUrl = $apiClient->getOAuthClient()->getAuthorizeUrl([
			'state' => $state,
			'mode' => 'post_message',
		]);
		header('Location: ' . $authorizationUrl);
		die;
	}
} elseif (empty($_GET['state']) || empty($_SESSION['oauth2state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
	unset($_SESSION['oauth2state']);
	exit('Invalid state');
}

/**
 * Ловим обратный код
 */
try {
	$accessToken = $apiClient->getOAuthClient()->getAccessTokenByCode($_GET['code']);

	if (!$accessToken->hasExpired()) {
		saveToken([
			'accessToken' => $accessToken->getToken(),
			'refreshToken' => $accessToken->getRefreshToken(),
			'expires' => $accessToken->getExpires(),
			'baseDomain' => $apiClient->getAccountBaseDomain(),
		]);
	}
} catch (Exception $e) {
	die((string)$e);
}
saveToken([
	'accessToken' => $accessToken->getToken(),
	'refreshToken' => $accessToken->getRefreshToken(),
	'expires' => $accessToken->getExpires(),
	'baseDomain' => $apiClient->getAccountBaseDomain(),
]);
$ownerDetails = $apiClient->getOAuthClient()->getResourceOwner($accessToken);

printf('Hello, %s!', $ownerDetails->getName());

echo 'hello';