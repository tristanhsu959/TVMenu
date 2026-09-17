<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum Functions : string
{
	case HOME						= 'home';
	case USER						= 'user';
	case ROLE 						= 'role'; #deprecated
	case AREA_MANAGER				= 'area_manager'; #deprecated
		
	#Product	
	case PRODUCT 					= 'product';
	case NEW_RELEASE_SETTING		= 'new_releases_setting';
	case SALES_SETTING				= 'sales_setting'; #deprecated
	case SALES_PRODUCT				= 'sales_product';
	case PURCHASE_PRODUCT			= 'purchase_product';
			
	#Bafang		
	case BF_NEW_RELEASE				= 'bafang:new_releases';
	case BF_SALES					= 'bafang:sales';
	case BF_SALE_EVENTS				= 'bafang:sale_events'; #bafang only
	case BF_EZORDER_POS				= 'bafang:ezorder_pos';
	case BF_DAILY_REVENUE			= 'bafang:daily_revenue';
	case BF_SHIPMENTS				= 'bafang:shipments';
	case BF_PURCHASE_REPORT			= 'bafang:purchase_report';
	case BF_MONTHLY_FILLING			= 'bafang:monthly_filling';
	case BF_MERCHANT				= 'bafang:merchant';
	case BF_PURCHASE_SALES			= 'bafang:purchase_sales';
	case BF_PURCHASE_NOT_ORDER		= 'bafang:purchases_not_order';
	case BF_PURCHASE_PRODUCT_INFO	= 'bafang:purchase_product_info';
	case BF_PURCHASE_SUPPLIER		= 'bafang:purchase_supplier';
		
	#Buygood	
	case BG_NEW_RELEASE				= 'buygood:new_releases';
	case BG_SALES					= 'buygood:sales';
	case BG_EZORDER_POS				= 'buygood:ezorder_pos';
	case BG_DAILY_REVENUE			= 'buygood:daily_revenue';
	#case BG_PURCHASE				= 'buygood:purchase';
	case BG_SHIPMENTS				= 'buygood:shipments';
	case BG_MERCHANT				= 'buygood:merchant';
	case BG_PURCHASE_SALES			= 'buygood:purchase_sales';
	case BG_PURCHASE_NOT_ORDER		= 'buygood:purchases_not_order';
	case BG_PURCHASE_PRODUCT_INFO	= 'buygood:purchase_product_info';
	case BG_PURCHASE_SUPPLIER		= 'buygood:purchase_supplier';
	
	#FjVeggie	
	case FJ_DAILY_REVENUE		= 'fjVeggie:daily_revenue';
	
	
	public function label() : string
    {
        return match ($this) 
		{
			#權限系統管理
			self::HOME						=> '首頁',
			self::USER						=> '帳號管理',
			self::ROLE 						=> '身份管理',
			self::AREA_MANAGER 				=> '督導維護',
				
			#產品管理	
			self::PRODUCT 					=> '產品料號設定',
			self::NEW_RELEASE_SETTING		=> '新品設定',
			self::SALES_SETTING				=> '銷售設定', #deprecated
			self::SALES_PRODUCT				=> '銷售產品設定',
			self::PURCHASE_PRODUCT			=> '出貨產品設定',
				
			#八方	
			self::BF_NEW_RELEASE			=> '新品銷售',
			self::BF_SALES 					=> '銷售查詢',
			self::BF_SALE_EVENTS			=> '銷售活動統計',
			self::BF_EZORDER_POS			=> '八方點統計',
			self::BF_SHIPMENTS 				=> '出貨總量查詢',
			self::BF_PURCHASE_REPORT 		=> '出貨統計報表',
			self::BF_MONTHLY_FILLING		=> '月初報表',
			self::BF_DAILY_REVENUE			=> '門店營收',
			self::BF_MERCHANT				=> '門店資訊',
			self::BF_PURCHASE_SALES			=> '門店進貨及銷售(Beta)',
			self::BF_PURCHASE_NOT_ORDER		=> '未訂貨查詢',
			self::BF_PURCHASE_PRODUCT_INFO 	=> '訂貨產品資訊',
			self::BF_PURCHASE_SUPPLIER 		=> '供應商出貨總量查詢',
			
			#御廚
			self::BG_NEW_RELEASE 			=> '新品銷售',
			self::BG_SALES 					=> '銷售查詢',
			self::BG_EZORDER_POS			=> '八方點統計',
			self::BG_SHIPMENTS 				=> '出貨總量查詢',
			#self::BG_PURCHASE 				=> '出貨統計報表',
			self::BG_DAILY_REVENUE			=> '門店營收',
			self::BG_MERCHANT				=> '門店資訊',
			self::BG_PURCHASE_SALES			=> '門店進貨及銷售(Beta)',
			self::BG_PURCHASE_NOT_ORDER		=> '未訂貨查詢',
			self::BG_PURCHASE_PRODUCT_INFO 	=> '訂貨產品資訊',
			self::BG_PURCHASE_SUPPLIER 		=> '供應商出貨總量查詢',
				
			#芳珍	
			self::FJ_DAILY_REVENUE			=> '門店營收',
        };
    }
	
	#key-value array
	public static function mapWithGroupKeys(): array
	{
		$list = [];
		foreach(self::cases() as $case)
		{
			if ($case->value > 0)
			{
				$prefix = '';
				
				if (Str::startsWith($case->value, 'bafang'))
					$prefix = '八方：';
				else if (Str::startsWith($case->value, 'buygood'))
					$prefix = '御廚：';
				else if (Str::startsWith($case->value, 'fjVeggie'))
					$prefix = '芳珍：';
					
				$list[$case->value] = Str::start($case->label(), $prefix);
			}
		}
		
		return $list;
	}
	
	public static function getAll(): array
	{
		$list = [];
		foreach(self::cases() as $case)
		{
			$list[] = $case->value;
		}
		
		return $list;
	}
}
