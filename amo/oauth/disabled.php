<?php
if(!$_POST)
{
	$_POST = json_decode(file_get_contents('php://input'), true);
}
file_put_contents(__DIR__.'/disabled.txt', print_r($_POST, 1), FILE_APPEND);
