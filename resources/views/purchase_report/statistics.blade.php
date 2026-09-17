@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/purchase_report/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/purchase_report/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search panel -->
<dialog x-data="searchReport(@js($viewModel->searchFormData()))" id="searchPanel" class="right">
	<form :action="searchData.formAction" method="post" id="searchForm" novalidate @submit.prevent="search()">
	@csrf
		<h5>查詢</h5>
		<div class="field label suffix round border field-light-blue">
			<select x-model="searchData.type" name="searchType">
				<template x-for="(name, typeId) in options.type" :key="typeId">
					<option x-text="name" :value="typeId" :selected="searchData.type == typeId"></option>
				</template>
			</select>
			<label>報表類型</label>
			<i>arrow_drop_down</i>
		</div>
		
		<div x-show="showBrandList" class="field middle-align">
			<nav>
				<template x-for="(name, id) in options.brand" :key="id">
					<label class="radio field-red">
						<input type="radio" name="searchBrand" x-model="searchData.brand" :value="id" :disabled="!showBrandList">
						<span x-text="name"></span>
					</label>
				</template>
			</nav>
		</div>
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'stDate')">
			<input type="date" name="searchStDate" maxlength="7" x-model="searchData.stDate" x-ref="searchStDate" @input="errors.delete('stDate')" :max="searchData.tomorrow">
			<label>開始日期</label>
		</div>
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'endDate')">
			<input type="date" name="searchEndDate" maxlength="7" x-model="searchData.endDate" x-ref="searchEndDate" @input="errors.delete('endDate')" :max="searchData.tomorrow">
			<label>結束日期</label>
			<output class="red-text">查詢日期為到貨日期</output>
		</div>
		
		<fieldset x-show="showOpCenterList" class="field light-blue-border light-blue-text">
			<legend class="small">選擇營運中心</legend>
			<nav class="wrap">
				<template x-for="(opName, opId) in options.opCenterList" :key="opId">
				<label class="checkbox check-pink">
					<input type="checkbox" :value="opId" name="searchOpCenterIds[]" x-model="searchData.opCenterIds">
					<span x-text="opName"></span>
				</label>
				</template>
			</nav>
			<output class="red-text small">未選時取全部授權營運中心</output>
		</fieldset>
		
		<fieldset x-show="showAreaList" class="field light-blue-border light-blue-text">
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
		
		<div class="space"></div>
		<nav class="right-align group split">
			<button type="submit" class="btn-search left-round large"><i>search</i>查詢</button>
			<button @click="resetSearch()" type="button" class="btn-search-reset right-round square large"><i>backspace</i></button>
		</nav>
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