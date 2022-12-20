<?php

namespace reports;
use DB;

abstract class ReportBase extends DB
{
	protected string $table;
	protected array $fields;

	public function __construct()
	{
		parent::__construct();
	}

	public function add(array $values)
	{
		$valuesS = implode('\',\'', $values);
		$res = $this->conn->query("INSERT INTO `$this->table` (" . implode(',', $this->fields) . ") values('$valuesS')");
		}
}