<?php

use App\Enums\MenuGroup;
use App\Enums\Brand;
use App\Enums\Functions;
use Illuminate\Support\Str;

#Menu Config (key與route要相同)
return [
	#Group (Enable List)
	MenuGroup::BAFANG->value => [
		[
			'name' 		=> Functions::BF_NEW_RELEASE->label(), #新品銷售
			'code'		=> Functions::BF_NEW_RELEASE->value,
			'style' 	=> ['icon' => 'chart_data', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.new_releases'),
		],
		[
			'name' 		=> Functions::BF_SALES->label(), #銷售查詢
			'code'		=> Functions::BF_SALES->value,
			'style' 	=> ['icon' => 'point_of_sale', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.sales'),
		],
		[
			'name' 		=> Functions::BF_SALE_EVENTS->label(), #銷售活動統計(目前只有八方有)
			'code'		=> Functions::BF_SALE_EVENTS->value,
			'style' 	=> ['icon' => 'event', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.sale_events'),
		],
		[
			'name' 		=> Functions::BF_EZORDER_POS->label(), #八方點
			'code'		=> Functions::BF_EZORDER_POS->value,
			'style' 	=> ['icon' => 'qr_code_scanner', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.ezorder_pos'),
		],
		[
			'name' 		=> Functions::BF_SHIPMENTS->label(), #出貨總量查詢
			'code'		=> Functions::BF_SHIPMENTS->value,
			'style' 	=> ['icon' => 'local_shipping', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.shipments'),
		],
		[
			'name' 		=> Functions::BF_PURCHASE_SUPPLIER->label(), #供應商出貨總量查詢
			'code'		=> Functions::BF_PURCHASE_SUPPLIER->value,
			'style' 	=> ['icon' => 'delivery_truck_speed', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.supplier'),
		],
		[
			'name' 		=> Functions::BF_PURCHASE_NOT_ORDER->label(), #未訂貨查詢
			'code'		=> Functions::BF_PURCHASE_NOT_ORDER->value,
			'style' 	=> ['icon' => 'orders', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.purchase_not_order'),
		],
		[
			'name' 		=> Functions::BF_PURCHASE_PRODUCT_INFO->label(), #產品資訊
			'code'		=> Functions::BF_PURCHASE_PRODUCT_INFO->value,
			'style' 	=> ['icon' => 'pallet', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.purchase_product_info'),
		],
		[
			'name' 		=> Functions::BF_PURCHASE_REPORT->label(), #出貨統計報表(八方only)
			'code'		=> Functions::BF_PURCHASE_REPORT->value,
			'style' 	=> ['icon' => 'analytics', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.purchase_report'),
		],
		[
			'name' 		=> Functions::BF_MONTHLY_FILLING->label(), #月初報表(八方only)
			'code'		=> Functions::BF_MONTHLY_FILLING->value,
			'style' 	=> ['icon' => 'summarize', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.monthly_filling'),
		],
		[
			'name' 		=> Functions::BF_DAILY_REVENUE->label(), #門店營收
			'code'		=> Functions::BF_DAILY_REVENUE->value,
			'style' 	=> ['icon' => 'paid', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.daily_revenue'),
		],
		[
			'name' 		=> Functions::BF_MERCHANT->label(), #門店資訊
			'code'		=> Functions::BF_MERCHANT->value,
			'style' 	=> ['icon' => 'storefront', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.merchant'),
		],
		/* [
			'name' 		=> Functions::BF_PURCHASE_SALES->label(), #門店進貨及銷售
			'code'		=> Functions::BF_PURCHASE_SALES->value,
			'style' 	=> ['icon' => 'finance_mode', 'color' => 'orange-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BAFANG->code()], '?.purchase_sales'),
		], */
	],
	
	MenuGroup::BUYGOOD->value => [
		[
			'name' 		=> Functions::BG_NEW_RELEASE->label(), #新品銷售
			'code'		=> Functions::BG_NEW_RELEASE->value,
			'style' 	=> ['icon' => 'chart_data', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.new_releases'),
		],
		[
			'name' 		=> Functions::BG_SALES->label(), #銷售查詢
			'code'		=> Functions::BG_SALES->value,
			'style' 	=> ['icon' => 'point_of_sale', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.sales'),
		],
		[
			'name' 		=> Functions::BG_EZORDER_POS->label(), #八方點
			'code'		=> Functions::BG_EZORDER_POS->value,
			'style' 	=> ['icon' => 'qr_code_scanner', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.ezorder_pos'),
		],
		[
			'name' 		=> Functions::BG_SHIPMENTS->label(), #出貨總量查詢
			'code'		=> Functions::BG_SHIPMENTS->value,
			'style' 	=> ['icon' => 'local_shipping', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.shipments'),
		],
		[
			'name' 		=> Functions::BG_PURCHASE_SUPPLIER->label(), #供應商出貨總量查詢
			'code'		=> Functions::BG_PURCHASE_SUPPLIER->value,
			'style' 	=> ['icon' => 'delivery_truck_speed', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.supplier'),
		],
		[
			'name' 		=> Functions::BG_PURCHASE_NOT_ORDER->label(), #未訂貨查詢
			'code'		=> Functions::BG_PURCHASE_NOT_ORDER->value,
			'style' 	=> ['icon' => 'orders', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.purchase_not_order'),
		],
		[
			'name' 		=> Functions::BG_PURCHASE_PRODUCT_INFO->label(), #產品資訊
			'code'		=> Functions::BG_PURCHASE_PRODUCT_INFO->value,
			'style' 	=> ['icon' => 'pallet', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.purchase_product_info'),
		],
		/* [
			'name' 		=> Functions::BG_PURCHASE->label(), #進貨統計
			'code'		=> Functions::BG_PURCHASE->value,
			'style' 	=> ['icon' => 'trolley', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.purchase'),
		], */
		[
			'name' 		=> Functions::BG_DAILY_REVENUE->label(), #門店營收
			'code'		=> Functions::BG_DAILY_REVENUE->value,
			'style' 	=> ['icon' => 'paid', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.daily_revenue'),
		],
		[
			'name' 		=> Functions::BG_MERCHANT->label(), #門店資訊
			'code'		=> Functions::BG_MERCHANT->value,
			'style' 	=> ['icon' => 'storefront', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.merchant'),
		],
		/* [
			'name' 		=> Functions::BG_PURCHASE_SALES->label(), #門店進貨及銷售
			'code'		=> Functions::BG_PURCHASE_SALES->value,
			'style' 	=> ['icon' => 'finance_mode', 'color' => 'cyan-text'],
			'url' 		=> Str::replaceArray('?', [Brand::BUYGOOD->code()], '?.purchase_sales'),
		], */
	],
	
	MenuGroup::FJVEGGIE->value => [
		[
			'name' 		=> Functions::FJ_DAILY_REVENUE->label(), #門店營收
			'code'		=> Functions::FJ_DAILY_REVENUE->value,
			'style' 	=> ['icon' => 'paid', 'color' => 'light-green-text'],
			'url' 		=> Str::replaceArray('?', [Brand::FJVEGGIE->code()], '?.daily_revenue'),
		],
	],
	
	MenuGroup::DATA->value => [
		[
			'name' 		=> Functions::AREA_MANAGER->label(), #督導管理,
			'code'		=> Functions::AREA_MANAGER->value,
			'style' 	=> ['icon' => 'location_away', 'color' => 'yellow-text'],
			'url' 		=> 'area_manager', 
		],
	],
	
	MenuGroup::PRODUCT->value => [
		[
			'name' 		=> Functions::PRODUCT->label(), #產品基本資料,
			'code'		=> Functions::PRODUCT->value,
			'style' 	=> ['icon' => 'barcode', 'color' => 'light-blue-text'],
			'url' 		=> 'products', 
		],
		[
			'name' 		=> Functions::NEW_RELEASE_SETTING->label(), #新品設定,
			'code'		=> Functions::NEW_RELEASE_SETTING->value,
			'style' 	=> ['icon' => 'fiber_new', 'color' => 'light-blue-text'],
			'url' 		=> 'new_release_setting', 
		],
		/* [
			'name' 		=> Functions::SALES_SETTING->label(), #銷售設定,
			'code'		=> Functions::SALES_SETTING->value,
			'style' 	=> ['icon' => 'settings_applications', 'color' => 'light-blue-text'],
			'url' 		=> 'sales_setting', 
		], */
		[
			'name' 		=> Functions::SALES_PRODUCT->label(), #銷售產品設定,
			'code'		=> Functions::SALES_PRODUCT->value,
			'style' 	=> ['icon' => 'washoku', 'color' => 'light-blue-text'],
			'url' 		=> 'sales_product', 
		],
		[
			'name' 		=> Functions::PURCHASE_PRODUCT->label(), #訂貨產品設定,
			'code'		=> Functions::PURCHASE_PRODUCT->value,
			'style' 	=> ['icon' => 'warehouse', 'color' => 'light-blue-text'],
			'url' 		=> 'purchase_product', 
		],
	],
	
	MenuGroup::MANAGE->value => [
		[
			'name' 		=> Functions::USER->label(), #帳號管理,
			'code'		=> Functions::USER->value,
			'style' 	=> ['icon' => 'admin_panel_settings', 'color' => 'red-text'],
			'url' 		=> 'users', 
		],
		/* [
			'name' 		=> Functions::STORE_MAP->label(), #門店管理,
			'code'		=> Functions::STORE_MAP->value,
			'style' 	=> ['icon' => 'two_pager_store', 'color' => 'red-text'],
			'url' 		=> 'store_map', 
		], */
		/*
		[
			'name' 		=> Functions::ROLE->label(), #身份管理,
			'code'		=> Functions::ROLE->value,
			'style' 	=> ['icon' => 'how_to_reg', 'color' => 'red-text'],
			'url' 		=> 'roles', 
		],*/
	],
];
