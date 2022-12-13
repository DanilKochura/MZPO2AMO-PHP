<?php

class DB
{
	private string $host='localhost';
	private string $user='mzpo_s_ru_usr';
	private string $db='u60844_seminars';
	private string $pw='yZAMhtU9CnGbeDDg';
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
	public function saveToken($access, $refresh, $time, $time2)
	{
		$this->conn->query("UPDATE '$this->table' set isp = 0");
		$this->conn->query("INSERT INTO '$this->table'(access, refresh, access_expires, refresh_expires, isp) values('$access', '$refresh', '$time', '$time2', 1)");
	}
}


const TYPE = 639075;
const COURSE = 357005;
const SITE = 639081;
const CITY = 639087;
const PIPELINE = 6167398;
