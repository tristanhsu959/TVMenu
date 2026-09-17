<?php

use App\Enums\Brand;

#Dashboard的product
#Product setting category
return [
	Brand::BAFANG->value => [
		1 	=> '鍋貼',
		2 	=> '水餃',
		3 	=> '乾麵',
		4 	=> '湯麵',
		5 	=> '金牌牛肉',
		6 	=> '湯品',
		7 	=> '湯餃',
		8 	=> '抄手',
		9 	=> '小菜',
		10 	=> '飲品',
		11	=> '生鮮冷凍',
		12	=> '牛小排麵',
    ],
	
	Brand::BUYGOOD->value => [
		1 	=> '飯類',
		2 	=> '麵類',
		3 	=> '小菜',
		4 	=> '單點',
		5 	=> '湯品',
		6 	=> '飲品',
	],
];

