<?php

namespace App\Exceptions;

use Exception;
use Log;

class DBException extends Exception
{
	
	public function __construct(string $message, string $devMessage, string $class, string $function)
	{
		parent::__construct($message);
		$this->log($message, $devMessage, $class, $function);
	}
	
    public function log(string $message, string $devMessage, string $class, string $function)
	{
		Log::channel('webSysLog')->error("{$message} [{$class}::{$function}] {$devMessage}");
	}
}
