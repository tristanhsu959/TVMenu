<?php

namespace App\Libraries\Sales;

use Illuminate\Support\Str;
use App\Enums\Brand;
use App\Enums\Area;

class AreaLib
{
	public static function toArea($srcId)
	{
		return match ($srcId) 
		{
			'1'		=> 	Area::TAIPEI,
			'2'		=> 	Area::TCM,
			'3'		=> 	Area::CCT,
			'4'		=> 	Area::YCN,
			'5'		=> 	Area::TAIPEI, #Area::YILAN,
			'6'	 	=> 	Area::KAOHSIUNG,
			'A01'	=>  Area::TAIPEI,	
			'A02'	=>  Area::TCM,	
			'A03'	=>  Area::CCT,		
			'A04'	=>  Area::YCN,
			'A05'	=>  Area::KAOHSIUNG,
			'A06'	=>  Area::TAIPEI, #Area::YILAN,
			default => Area::NONE,
		};
	}
	
	#Bafang|Buygood shopgroup gid to my area id
	public static function toId($srcId): int
	{
		return match ($srcId) 
		{
			'1'		=> 	Area::TAIPEI->value,
			'2'		=> 	Area::TCM->value,
			'3'		=> 	Area::CCT->value,
			'4'		=> 	Area::YCN->value,
			'5'		=> 	Area::TAIPEI->value, #Area::YILAN->value,
			'6'	 	=> 	Area::KAOHSIUNG->value,
			'A01'	=>  Area::TAIPEI->value,	
			'A02'	=>  Area::TCM->value,	
			'A03'	=>  Area::CCT->value,		
			'A04'	=>  Area::YCN->value,
			'A05'	=>  Area::KAOHSIUNG->value,
			'A06'	=>  Area::TAIPEI->value, #Area::YILAN->value,
			default => Area::NONE->value,
		};
	}
	
	#To Bafang shopgroup gid
	public static function toSalesAreaId($brand, $srcIds): array
	{
		if ($brand == Brand::BAFANG)
			return self::toBafangId($srcIds);
		else if ($brand == Brand::BUYGOOD)
			return self::toBuygoodId($srcIds);
		else if ($brand == Brand::FJVEGGIE)
			return self::toFjVeggieId($srcIds);
		else
			return [];
	}
	
	#To Bafang shopgroup gid
	public static function toBafangId($srcIds): array
	{
		return collect($srcIds)->map(function ($value, int $key) {
			$value = intval($value);
			
			return match ($value) 
			{
				Area::TAIPEI->value		=> ['1', '5'], #因pos還有宜蘭的id
				Area::TCM->value		=> ['2'],
				Area::CCT->value 		=> ['3'],
				Area::YCN->value		=> ['4'],
				Area::YILAN->value 		=> ['5'],
				Area::KAOHSIUNG->value 	=> ['6'],
				default => '0',
			};
			
		})->collapse()->unique()->toArray();
	}
	
	#To Buygood shopgroup gid : toBuygoodId
	public static function toBuygoodId($srcIds): array
	{
		return collect($srcIds)->map(function ($value, int $key) {
			$value = intval($value);
			
			return match ($value) 
			{
				Area::TAIPEI->value		=> ['A01', 'A06'],
				Area::TCM->value		=> ['A02'],
				Area::CCT->value 		=> ['A03'],
				Area::YCN->value		=> ['A04'],
				Area::KAOHSIUNG->value 	=> ['A05'],
				Area::YILAN->value 		=> ['A06'],
				default => '0',
			};
			
		})->collapse()->unique()->toArray();
	}
	
	#To Fj shopgroup gid(同bafang):toFjVeggieId
	public static function toFjVeggieId($srcIds): array
	{
		return collect($srcIds)->map(function ($value, int $key) {
			$value = intval($value);
			
			return match ($value) 
			{
				Area::TAIPEI->value		=> ['1', '5'],
				Area::TCM->value		=> ['2'],
				Area::CCT->value 		=> ['3'],
				Area::YCN->value		=> ['4'],
				Area::YILAN->value 		=> ['5'],
				Area::KAOHSIUNG->value 	=> ['6'],
				default => '0',
			};
			
		})->collapse()->unique()->toArray();
	}
}