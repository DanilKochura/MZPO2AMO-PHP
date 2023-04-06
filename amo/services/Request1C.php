<?php
namespace services;

use GuzzleHttp\Client;

class Request1C
{
	public string $pass;
	public string $user;
	public string $host;
	public Client $client;

	public function __construct()
	{
		$data = json_decode(file_get_contents('access.json'), true);
		$this->pass = $data['pwd'];
		$this->user = $data['username'];
		$this->host = $data['uri'];
		$this->client = new Client();

	}

	public function request($type, $method, $data = null)
	{
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
		return $this->client->request($type, $this->host.$method, [
			'auth' => [$this->user, $this->pass],
			'User-agent' => 'mzpo1C-client/1.0',
			'Content-type' => 'application/json',
			'Accept' => 'application/json',
			'body' => $data,
		]);
	}
}
