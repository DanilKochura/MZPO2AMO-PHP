<?php

namespace MzpoAmo;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;


class MzposApiEvent
{
	public int $id; //id мероприятия
	public string $page_name; //название мероприятия
	public string $datetime; //дата  и время мероприятия
	public string $adress; //адрес проведения


	public const DOD = 6;
	public const FREE_EVENT = 1;
	public const OPEN_LESSON = 12;
	public const STYX = 'STYX';
	public const MORIZO = 'MORIZO';
	public const MKV = 13;


	public function __construct($name)
	{
		$client = new Client();
		$response = $client->request(
			'POST',
			'https://www.mzpo-s.ru/api/activities_api.php',
				[
					'form_params' => [
						'mzpo2amo'=>'xDvkV@DgpsWh',
						'secret'=>'i$eSem64nQka',
						'name'=>htmlspecialchars($name)
					]
				]
		);
		if($response->getStatusCode() == 200 and $response->getHeaderLine('content-type') == 'application/json;charset=utf-8')
		{
			$resp = json_decode($response->getBody());
			$this->id = $resp->id;
			$this->adress = $resp->event_address;
			$this->datetime = $resp->vrema;
			$this->page_name = $resp->page_name;
		}
		else
		{
			die('Mzpo-s api error');
		}
	}

	/**
	 * Тип мероприятия по названию
	 * @return int|string
	 */
	public function getType()
	{
		if (strpos($this->page_name,"Международного клуба выпускников")!==false)
			{
				return $this::MKV;
			}
			if (strpos($this->page_name,"STYX")!==false)
			{
				return $this::STYX;
			}
			if (strpos($this->page_name,"Morizo")!==false)
			{
				return $this::MORIZO;
			}
			if (strpos($this->page_name,"День открытых дверей")!==false)
			{
				return $this::DOD;
			}
			if (strpos($this->page_name,"Пробный урок")!==false)
			{
				return $this::OPEN_LESSON;
			}
			return $this::FREE_EVENT;
	}
}