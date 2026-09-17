<?php

namespace App\Enums;

enum Factory : string
{
	case TP		= 'TW_TP';
	case KH 	= 'TW_KH';
	case TS		= 'TW_TS';
	case RL 	= 'TW_RL';
    
	public function label() : string
    {
        return match ($this) 
		{
			self::TP	=> '淡水總廠',
			self::KH	=> '高雄工廠',
			self::TS	=> '屯山廠',
			self::RL	=> '二崙工廠',
		};
    }
	
	public static function toArray(): array 
	{
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->all();
    }
	
	public static function toValueArray(): array 
	{
        return collect(self::cases())->map(function ($case) {
            return $case->value;
        })->all();
    }
}	

