<?php

namespace App\Libraries;

use Illuminate\Support\Str;
use App\Enums\Area;

#目前只有舊的command有用到, 應可deprecated
class ShopLib
{
	/* Get area name
	 * @params: string
	 * @return: string
	 */
    public static function getAreaByShopId($shopId)
    {
		if (intval($shopId) >= 100000 and intval($shopId) <= 259999)
			return Area::TAIPEI->label(); #'大台北區'
		else if (intval($shopId) >= 260000 and intval($shopId) <= 270999)
			return Area::YILAN->label(); #'宜蘭區'
		else if (Str::startsWith($shopId, ['3']))
			return Area::TCM->label(); #'桃竹苗區'
		else if (Str::startsWith($shopId, ['4', '5']))
			return Area::CCT->label(); #'中彰投區'
		else if (Str::startsWith($shopId, ['6', '7']))
			return Area::YCN->label(); #'雲嘉南區'
		else if (Str::startsWith($shopId, ['8', '9']))
			return Area::KAOHSIUNG->label(); #'大高雄區'
		else
			return 'UNKNOW';
    }
	
	/* Get area id
	 * @params: string
	 * @return: string
	 */
	public static function getAreaIdByShopId($shopId)
    {
		if (intval($shopId) >= 100000 and intval($shopId) <= 259999)
			return Area::TAIPEI->value; #'大台北區'
		else if (intval($shopId) >= 260000 and intval($shopId) <= 270999)
			return Area::YILAN->value; #'宜蘭區'
		else if (Str::startsWith($shopId, ['3']))
			return Area::TCM->value; #'桃竹苗區'
		else if (Str::startsWith($shopId, ['4', '5']))
			return Area::CCT->value; #'中彰投區'
		else if (Str::startsWith($shopId, ['6', '7']))
			return Area::YCN->value; #'雲嘉南區'
		else if (Str::startsWith($shopId, ['8', '9']))
			return Area::KAOHSIUNG->value; #'大高雄區'
		else
			return 0;
    }
}