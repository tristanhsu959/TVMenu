<?php

namespace App\Enums;

enum Area : int
{
    case TAIPEI		= 1;
	case YILAN 		= 2; #整併至大台北(pos還有此區,不能直接刪)
	case TCM 		= 3; #Taoyuan, Hsinchu, and Miaoli 
	case CCT 		= 4; #Taichung, Changhua, Nantou
	case YCN  		= 5; #Yunlin, Chiayi, Tainan
	case KAOHSIUNG  = 6;
	case NONE		= 0; #給AreaLib設定miss match的default值
	
	public function label() : string
    {
        return match ($this) 
		{
			self::TAIPEI	=> '大台北區',
			self::YILAN 	=> '宜蘭區',		#此區可歸至大台北
			self::TCM		=> '桃竹苗區',
			self::CCT 		=> '中彰投區',
			self::YCN		=> '雲嘉南區',
			self::KAOHSIUNG => '大高雄區',
			self::NONE 		=> 'N/A',
			default			=> '',
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
		$list[] = self::YILAN->value;
		$list[] = self::TCM->value;
		$list[] = self::CCT->value;
		$list[] = self::YCN->value;
		$list[] = self::KAOHSIUNG->value;
		
		return $list;
	}
	
	/* Area key-enum array
	 * @params:  
	 * @return: array
	 */
	public static function caseWithKeys(): array
	{
		$list = [];
		foreach(self::cases() as $case)
		{
			if ($case->value > 0)
				$list[] = $case;
		}
		
		return $list;
	}
	
	#key-value array
	/* public static function mapWithKeys(): array
	{
		$list = [];
		foreach(self::cases() as $case)
		{
			if ($case->value > 0)
				$list[$case->value] = $case->label();
		}
		
		return $list;
	} */
	
	/* Area key-value array(20260611改為只回傳不含NONE)
	 * @params:  
	 * @return: array
	 */
	public static function options(): array
	{
		return collect(self::cases())->filter(function($item, $key){
			return $item->value > 0 && $item != self::YILAN;
		})->mapWithKeys(function ($case) {
			return [$case->value => $case->label()];
		})->toArray();
	}
}
