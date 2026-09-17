<?php

use App\Enums\Brand;

#Product category
return [
	'typeNo' => [
		'enabled' => [
			Brand::BAFANG->value => [
				'A', 'A2', 'A3', 'B',
				'D', 'E', 'F', 'G'
			],
			
			Brand::BUYGOOD->value => [
				'A', 'A2', 'A3',
				'D', 'E', 'F', 'G'
			],
		],
		'except' => [
			Brand::BAFANG->value => [
				'H', 'I', 'Z'
			],
		
			Brand::BUYGOOD->value => [
				'H', 'I', 'Z'
			]
		]
	],
	'shortCode' => [
		#use for like
		'enabled' => [
			'0%', '2%', '3%', '4%', '5%', '6%', '7%', '8%', '9%',
		],
		'except' => [
			
		]
	],
	
	#20260611改為分brand
	'groupPrefix' => [
		Brand::BAFANG->value => [
			[
				'id'		=> 0,
				'name' 		=> '餡類', #OK
				'pattern' 	=> ['00']/* ['000', '001'] */
			],
			[
				'id'		=> 1,
				'name' 		=> '皮類', #OK
				'pattern' 	=> ['01']
			],
			[
				'id'		=> 2,
				'name' 		=> '飲品類', #OK
				'pattern' 	=> ['02']
			],
			[
				'id'		=> 3,
				'name' 		=> '麵類', #OK
				'pattern' 	=> ['03']
			],
			[
				'id'		=> 20,
				'name' 		=> '冷凍', #OK
				'pattern' 	=> ['20', '22', '9961']
			],
			[
				'id'		=> 30,
				'name' 		=> '冷藏', #OK
				'pattern' 	=> ['30', '32', '37', '39']
			],
			[
				'id'		=> 32,
				'name' 		=> '醬類', #OK
				'pattern' 	=> ['34', '36']
			],
			[
				'id'		=> 40,
				'name' 		=> '粉類', #OK
				'pattern' 	=> ['40']
			],
			[
				'id'		=> 42,
				'name' 		=> '乾料類',
				'pattern' 	=> ['42']
			],
			[
				'id'		=> 44,
				'name' 		=> '調味料類',
				'pattern' 	=> ['44']
			],
			[
				'id'		=> 50,
				'name' 		=> '小菜', #OK
				'pattern' 	=> ['50', '52', '53']
			],
			[
				'id'		=> 60,
				'name' 		=> '袋類',
				'pattern' 	=> ['60']
			],
			[
				'id'		=> 61,
				'name' 		=> '衣服類',
				'pattern' 	=> ['61']
			],
			[
				'id'		=> 62,
				'name' 		=> '圍裙．帽．圍巾',
				'pattern' 	=> ['62']
			],
			[
				'id'		=> 63,
				'name' 		=> '點菜單',
				'pattern' 	=> ['63']
			],
			[
				'id'		=> 66,
				'name' 		=> '雜項',
				'pattern' 	=> ['66', '70', '90']
			],
			[
				'id'		=> 80,
				'name' 		=> '供應商',
				'pattern' 	=> ['43', '45', '80', '82', '83', '84']  
			],
			[
				'id'		=> 99,
				'name' 		=> '員購類',
				'pattern' 	=> ['99']
			],
		],
		
		#御廚
		Brand::BUYGOOD->value => [
			[
				'id'		=> 2,
				'name' 		=> '飲品類', #OK
				'pattern' 	=> ['02']
			],
			[
				'id'		=> 3,
				'name' 		=> '麵類', #OK
				'pattern' 	=> ['03']
			],
			[
				'id'		=> 20,
				'name' 		=> '冷凍', #OK
				'pattern' 	=> ['20', '22']
			],
			[
				'id'		=> 30,
				'name' 		=> '冷藏', #OK
				'pattern' 	=> ['30', '32', '37', '39']
			],
			[
				'id'		=> 32,
				'name' 		=> '醬類', #OK
				'pattern' 	=> ['34', '36']
			],
			[
				'id'		=> 40,
				'name' 		=> '粉類', #OK
				'pattern' 	=> ['40']
			],
			[
				'id'		=> 42,
				'name' 		=> '乾料類',
				'pattern' 	=> ['42']
			],
			[
				'id'		=> 44,
				'name' 		=> '調味料類',
				'pattern' 	=> ['44']
			],
			[
				'id'		=> 50,
				'name' 		=> '小菜', #OK
				'pattern' 	=> ['50', '52', '53']
			],
			[
				'id'		=> 56,
				'name' 		=> '生鮮菜類', #OK
				'pattern' 	=> ['56']
			],
			[
				'id'		=> 60,
				'name' 		=> '袋類',
				'pattern' 	=> ['60']
			],
			[
				'id'		=> 62,
				'name' 		=> '圍裙．帽．圍巾',
				'pattern' 	=> ['62']
			],
			[
				'id'		=> 63,
				'name' 		=> '點菜單',
				'pattern' 	=> ['63']
			],
			[
				'id'		=> 66,
				'name' 		=> '雜項',
				'pattern' 	=> ['66', '70', '82', '90', '95']
			],
			[
				'id'		=> 71,
				'name' 		=> '衣服類',
				'pattern' 	=> ['71']
			],
			[
				'id'		=> 80,
				'name' 		=> '供應商',
				'pattern' 	=> ['43', '45', '80', '82', '83', '84']  
			],
			[
				'id'		=> 99,
				'name' 		=> '員購類',
				'pattern' 	=> ['99']
			],
		],
	],

	#包裝斤數轉換
	'packagingScale' => [
		'0015' => 1.8, #新蔬食餡
		'0006' => 2.5, #菜肉餡
	],
	
	#店休判別
	'dayOff' => [
		Brand::BAFANG->value => [
			'0001', '0002', '0003'
		],
		Brand::BUYGOOD->value => [
			'2205', '2206'
		],
	],
	
	#未訂判別
	'notOrderFillingProducts' => [
		Brand::BAFANG->value => [
			'0001', '0002', '0003', '0005', '0011', '0015', '0006', '0007',
			'0101', '0102', '0104', '0106', '0107', '0110', '0117',
		],
		Brand::BUYGOOD->value => [
			'2205', '2206'
		],
	],
	
	#粗細麵轉換-因南北不同代碼(未來可重構既有功能判別)
	'noodles' => [
		#查詢DB用
		'queryShortCodes' => [
			'0301' => ['0301', '0303'], #白細麵球
			'0302' => ['0302', '0304'], #粗麵球
		],
		
		#統一轉換為一致代碼
		'convert' => [
			'0303' => '0301', #白細麵球
			'0304' => '0302', #粗麵球
		]
	],
];

/* Short code
餡類					00xx
皮類					01xx
飲料					02xx
麵類					03xx
冷藏類				30xx 32xx 34xx 36xx 37xx 39xx 50xx 52xx 53xx - filter out
生鮮					56xx
冷凍類				20xx 
乾貨類				40xx 42xx 43xx 44xx 46xx - filter out
美食類				22xx
雜項類（制服、菜單）	6xxx 7xxx - filter out
五金類				9xxx - filter out

===
餡類					00xx
皮類					01xx
飲料					02xx
麵類					03xx
湯料					20xx 30xx 42xx
醬包					34xx 3601
麵醬					36xx (除3601)
粉					40xx
調味料				42xx 44xx
湯包					44xx
小菜					50xx 52xx
*/

/* BF b.No not in ('F', 'Z', 'H', 'I')
1 : A A2 A3 B
2 : D E G
3 : F H I Z
餡類					A
皮類					A2
餡皮類-2				A3
麵類					B
冷藏類				D
冷凍類				E
乾貨類				F
美食類				G
雜項類（制服、菜單）	H
五金類				I
退貨單條類(不列印)		I
供應商出貨			Z
*/

#--------
/* BG ProducType AEGJDFZHI
b.No not in ('F', 'Z', 'H', 'I')
1 : A A2 A3
2 : D E G 
3 : J F(有錯誤類別) Z H I

餡類					A
皮類					A2
主食類				A3
冷藏類				D
冷凍類				E
乾貨類				F
美食類				G
雜項類（制服、菜單）	H
五金類				I
生鮮菜類				J
供應商出貨			Z
*/


