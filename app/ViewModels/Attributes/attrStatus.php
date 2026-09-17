<?php

namespace App\ViewModels\Attributes;


#Status & Message
trait attrStatus
{
	protected $_status;
	protected $_msg; 
	
	/* Set status & msg
	 * @params: string
	 * @return: void
	 */
	public function success($msg = NULL)
	{
		$this->_status	= TRUE;
		$this->_msg 	= $msg ?? '';
	}
	
	public function fail($msg)
	{
		$this->_status	= FALSE;
		$this->_msg 	= $msg;
	}
	
	/* Get status or msg
	 * @params: string
	 * @return: void
	 */
	public function status()
	{ 
		return $this->_status;
	}
	
	public function msg()
	{
		#Pass from middleware
		if (! empty(session('msg')))
			return session('msg');
		else
			return $this->_msg;
	}
}