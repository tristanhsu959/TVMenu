<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class Repository
{
	#八方
	protected function connectBFPosErp($table = NULL)
	{
		if (empty($table))
			return DB::connection('BFPosErp');
		else
			return DB::connection('BFPosErp')->table($table);
	}
	
	#梁社漢
	protected function connectBGPosErp($table = NULL)
	{
		if (empty($table))
			return DB::connection('BGPosErp');
		else
			return DB::connection('BGPosErp')->table($table);
	}
	
	#芳珍
	protected function connectFJPosErp($table = NULL)
	{
		if (empty($table))
			return DB::connection('FJPosErp');
		else
			return DB::connection('FJPosErp')->table($table);
	}
	
	/* Local Sale[s]_Dashboard */
	protected function connectSalesDashboard($table = NULL)
	{
		if (empty($table))
			return DB::connection('SalesDashboard');
		else
			return DB::connection('SalesDashboard')->table($table);
	}
	
	protected function connectPosStatistics($table = NULL)
	{
		if (empty($table))
			return DB::connection('PosStatistics');
		else
			return DB::connection('PosStatistics')->table($table);
	}
	
	#台北(北區)
	protected function connectOrderTP($table = NULL)
	{
		if (empty($table))
			return DB::connection('OrderTP');
		else
			return DB::connection('OrderTP')->table($table); 
	}
	
	#屯山(北區)
	protected function connectOrderTS($table = NULL)
	{
		if (empty($table))
			return DB::connection('OrderTS');
		else
			return DB::connection('OrderTS')->table($table); 
	}
	
	#高雄(南區)
	protected function connectOrderKH($table = NULL)
	{
		if (empty($table))
			return DB::connection('OrderKH');
		else
			return DB::connection('OrderKH')->table($table); 
	}
	#二崙(南區)
	protected function connectOrderRL($table = NULL)
	{
		if (empty($table))
			return DB::connection('OrderRL');
		else
			return DB::connection('OrderRL')->table($table); 
	}
	
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
	
	/* 原測試機已改為Local MySql */
	/*protected function connectSaleDashboard($table = NULL)
	{
		return $this->connectLocalSalesDashboard($table);
		
		#deprecated
		if (empty($table))
			return DB::connection('SaleDashboard');
		else
			return DB::connection('SaleDashboard')->table($table)->lock('WITH(NOLOCK)');
		
	}*/
}
