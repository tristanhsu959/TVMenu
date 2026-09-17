@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/shipments/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/shipments/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search panel -->
<dialog x-data="searchShipments(@js($viewModel->searchFormData()))" id="searchPanel" class="right">
	<form :action="searchData.formAction" method="post" id="searchForm" novalidate @submit.prevent="search()">
	@csrf
		<h5>查詢</h5>
		
		<div class="field middle-align">
			<nav>
				<template x-for="(name, id) in options.type" :key="id">
					<label class="radio field-red">
						<input type="radio" name="searchType" x-model="searchData.type" :value="id">
						<span x-text="name"></span>
					</label>
				</template>
			</nav>
			<nav x-show="showByOptions">
				<template x-for="(name, id) in options.by" :key="id">
					<label class="radio field-light-blue">
						<input type="radio" name="searchBy" x-model="searchData.by" :value="id">
						<span x-text="name"></span>
					</label>
				</template>
			</nav>
			<nav x-show="showCalcOptions">
				<template x-for="(name, id) in options.calc" :key="id">
					<label class="radio field-purple">
						<input type="radio" name="searchCalc" x-model="searchData.calc" :value="id">
						<span x-text="name"></span>
					</label>
				</template>
			</nav>
		</div>
		<div class="space"></div>
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'stDate')">
			<input type="date" name="searchStDate" maxlength="10" x-model="searchData.stDate" x-ref="searchStDate" @input="errors.delete('stDate')" :max="searchData.tomorrow">
			<label>開始日期</label>
		</div>
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'endDate')">
			<input type="date" name="searchEndDate" maxlength="10" x-model="searchData.endDate" x-ref="searchEndDate" @input="errors.delete('endDate')" :max="searchData.tomorrow">
			<label>結束日期</label>
			<output class="red-text">查詢日期為到貨日期</output>
		</div>
		
		<fieldset x-show="showAreaOptions" class="field light-blue-border light-blue-text">
			<legend class="small">選擇區域</legend>
			<nav class="wrap">
				<template x-for="(areaName, areaId) in options.areaList" :key="areaId">
				<label class="checkbox check-pink">
					<input type="checkbox" :value="areaId" name="searchAreaIds[]" x-model="searchData.areaIds">
					<span x-text="areaName"></span>
				</label>
				</template>
			</nav>
			<output class="red-text small">未選時取全部授權區域</output>
		</fieldset>
		
		<div x-show="showWhereOptions" class="field middle-align">
			<nav class="wrap">
				<template x-for="(name, id) in options.where" :key="id">
					<label class="radio field-purple">
						<input type="radio" name="searchWhere" x-model="searchData.where" :value="id">
						<span x-text="name"></span>
					</label>
				</template>
			</nav>
		</div>
		
		<div x-show="showProductName" class="field label border round field-light-blue" :class="Helper.hasError(errors, 'keyword')">
			<input type="text" name="searchKeyword" maxlength="30" x-model="searchData.keyword" @input="errors.delete('keyword')">
			<label>產品名稱</label>
		</div>
			
		<div x-show="showCategory" class="field label suffix round border field-light-blue" :class="Helper.hasError(errors, 'category')">
			<select x-model="searchData.category" name="searchCategory" :disabled="! showCategory"> <!--@change="searchData.shortCodes = []"-->
				<option value="">請選擇</option>
				<template x-for="(name, catId) in options.category" :key="catId">
					<option x-text="name" :value="catId" :selected="searchData.category == catId"></option>
				</template>
			</select>
			<label>類別</label>
			<i>arrow_drop_down</i>
			<output x-text="`已選 ${searchData.shortCodes.length} 個項目`" class="red-text"></output>
		</div>
		
		<template x-for="(products, catId) in options.products" :key="catId">
			<fieldset x-show="showProduct(catId)" class="light-blue-border fieldset product-list">
				<legend><i class="small red-text">asterisk</i><span class="light-blue-text">請勾選產品</span></legend>
				<template x-for="(item, idx) in products" :key="idx">
					<div class="row">
						<label class="checkbox large s3 check-amber">
							<input type="checkbox" :name="`searchShortCodes[]`" x-model="searchData.shortCodes" :value="item.shortCode">
							<span x-text="`${item.shortCode} ${item.productName}`"></span>
						</label>
					</div>
				</template>
			</fieldset>
		</template>
		
		<div x-show="showStoreName" class="field label border round field-light-blue">
			<input type="text" name="searchStoreName" maxlength="30" x-model="searchData.storeName">
			<label>找店名</label>
		</div>
		
		<div class="space"></div>
		<div>
			<nav class="right-align split group">
				<button type="submit" class="btn-search left-round large"><i>search</i>查詢</button>
				<button @click="resetSearch()" type="button" class="btn-search-reset right-round square large"><i>backspace</i></button>
			</nav>
		</div>
	</form>	
</dialog>
<!-- Search panel end -->

<div x-data="{response:@js($viewModel->responseData())}" class="content-wrapper">
	<header class="page-nav">
		<nav>
			<button type="button" class="btn-show-search button circle extend" data-ui="#searchPanel">
				<i>search</i>
				<span>查詢</span>
			</button>
			
			<template x-if="response.exportAction">
				<a :href="`javascript:window.location.href='${response.exportAction}'`" class="button circle extend red" type="button">
					<i>download_2</i>
					<span>下載</span>
				</a>
			</template>
			<template x-if="response.hasResult && response.hasFilter">
				<nav class="no-space filter">
					<div class="field label border prefix field-filter-dark small">
						<i>filter_alt</i>
						<input type="text" x-model="$store.shipmentStore.filter">
						<label>篩選</label>
					</div>
					<button class="right-round" @click="$store.shipmentStore.filter = ''"><i>backspace</i></button>
				</nav>
			</template>
		</nav>
	</header>
	
	<template x-if="response.status && response.isInit">
		<!-- Loading -->
		<section class="container">
			<pre><i>arrow_warm_up</i>點擊查詢按鈕執行查詢</pre>
			<pre x-show="response.brandCode == 'bafang'" class="red-border">菜肉餡：Qty X 2.5<br/>新蔬食餡：Qty X 1.8</pre>
		</section>
	</template>
	
	<template x-if="!response.status">
		<section class="container">
			<article class="error-container border">
				<div class="row">
					<i>error</i><div class="max">查詢時發生錯誤，請重新查詢</div>
				</div>
			</article>
		</section>
	</template>
	
	<template x-if="response.status && !response.isInit">
		@include($viewModel->getPartialView())
	</template>
</div>
@endsection