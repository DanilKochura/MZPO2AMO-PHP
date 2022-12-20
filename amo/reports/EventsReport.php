<?php

namespace reports;

class EventsReport extends ReportBase
{
	public function __construct()
	{
		parent::__construct();
		$this->table = 'amo_event_reports';
		$this->fields = ['manager', 'official_name', 'company', 'name', 'email', 'phone', 'text', 'payment', 'price', 'link'];
	}
}