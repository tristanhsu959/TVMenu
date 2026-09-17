<?php

namespace App\Enums;

enum Operation : int
{
    case CREATE	= 1;
	case READ 	= 2;
	case UPDATE = 3;
	case DELETE = 4;
	
	public function label() : string
    {
        return match ($this) 
		{
			self::CREATE	=> '新增',
			self::READ 		=> '查詢',
			self::UPDATE 	=> '編輯',
			self::DELETE 	=> '刪除',
        };
    }
}
