@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/sale_events/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/sale_events/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search panel -->
<dialog x-data="searchEvents(@js($viewModel->searchFormData()))" id="searchPanel" class="right">
	<form :action="searchData.formAction" method="post" id="searchForm" novalidate @submit.prevent="search()" class="vertical">
	@csrf
		<h5>查詢</h5>
		
		<div class="field middle-align">
			<nav class="wrap">
				<template x-for="(name, id) in options.type" :key="id">
					<label class="radio field-red">
						<input type="radio" name="searchType" x-model="searchData.type" :value="id">
						<span x-text="name"></span>
					</label>
				</template>
			</nav>
		</div>
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'stDate')">
			<input type="date" name="searchStDate" maxlength="10" x-model="searchData.stDate" @input="errors.delete('stDate')" :min="searchData.eventStDate" :max="searchData.eventEndDate">
			<label>開始日期</label>
		</div>
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'endDate')">
			<input type="date" name="searchEndDate" maxlength="10" x-model="searchData.endDate" @input="errors.delete('endDate')" :min="searchData.eventStDate" :max="searchData.eventEndDate">
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
			
			<template x-if="response.hasResult && response.hasFilter">
				<nav x-show="$store.saleEvents.showFilter" class="no-space filter">
					<div class="field label border prefix field-filter-dark small">
						<i>filter_alt</i>
						<input type="text" x-model="$store.saleEvents.filter">
						<label>篩選</label>
					</div>
					<button class="right-round" @click="$store.saleEvents.filter = ''"><i>backspace</i></button>
				</nav>
			</template>
		</nav>
	</header>
	
	<template x-if="response.status && response.isInit">
		<!-- Loading -->
		<section class="container">
			<pre><i>arrow_warm_up</i>點擊查詢按鈕執行查詢</pre>
			<pre class="red-border red-text"><i>calendar_clock</i>八方X美廉社中秋聯名活動：2026-09-02 ~ 2026-10-05</pre>
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
		<section class="sales-list container">
			<article x-show="!response.hasResult" class="secondary-container border">
				<div class="row">
					<i>info</i><div class="max">查無銷售資料</div>
				</div>
			</article>
			
			<div x-show="response.hasResult" class="container">
				@include('sale_events.moon_festival')
			</div>
		</section>
	</template>
</div>

@endsection