<?php

use App\Enums\Brand;

return [
	#Op Center對應DB的BrandId, 用對應的Id,等同過濾了營運中心
	'brandMap' => [
		'TP' => [
			Brand::BAFANG->value 	=> 1,
			Brand::BUYGOOD->value 	=> 2,
			Brand::FJVEGGIE->value 	=> 3,
			Brand::LUOBO->value 	=> 13, #沒分,都歸在TP
		],
		
		'KH' => [
			Brand::BAFANG->value 	=> 5,
			Brand::BUYGOOD->value 	=> 6,
			Brand::FJVEGGIE->value 	=> 7,
			Brand::LUOBO->value 	=> 13,
		],
	],
];
