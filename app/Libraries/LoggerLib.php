<?php

namespace App\Libraries;

use Illuminate\Support\Str;
use Log;

#暫時deprecated不使用
class LoggerLib
{
	private $_title;
	
	public function __construct($title)
	{
		$this->_title = $title;
	}
	
	/* initialize
	 * @params: string
	 * @return: object
	 */
    public static function initialize($title)
    {
		return new LoggerLib($title);
    }
	
	/* initialize
	 * @params: array
	 * @return: object
	 */
    public function sysLog($msg, $class = 'NA', $function = 'NA', $error = TRUE)
    {
		if ($error)
			Log::channel('webSysLog')->error("{$this->_title} [{$class}::{$function}] {$msg}");
		else
			Log::channel('webSysLog')->info("{$this->_title} [{$class}::{$function}] {$msg}");
    }
}