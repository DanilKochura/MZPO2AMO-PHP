<?php

namespace services;

use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class QueueService
{
	public const LEADS = 'leads';
	public const WEBHOOKS = 'webhooks';

	public  const HOST = 'hawk.rmq.cloudamqp.com';

	public const USER = 'wxnojbul';

	public const PORT = 5672;
	public const PASSWORD = 'kMD0TgkoztlrBKYBxxkY-_StEN0xU9is';
	public const VHOST = 'wxnojbul';
	private AMQPStreamConnection $connection;

	public function __construct()
	{
		$this->connection = new AMQPStreamConnection(self::HOST, self::PORT, self::USER, self::PASSWORD, self::VHOST);
	}
	public function addToQueue($type, $data)
	{

			$channel = $this->connection->channel();
			$channel->queue_declare($type, false, true, false, false);

			$msg = new AMQPMessage($data);
			$channel->basic_publish($msg, '', $type);

			$channel->close();
	}

	public function __destruct()
	{
		$this->connection->close();
	}
}