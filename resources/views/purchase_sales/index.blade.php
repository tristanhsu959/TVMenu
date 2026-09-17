@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/purchase_sales/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/purchase_sales/list.js') }}" defer></script>
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
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'date')">
			<input type="date" name="searchDate" maxlength="10" x-model="searchData.date" x-ref="searchDate" @input="errors.delete('date')" :max="searchData.today">
			<label>查詢日期</label>
		</div>
		
		<div x-show="searchData.type == 'area'">
			<div class="field label suffix round border field-light-blue" :class="Helper.hasError(errors, 'areaId')">
				<select x-model="searchData.areaId" name="searchAreaId" :disabled="searchData.type != 'area'" @change="errors.delete('areaId')">
					<option value="">請選擇</option>
					<template x-for="(name, areaId) in options.areaList" :key="areaId">
						<option x-text="name" :value="areaId" :selected="searchData.areaId == areaId"></option>
					</template>
				</select>
				<label>區域</label>
				<i>arrow_drop_down</i>
			</div>
		</div>
		
		<div x-show="searchData.type == 'storeName'">
			<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'storeName')">
				<input type="text" name="searchStoreName" maxlength="30" x-model="searchData.storeName" :disabled="searchData.type != 'storeName'" @input="errors.delete('storeName')">
				<label>門店名稱</label>
			</div>
		</div>
		
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
		<section x-data="storeList(@js($viewModel->statisticsData()))" class="store-list container">
			<article x-show="!response.hasResult" class="secondary-container border">
				<div class="row">
					<i>info</i><div class="max">查無符合資料</div>
				</div>
			</article>
			
			<form :action="response.detailFormAction" method="post" x-ref="detailForm" class="no-margin" novalidate>
				@csrf
				<input type="hidden" name="searchStoreId" x-model="formData.storeId">
				<input type="hidden" name="searchDate" x-model="formData.date">
			</form>
			
			<div class="store-content">
				<article class="info border">
					<div class="row">
						<div x-text="listHeader"></div>
					</div>
				</article>
				<!-- 門店 -->
				<section class="statistics-store scrollbar" :class="response.brandCode">
					<table class="stripes">
						<thead>
							<tr>
								<template x-for="(header, idx) in statistics.store.header" :key="idx">
									<th x-text="header"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(store, idx) in statistics.store.data" :key="idx">
							<tr>
								<td x-text="store['posId']"></td>
								<td x-text="store['areaName']"></td>
								<td x-text="store['storeKey']"></td>
								<td x-text="store['storeName']"></td>
								<td x-text="store['address']"></td>
								<td x-text="store['bossName']"></td>
								<td x-text="store['openDate']"></td>
								<td><button :data-id="store['storeId']" @click="getDetail($el.getAttribute('data-id'))"><i>more_horiz</i><span>More</span></button></td>
							</tr>
							</template>
						</tbody>
					</table>
				</section>
			</div>
		</section>
	</template>
@endsection