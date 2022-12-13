<?php

use MzpoAmo\Contact;
use MzpoAmo\Leads;
use MzpoAmo\MzpoAmo;

require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/MzpoAmo.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Leads.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/amo/model/Contact.php';
file_put_contents(__DIR__.'/0.txt', print_r($_POST, 1));

if($_POST['method'] == 'siteform')
{


	$_POST['site'] = ltrim($_POST['site'], 'www.');
	$contact = new Contact($_POST); //создаем контакт
	if($contact->hasMergableLead()) //если есть сделка, которую можно склеить
	{
		$base = new Leads($_POST, $contact->hasMergableLead());
	}
	else
	{
		$base = new Leads($_POST);
	}

	if($_POST['comment'])
	{
		$note = $base->newNote($_POST['comment']);
	}
	if($base and $contact and $contact->linkLead($base->getLead()))
	{
		echo 'ok';

	}
}else{
	http_response_code(404);
	die('no post');
}

?>


