<?php

use App\Enums\Brand;
use App\Enums\Factory;

#Store
return [
	'except' => [
		Brand::BAFANG->value => [
			'KH1100000', 'KH1100100', 'KH1688', 'KH16888', 'KH168888',
			'TP99999991', 'KH1034', 'KH99999991', 'TPB000123', 
			'4030007', '7020010' #建檔錯誤無前兩碼
		],
		Brand::BUYGOOD->value => [
			'TS10006000', 'TS999111', 'RLbg999', 'RL1002'
		],
		Brand::FJVEGGIE->value => [],
	],
	#根據八方點另整理
	'factoryStore' => [
		Brand::BAFANG->value => [
			'1040020', '1060036', '1060005', '1120020', '1120022', '1150006', 
			'2410016', '2410027', '2410028', '2420004', '2510010', 
			'3000001', '3000006', '3000013', '3000037', '3000047', 
			'3000048', '3020002', '3020011', '3020012', '3020013',
			'3060002', '3080005', '3080006', '3300002', '3300006', 
			'3300013', '3330001', '3380005', '3500002', '3500004', 
			'3500006', '3250008',
			'4060017', '4070021', '4070022', '4070023', '4080012', 
			'4080013', '4130002', '4130003', '4210005', '4280006', 
			'5450006',
			'7110003',
			'8240003', '8330006', '8400003', '8110012', 
			'8110011', '8210004',
			#'8330004', #高雄長庚直營店(Dashboard暫不排除)
			'4280007', #Dashboard新增
		],
		
		Brand::BUYGOOD->value => [],
		Brand::FJVEGGIE->value => [],
	],
	
	#蘿蔔店:特別要處理的店(蘿蔔 => 八方)
	'lbSpecialStore'=> [
		'TP11100152' => 'TP11100071',
		'TP11200112' => 'TP11200051',
	],
	
	#公關員購StoreId in舊系統
	'employee'=> [
		Brand::BAFANG->value => [
			Factory::TP->value => '1000',
			Factory::KH->value => '1100000' #KH
		],
		
		Brand::BUYGOOD->value => [
			Factory::RL->value => '1002', #RL
			Factory::TS->value => '1003' #TS
		],
	],
	
	'pr'=> [
		Brand::BAFANG->value => [
			Factory::TP->value => '1001', #TP
			Factory::KH->value => '1100100' #KH
		],
		
		Brand::BUYGOOD->value => [
			Factory::RL->value => '1001', #RL
			Factory::TS->value => '1001' #TS
		],
	],
	
	
];
