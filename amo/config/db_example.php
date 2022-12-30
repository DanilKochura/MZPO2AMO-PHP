<?php

class DB
{
	private string $host='localhost';
	private string $user='root';
	private string $db='db';
	private string $pw='pass';
	protected $conn;
	public function __construct()
	{
		$this->conn = mysqli_connect($this->host, $this->user, $this->pw, $this->db);
	}

}

class TokenDb extends DB
{
	private $table = 'amo';

	public function __construct()
	{
		parent::__construct();
	}
	public function saveToken($access, $refresh, $expires, $domain)
	{
		$this->conn->query("UPDATE amo set isp = 0");
		$this->conn->query("INSERT INTO amo(`access`, `refresh`, `expires`, `domain`,  `isp`) values('$access', '$refresh', '$expires', '$domain', 1)");
	}

	public function getToken()
	{
		$res = $this->conn->query("SELECT * from amo where isp = 1 limit 1");
		$resp = $res->fetch_assoc();
		return  [
			'accessToken' => $resp['access'],
			'expires' => $resp['expires'],
			'refreshToken' => $resp['refresh'],
			'baseDomain' => $resp['domain'],
		];
	}

	public function __destruct()
	{
		$this->conn->close();
	}
}

const TYPE = 639075;
const COURSE = 357005;
const SITE = 639081;
const CITY = 639087;
const PIPELINE = 6167398;
