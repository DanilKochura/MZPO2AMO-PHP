<?php
require 'config/db.php';
require 'config/helpers.php';
$subdomain = 'mzpoeducation'; //Поддомен нужного аккаунта
$link = 'https://' . $subdomain . '.amocrm.ru/oauth2/access_token'; //Формируем URL для запроса

/** Соберем данные для запроса */
$data = [
	'client_id' => 'e48269b8-aca1-4ebd-8809-420d71f57522',
	'client_secret' => 'PpfKPVKEoND3MBHh7fjLSQIcYBNaZetCmVkUXU9VMLI02ynoGJl1SJ4e4YStYust',
	'grant_type' => 'authorization_code',
	'code' => 'def502007c6ce524148c0928f07d201c769f26f66e6f8ec7d1db51a5ddb2155f6236361dbd57b2239d197b40531c1c049ebfda6769b5d7822fa6444d6224158e752c47e0f1adcca594bfbdb92ce79a81340e518b66a842ce440884d48aa5a34d525198adc9096ba2c0d67544dc5c26a1e36a732c20d0189b81844091de436c1678c3acc1487f76762e2ecce5f9c3d8c87904ae853ecd5c592e153ec5d631dafe3c0279c2b6860a32f74b43a6298319d744ef14bbb495173a59c5ca588c5c09d1efe6cd65dbe4d1d976b42600eb4eda137990683ddd389297449a321bcc996796bc4d5f5cb5f6c7a5331182da5089b9f45d2d65761a746be6b1f6b1b1e9e6f6ada8d5a5ed03a85a4c57692b748f839b1c9f4becc199dc246a725d52f61d05b1596f1623fa224c0d9f7253587b61111d50a5e45ccdc8ea23e3dbd4f316271a31479c019b147fc7b2e855144e56d02feef9e8d96353bbe2d6d5ae5a88317e3f4395388b0ed037c339b418db5dc541a79860f676131a42695ebb0960e49c86a2fbe493bae291117732070cf44289c29569380268ee968085176fd6baa1b7d1f9b0a8753bad6ce67690c30a8ceac455fdb0e62f9ef15656cce3830a4f4ade764ca39bc5ede87336db6758c684fa1616863fb705f1265e6884482a21b9542d485d86cbb30d0357fc',
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
catch(Exception $e)
{
	die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
}

/**
 * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
 * нам придётся перевести ответ в формат, понятный PHP
 */
$response = json_decode($out, true);
file_put_contents(__DIR__.'/0.txt', print_r($response, 1), FILE_APPEND);
$access_token = $response['access_token']; //Access токен
$refresh_token = $response['refresh_token']; //Refresh токен
$token_type = $response['token_type']; //Тип токена
$expires_in = $response['expires_in']; //Через сколько действие токена истекает
saveToken([
	'accessToken' => $access_token,
	'refreshToken' => $refresh_token,
	'expires' => $expires_in,
	'baseDomain' => $subdomain,
]);