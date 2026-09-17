<?php

namespace App\Libraries;

use Illuminate\Support\Str;

class ResponseLib
{
	private $_response;
	
	public function __construct($data)
	{
		$this->_response['status'] = FALSE;
		$this->_response['data'] = $data;
		$this->_response['msg'] = '';
	}
	
	/* initialize
	 * @params: array
	 * @return: object
	 */
    public static function initialize($data = [])
    {
		return new ResponseLib($data);
    }
	
	public function __get($name)
    {
		return data_get($this->_response, $name, FALSE);
	}
	
	/* set to success : responseLib::initialize($initData)->success($resultData)->get()
	 * @params: string
	 * @return: object
	 */
	public function success($msg = NULL)
	{
		if (! empty($msg))
			$this->_response['msg'] = $msg;
		
		$this->_response['status'] = TRUE;
		
		return $this;
	}
	
	/* set to fail : responseLib::initialize($initData)->fail($msg)->get()
	 * @params: string
	 * @return: object
	 */
	public function fail($msg = NULL)
	{
		if (! empty($msg))
			$this->_response['msg'] = $msg;
		
		$this->_response['status'] = FALSE;
		
		return $this;
	}
	
	/* Return response format
	 * @params: string
	 * @return: array
	 */
	public function get()
	{
		return $this->_response;
	}
	
	/*public function data($data = [])
	{
		$this->_response['data'] = $data;
		return $this;
	}
	
	public function msg($msg = '')
	{
		$this->_response['msg'] = $msg;
		return $this;
	}*/
	
}