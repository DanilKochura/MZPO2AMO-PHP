<?php

require '../config/helpers.php';
require '../config/db.php';

$subdomain = 'mzpoeducation'; //Поддомен нужного аккаунта
$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

/** Соберем данные для запроса */
$data = [
	'client_id' => '8649a1dc-ba5d-4907-9526-8be7aaa039e3',
	'client_secret' => 'L71rgUk4prxwmDV87xTE8xaf6tEHBaE85LATKUgeaSGhRXIQiG4hmBd8Eh4ak3fD',
	'grant_type' => 'authorization_code',
	'code' => 'def5020005bef8e04632fa7129c1f600f476ea384aa0277e75a8847e53bd3d33de8a8789355c47bd3b7048e1c53e844b4665cc160493631b13c52da612d04ca85b1598b4802f9ef25cdc3d0a7f45c31f65af19075a4092ff5ed0010baf41b89a2f10044579a3567c655ae97b39e5dd8494e362f86728fbeece43dcf5f387ff9fb648935d5b901b28419e9da1b664f2ec4f962f386e9bd39ca204bb3112def36d047daef2bb310e1cc145f50efb5c0a56afede0b4594bb55c7bffec21e98cbf1bfd833bc1728182e20d4980bf7a637ec52eea451c3ad86fcce609f61d588d265ff9b85e76270ee5447e8ab29fb298f4e71c337b2b9acccede8c3f0994b3ee7b758918032e47002931e192786088aff3040544951ed0ce705daf45c5e025ac34d153421800e4564826acd0b6ca5cfe5800e23f0b7b25e982011237e770f0bfc4e90fa63c120f2cf946ab7b8b5b14862597df788236524e54eca6581f7c3cfc17606e3d5731db2dc00384d64023e131a96b9969b75b2c71ae165f84902be5a3c9d1d970b64ea130f21655e7cc649c01af484e0c4fe602b0d071e4b60cb1b82aab14a22cba8e551697169e8000dc47315e7ba5327a619c08a0267020de0b17b84322d9519f5a6b865862d339f5e7cae0cfcf84aac6c4d602fd32510690b259f267f9c9e72ef9cc81fa8d45bf6ad9977fd3518b5aa2fb',
	'redirect_uri' => 'https://mzpo-s.ru/amo/mainhook.php',
];

/**
 * Нам необходимо инициировать запрос к серверу.
 * Воспользуемся библиотекой cURL (поставляется в составе PHP).
 * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
 */
$curl = curl_init(); //Сохраняем дескриптор сеанса cURL
/** Устанавливаем необходимые опции для сеанса cURL  */
curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
curl_setopt($curl,CURLOPT_URL, $link);
curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
curl_setopt($curl,CURLOPT_HEADER, false);
curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
$out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);
/** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
$code = (int)$code;
$errors = [
	400 => 'Bad request',
	401 => 'Unauthorized',
	403 => 'Forbidden',
	404 => 'Not found',
	500 => 'Internal server error',
	502 => 'Bad gateway',
	503 => 'Service unavailable',
];

try
{
	/** Если код ответа не успешный - возвращаем сообщение об ошибке  */
	if ($code < 200 || $code > 204) {
		throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
	}
}
catch(\Exception $e)
{
	die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
}

/**
 * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
 * нам придётся перевести ответ в формат, понятный PHP
 */
$response = json_decode($out, true);

$access_token = $response['access_token']; //Access токен
$refresh_token = $response['refresh_token']; //Refresh токен
$token_type = $response['token_type']; //Тип токена
$expires_in = $response['expires_in']; //Через сколько действие токена истекает

saveToken(
	[
		'accessToken' => $access_token,
		'refreshToken' => $refresh_token,
		'expires' => $expires_in,
		'baseDomain' => 'mzpoeducation.amocrm.ru'
	]
);
file_put_contents(__DIR__.'/0.txt', print_r([$access_token, $refresh_token, $token_type, $expires_in], 1), FILE_APPEND);