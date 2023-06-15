<?php
if(!$_POST)
{
	$_POST = json_decode(file_get_contents('php://input'), true);
}
file_put_contents(__DIR__.'/0.txt', print_r($_POST, 1), FILE_APPEND);
