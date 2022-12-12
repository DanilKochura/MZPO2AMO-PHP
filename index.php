<?php

use MzpoAmo\Leads;
use MzpoAmo\MzpoAmo;

require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/MzpoAmo.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Leads.php';
file_put_contents(__DIR__.'/0.txt', print_r($_POST, 1));

if($_POST['method'] == 'siteform')
{

	$_POST['site'] = ltrim($_POST['site'], 'www.');


	$base = new Leads();
	$lead = $base->newLead($_POST);
	$contact = $base->newContact($_POST);
	if($_POST['comment'])
	{
		$note = $base->newNote($_POST['comment'], $lead);
	}
	if($base and $lead and $contact and $base->linkContact($contact, $lead))
	{
		echo 'ok';

	}
}else{
	http_response_code(404);
	die('no post');
}

?>


