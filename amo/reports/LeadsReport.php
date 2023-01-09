<?php

namespace reports;

class LeadsReport extends BaseReport
{
	public function __construct()
	{
		parent::__construct();
		$this->table = 'amo_leads_reports';
		$this->fields = ['site', 'datetime', 'text', 'leadId', 'contactId'];
	}

}