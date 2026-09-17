@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/sales_product/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/sales_product/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
<div x-data="{response:@js($viewModel->responseData())}" class="content-wrapper">
	<header class="page-nav">
		<nav>
			<a :href="response.updateFormAction" class="btn-create button circle"><i>settings</i></a>
			
			<template x-for="(brand, brandId) in response.options.brands">
				<nav x-show="brandId == $store.salesProductSetting.tabIndex" class="no-space filter">
					<div class="field label suffix border field-filter-dark small">
						<select x-model="$store.salesProductSetting.filterCat[brandId]" id="filterCat">
							<option value="">全部</option>
							<template x-for="(catName, catId) in response.options.categories[brandId]" :key="catId">
								<option x-text="catName" :value="catName"></option>
							</template>
						</select>
						<label>篩選分類</label>
						<i>arrow_drop_down</i>
					</div>
				</nav>
			</template>
		</nav>
	</header>
	
	<template x-if="!response.status">
		<section class="container">
			<article class="error-container border">
				<div class="row">
					<i>error</i><div class="max">讀取銷售產品設定時發生錯誤，請重新查詢</div>
				</div>
			</article>
		</section>
	</template>
	
	<template x-if="response.status">
		<section x-data="salesSettingList(@js($viewModel->responseList()))" class="setting-list container">
			<article x-show="!response.hasResult" class="secondary-container border">
				<div class="row">
					<i>info</i><div class="max">查無產品設定</div>
				</div>
			</article>
			
			<div class="list-wrapper">
				<div class="tabs">
					<template x-for="(brand, brandId) in options.brands" :key="brandId">
						<a :data-ui="`#page-${brandId}`" :class="activeTab == brandId ? 'active':''"  @click="$store.salesProductSetting.tabIndex = brandId"">
							<span x-text="brand"></span>
							<span x-text="settings[brandId].length" class="chip round fill"></span>
						</a>
					</template>
				</div>
				
				<template x-for="(brand, brandId) in options.brands" :key="'list_' + brandId">
					<div class="page padding" :id="`page-${brandId}`" :class="activeTab == brandId ? 'active':''">
						<ul class="list border">
							<template x-for="product in filterSettings(brandId)">
								<li>
									<i class="fill extra green-text">check_circle</i>
									<div class="max">
										<h6 x-text="product.name"></h6>
										<div class="small grey-text" x-text="product.category"></div>
									</div>
								</li>
							</template>
						</ul>
					</div>
				</template>
			</div>
		</section>
	</template>
</div>
<!-- Content -->
@endsection