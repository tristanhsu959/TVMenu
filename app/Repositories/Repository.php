<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class Repository
{
	#norder database(新訂貨系統)
	protected function connectNewOrder($table = NULL)
	{
		if (empty($table))
			return DB::connection('NewOrder');
		else
			return DB::connection('NewOrder')->table($table); 
	}
	
	#八方點
	protected function connectQuickOrder($table = NULL)
	{
		if (empty($table))
			return DB::connection('QuickOrder');
		else
			return DB::connection('QuickOrder')->table($table); 
	}
	
	protected function connectTvMenu($table = NULL)
	{
		if (empty($table))
			return DB::connection('TVMenu');
		else
			return DB::connection('TVMenu')->table($table); 
	}
}
