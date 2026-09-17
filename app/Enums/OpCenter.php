<?php

namespace App\Enums;

enum OpCenter : string
{
	case TAIPEI		= 'TP';
	case KAOHSIUNG 	= 'KH';
    
	public function label() : string
    {
        return match ($this) 
		{
			self::TAIPEI	=> '台北總公司',
			self::KAOHSIUNG	=> '高雄分公司',
		};
    }
	
	/* Area values,只有處理supervisor時呼叫(自動設為全權限)
	 * @params:  
	 * @return: array
	 */
	public static function getAll() : array
	{
		$list = [];
		
		$list[] = self::TAIPEI->value;
		$list[] = self::KAOHSIUNG->value;
		
		return $list;
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
	
	public static function options(): array
	{
		return collect(self::cases())->mapWithKeys(function ($case) {
			return [$case->value => $case->label()];
		})->toArray();
	}
}
