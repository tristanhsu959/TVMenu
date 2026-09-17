<?php
use App\Enums\Brand;

#銷售
return [
	Brand::BAFANG->value => [
		#八方X美廉社中秋活動
		'moonFestival' => [
			'startDate'	=> '2026-09-02',
			'endDate'	=> '2026-10-05',
			'comboId' => 'PS10009079',
			'productList' => [
				'PS00000001' => '招牌鍋貼',
				'PS00000002' => '韭菜鍋貼',
				'PS00000003' => '韓式辣味鍋貼',
			],
			'productShortList' => [
				'PS00000001' => '招',
				'PS00000002' => '韭',
				'PS00000003' => '辣',
			],
		],
	],
	
	Brand::BUYGOOD->value => [
	],
	Brand::FJVEGGIE->value => [
	],
];
