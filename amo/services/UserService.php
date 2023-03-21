<?php

namespace services;

use MzpoAmo\MzpoAmo;

class UserService extends \MzpoAmo\MzpoAmo
{
	public function __construct($type = MzpoAmo::SUBDOMAIN)
	{
		parent::__construct($type);
	}
	public function getUser($name)
	{
		$headers = [
			'Authorization: Bearer ' . getToken($this->type)
		];
		$curl = curl_init();
		/** Устанавливаем необходимые опции для сеанса cURL  */
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
		curl_setopt($curl, CURLOPT_URL, 'https://mzpoeducationsale.amocrm.ru/api/v4/users/?page=1&limit=150');
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		$out = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		curl_close($curl);

		$out = json_decode($out, 1);
		foreach($out['_embedded']['users'] as $user)
		{
			if($user['name'] == $name and $user['rights']['is_active'])
			{
				return $user['id'];
			}
		}
		return null;
	}
}