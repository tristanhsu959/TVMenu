<?php

namespace App\Enums;

enum MediaType : int
{
	case IMAGE	= 1;
	case VIDEO 	= 2;
	
	public function label() : string
    {
        return match ($this) 
		{
			self::IMAGE		=> '圖片',
			self::VIDEO		=> '影片',
			default			=> ''
		};
    }
	
	public static function toArray(): array 
	{
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->all();
    }
}
