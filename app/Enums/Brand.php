<?php

namespace App\Enums;

enum Brand : int
{
	case BAFANG		= 1;
	case BUYGOOD 	= 2;
	
	public function label() : string
    {
        return match ($this) 
		{
			self::BAFANG	=> '八方',
			self::BUYGOOD	=> '御廚',
		};
    }
	
	public function code()
	{
		return match ($this) 
		{
			self::BAFANG	=> '8WAY',
			self::BUYGOOD	=> 'BUYGOOD',
        };
	}

	public static function tryFromCode($code)
	{
		return match ($code) 
		{
			self::BAFANG->code()	=> self::BAFANG,
			self::BUYGOOD->code()	=> self::BUYGOOD,
        };
	}
	
	public static function toArray(): array 
	{
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->code() => $case->label()];
        })->all();
    }
}
