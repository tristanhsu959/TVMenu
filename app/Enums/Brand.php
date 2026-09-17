<?php

namespace App\Enums;

enum Brand : int
{
	case BAFANG		= 1;
	case BUYGOOD 	= 2;
	case FJVEGGIE 	= 3;
	case LUOBO		= 99; #蘿蔔
    
	public function label() : string
    {
        return match ($this) 
		{
			self::BAFANG	=> '八方',
			self::BUYGOOD	=> '御廚',
			self::FJVEGGIE	=> '芳珍',
			self::LUOBO		=> '蘿蔔',
		};
    }
	
	public function code()
	{
		return match ($this) 
		{
			self::BAFANG	=> 'bafang',
			self::BUYGOOD	=> 'buygood',
			self::FJVEGGIE	=> 'fjveggie',
			self::LUOBO		=> 'luobo',
        };
	}

	public function shortCode()
	{
		return match ($this) 
		{
			self::BAFANG	=> 'BF',
			self::BUYGOOD	=> 'BG',
			self::FJVEGGIE	=> 'FJ',
			self::LUOBO		=> 'LB',
        };
	} 	
	
	public static function tryFromCode($code)
	{
		return match ($code) 
		{
			self::BAFANG->code()	=> self::BAFANG,
			self::BUYGOOD->code()	=> self::BUYGOOD,
			self::FJVEGGIE->code()	=> self::FJVEGGIE,
			self::LUOBO->code()		=> self::LUOBO,
        };
	}
	
	public static function toArray(): array 
	{
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->filter(function($value, $key){
			return $key != self::FJVEGGIE->value && $key != self::LUOBO->value;
		})
		->all();
    }
}
