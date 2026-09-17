<?php

namespace App\Enums;

enum FormAction : int
{
	case SIGNIN	= 1;
	case HOME 	= 2;
    case LIST 	= 3;
	case CREATE	= 4;
	case UPDATE = 5;
	case DELETE = 6;
	case EXPORT = 7;
	case DETAIL	= 8;
	
	public function label() : string
    {
        return match ($this) 
		{
			self::SIGNIN	=> '登入',
			self::HOME		=> '首頁',
			self::LIST 		=> '',	#'列表',
			self::CREATE	=> '新增',
			self::UPDATE 	=> '編輯',
			self::DELETE 	=> '刪除',
			self::EXPORT 	=> '匯出',
			self::DETAIL 	=> '明細',
		};
    }
}
