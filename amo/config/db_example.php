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
	public function saveToken($access, $refresh)
	{
		$this->conn->query("UPDATE amo set isp = 0");
		$this->conn->query("INSERT INTO amo(access, refresh, isp) values('$access', '$refresh', 1)");
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
