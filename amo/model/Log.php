<?php

namespace MzpoAmo;

class Log
{
	public const CONTACT = 'contact/';
	public const LEAD = 'lead/';
	public const POST = 'common/';

	public const COURSE = 'course/';

	public const ERROR = 1;
	public static function writeError($class, \Exception $exception = null)
	{

		$code = $exception->getCode();
		$line = $exception->getLine();
		$file = $exception->getFile();
		$text = $exception->getMessage();
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/amo/logs/' . $class.date('Y-m').'.txt', date("Y-m-d H:i:s") . ' : ' . $code . PHP_EOL . 'Line: ' . $line . ' File: ' . $file . PHP_EOL . 'Message: ' . $text . PHP_EOL . '_________________________' . PHP_EOL, FILE_APPEND);
	}
	public static function write($class, $post)
	{
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/amo/logs/'.$class.date('Y-m').'.txt', date("Y-m-d H:i:s").' '.print_r($post, 1).PHP_EOL.'_________________________'.PHP_EOL, FILE_APPEND);
	}

	public static function writeLine($class, $message)
	{
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/amo/logs/'.$class.date('Y-m').'.txt', date("Y-m-d H:i:s").': '.$message.PHP_EOL, FILE_APPEND);
	}

}