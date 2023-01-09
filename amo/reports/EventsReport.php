<?php

namespace reports;

class EventsReport extends BaseReport
{
	public function __construct()
	{
		parent::__construct();
		$this->table = 'amo_events_reports';
		$this->fields = ['manager', 'official_name', 'company', 'name', 'email', 'phone', 'text', 'payment', 'price', 'link', 'datetime', 'contactId', 'leadId'];
	}
}