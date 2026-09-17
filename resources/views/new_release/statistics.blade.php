@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/new_release/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/new_release/list.js') }}" defer></script>
@endpush

@section('content')

<!-- Search panel -->
<dialog x-data="searchProduct(@js($viewModel->searchFormData()))" id="searchPanel" class="right">
	<form :action="searchData.formAction" method="post" id="searchForm" novalidate @submit.prevent="search()">
		@csrf
		
		<h5>查詢</h5>
		
		<div class="field label suffix round border field-light-blue" :class="Helper.hasError(errors, 'releaseId')">
			<select x-model="searchData.releaseId" name="searchReleaseId" @change="initSearchStDate($event.target.value);" x-effect="$nextTick(() => $el.value = searchData.releaseId)">
				<option value="0">請選擇</option>
				<template x-for="(item, key) in options.newReleaseProducts" :key="key">
					<option x-text="item.name" :value="item.id"></option>
				</template>
			</select>
			<label>新品</label>
			<i>arrow_drop_down</i>
		</div>
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'stDate')">
			<input type="date" name="searchStDate" maxlength="10" x-model="searchData.stDate" @input="errors.delete('stDate')" :max="searchData.today" :min="setMinDate">
			<label>開始日期</label>
		</div>
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'endDate')">
			<input type="date" name="searchEndDate" maxlength="10" x-model="searchData.endDate" @input="errors.delete('endDate')" :max="searchData.today" :min="setMinDate">
			<label>結束日期</label>
		</div>
		
		<fieldset x-show="Object.keys(options.areaList).length > 0" class="field light-blue-border light-blue-text">
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
		<section class="new-release-list container">
			<article x-show="!response.hasResult" class="secondary-container border">
				<div class="row">
					<i>info</i><div class="max">查無銷售資料</div>
				</div>
			</article>
		
			<div x-show="response.hasResult" class="statistics">
				<div class="tabs cyan-text">
					<a class="active" data-ui="#tab-area">區域彙總</a>
					<a data-ui="#tab-shop">店別明細</a>
					<a data-ui="#tab-ranking-asc">當日銷售前10名</a>
					<a data-ui="#tab-ranking-desc">當日銷售後10名</a>
				</div>
		
				@include('new_release.area')
				@include('new_release.store')
				@include('new_release.ranking')
			</div>	
		</section>
	</template>
</div>
@endsection