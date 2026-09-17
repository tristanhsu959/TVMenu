<?php

namespace App\Enums;

enum MenuGroup : int
{
	case BAFANG		= 1;
    case BUYGOOD 	= 2;
	case FJVEGGIE 	= 3;
	case DATA		= 70;
	case PRODUCT	= 80;
	case MANAGE		= 99;
	
	public function label() : string
    {
        return match ($this) 
		{
			self::BAFANG	=> '八方',
			self::BUYGOOD 	=> '御廚',
			self::FJVEGGIE 	=> '芳珍',
			self::DATA 		=> '資料維護',
			self::PRODUCT 	=> '產品設定',
			self::MANAGE 	=> '系統管理',
        };
    }
}
