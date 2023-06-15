<?php


use AmoCRM\Exceptions\AmoCRMApiErrorResponseException;
use AmoCRM\Exceptions\AmoCRMApiException;
use League\OAuth2\Client\Token\AccessToken;

define('TOKEN_FILE', DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'token_info.json');

/**
 * @param array $accessToken
 */
function saveToken($accessToken)
{
	if (
		isset($accessToken)
		&& isset($accessToken['accessToken'])
		&& isset($accessToken['refreshToken'])
		&& isset($accessToken['expires'])
		&& isset($accessToken['baseDomain'])
	) {
		$data = [
			'accessToken' => $accessToken['accessToken'],
			'expires' => $accessToken['expires'],
			'refreshToken' => $accessToken['refreshToken'],
			'baseDomain' => $accessToken['baseDomain'],
		];

		file_put_contents(__DIR__.'/010.txt', json_encode($data), FILE_APPEND);
		$token = new TokenDb();
		$token->saveToken($accessToken['accessToken'], $accessToken['refreshToken'], $accessToken['expires'], $accessToken['baseDomain'], '');
	} else {
		exit('Invalid access token ' . var_export($accessToken, true));
	}
}

/**
 * @return AccessToken
 */
function getToken($type)
{
	
	$token = new TokenDb();
	$accessToken = $token->getToken($type);
//	$accessToken = json_decode(file_get_contents(TOKEN_FILE), true);
	if (
		isset($accessToken)
		&& isset($accessToken['accessToken'])
		&& isset($accessToken['refreshToken'])
		&& isset($accessToken['expires'])
		&& isset($accessToken['baseDomain'])
	) {
		return new AccessToken([
			'access_token' => $accessToken['accessToken'],
			'refresh_token' => $accessToken['refreshToken'],
			'expires' => $accessToken['expires'],
			'baseDomain' => $accessToken['baseDomain'],
		]);
	} else {
		exit('Invalid access token ' . var_export($accessToken, true));
	}
}
function printError(AmoCRMApiException $e): void
{
	$errorTitle = $e->getTitle();
	$code = $e->getCode();
	$debugInfo = var_export($e->getLastRequestInfo(), true);

	$validationErrors = null;
	if ($e instanceof AmoCRMApiErrorResponseException) {
		$validationErrors = var_export($e->getValidationErrors(), true);
	}

	$error = <<<EOF
Error: $errorTitle
Code: $code
Debug: $debugInfo
EOF;

	if ($validationErrors !== null) {
		$error .= PHP_EOL . 'Validation-Errors: ' . $validationErrors . PHP_EOL;
	}

	echo '<pre>' . $error . '</pre>';
}

function dd($array)
{
	echo '<pre>';
	print_r($array);
	echo '</pre>';
	exit;

}

function safePhone($phone)
{
	$tel = $phone;
	$tel = ltrim($tel,'+');
	$tel = ltrim($tel, '8');
	$tel = ltrim($tel, '7');

	$tel = preg_replace('~[\( \) \-]+~', '', $tel);
	return $tel;
}