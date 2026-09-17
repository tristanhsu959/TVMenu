<?php

use App\Enums\RoleGroup;
use App\Enums\Operation;

#非Menu選單, 但可能一樣要設權限, 只放在Home
return [
	#每日結單
	'dailySales' => [ 
		[
			'center'	=> '台北',
			'factory' 	=> '屯山',
			'items' => [
				'porkRibs' => [
					'name'	=> '橙汁排骨',
					'unit'	=> '包'
				],
				'beef' => [
					'name'	=> '紅燒牛肉調理包',
					'unit'	=> '組'
				],
				'braisedPork' => [
					'name'	=> '滷肉',
					'unit'	=> '包'
				],
				'eggTofu' => [
					'name'	=> '非基改雞蛋豆腐',
					'unit'	=> '盒'
				],
			],
		],
		
	],
];
