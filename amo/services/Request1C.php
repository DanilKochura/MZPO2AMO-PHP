<?php
namespace services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ServerException;

/**
 * @method EditApplication_POST(\MzpoAmo\Lead1C $lead)
 * @method EditStudent_POST(\MzpoAmo\Contact1C $client)
 * @method EditStudent_GET(string $string)
 * @method EditStudentV2_POST(\MzpoAmo\Contact1C $fromAmo)
 * @method EditPartner_POST(\MzpoAmo\Company1C $contragent)
 */
class Request1C
{
	public string $pass;
	public string $user;
	public string $host;
	public Client $client;

	public function __construct()
	{
		$data = json_decode(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/amo/access.json'), true);
		$this->pass = $data['pwd'];
		$this->user = $data['username'];
		$this->host = $data['uri'];
		$this->client = new Client();

	}

	public function request($type, $method, $data = null)
	{

		//region cUrl
		//		$url = $this->host.$method;
//		$ch = curl_init($url);
//
//		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
//		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//		curl_setopt($ch, CURLOPT_USERPWD, $this->user . ":" . $this->pass);
//		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
//			'Content-Type: application/json',
//			'Content-Length: ' . strlen($data)
//		));
//		$response = curl_exec($ch);
//		if(curl_errno($ch)) {
//			die( 'Error: ' . curl_error($ch));
//		} else {
//			echo $response; die();
//		}
//		curl_close($ch);
		//endregion

		$head = [
			'auth' => [$this->user, $this->pass],
			'User-agent' => 'mzpo1C-client/1.0',
			'Content-type' => 'application/json',
			'Accept' => 'application/json',
		];
		if($data and strtolower($type) == 'post')
		{
			$head['body'] = json_encode($data);
		}
		file_put_contents(__DIR__.'/0.txt', json_encode($data), FILE_APPEND);
        try {
            return json_decode($this->client->request($type, $this->host.$method, $head)->getBody());
        } catch (\Exception $e)
        {
        throw new \Exception();
        }

	}

	public function __call($name, $arguments)
	{
		$array = explode('_', $name);
		if(count($array) < 2)
		{
			throw new \Exception('Undefined method: '.$array);
		}
		if(strtolower($array[1]) == 'get')
		{
			$method = $array[0].'?uid='.$arguments[0];
		} else
		{
			$method = $array[0];
		}

		return $this->request($array[1], $method, $arguments[0]);

	}



}
