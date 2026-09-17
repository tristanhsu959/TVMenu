@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/merchant/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/merchant/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search panel -->
<dialog x-data="search(@js($viewModel->searchFormData()))" id="searchPanel" class="right">
	<form :action="searchData.formAction" method="post" id="searchForm" novalidate @submit.prevent="search()">
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
			<input type="date" name="searchStDate" maxlength="10" x-model="searchData.stDate" x-ref="searchStDate" @input="errors.delete('stDate')" :max="searchData.tomorrow" :disabled="searchData.type != 'dayOff'">
			<label>查詢日期</label>
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
				<nav class="no-space filter">
					<div class="field label border prefix field-filter-dark small">
						<i>filter_alt</i>
						<input type="text" x-model="$store.merchantInfo.filter">
						<label>篩選</label>
						<span class="tooltip right">區域|Pos店號|門店代號|門店名稱|統一編號|督導</span>
					</div>
					<button class="right-round" @click="$store.merchantInfo.filter = ''"><i>backspace</i></button>
				</nav>
			</template>
		</nav>
	</header>
	
	<template x-if="response.status && response.isInit">
		<!-- 不可有空格 -->
		<section class="container">
			<pre><i>arrow_warm_up</i>點擊查詢按鈕執行查詢</pre>
			<pre x-show="response.brandCode == 'bafang'" class="red-border">店休資訊判別條件：<br/>招牌餡、韭菜餡、韓式辣味餡訂貨量</pre>
			<pre x-show="response.brandCode == 'buygood'" class="red-border">店休資訊判別條件：<br/>醃漬排骨、醃漬雞腿訂貨量</pre>
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