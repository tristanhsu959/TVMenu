@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/purchase_product_info/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/purchase_product_info/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search panel -->
<dialog x-data="searchProductInfo(@js($viewModel->searchFormData()))" id="searchPanel" class="right">
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
		</div>
		
		<div class="field middle-align">
			<nav class="wrap">
				<label class="checkbox check-blue large">
					<input type="checkbox" value="0" name="searchProductTypes[]" checked="checked" disabled="disabled">
					<span>一般產品</span>
				</label>
				<template x-for="(typeName, typeId) in options.productTypes" :key="typeId">
					<label class="checkbox check-blue large">
						<input type="checkbox" :value="typeId" name="searchProductTypes[]" x-model="searchData.productTypes">
						<span x-text="typeName"></span>
					</label>
				</template>
			</nav>
		</div>
		
		<fieldset class="field light-blue-border light-blue-text">
			<legend class="small">選擇工廠</legend>
			<nav class="wrap">
				<template x-for="(factoryName, factoryId) in options.factoryList" :key="factoryId">
					<label class="checkbox check-pink">
						<input type="checkbox" :value="factoryId" name="searchFactoryIds[]" x-model="searchData.factoryIds">
						<span x-text="factoryName"></span>
					</label>
				</template>
			</nav>
			<output class="red-text small">未選時取全部</output>
		</fieldset>
		
		<div class="field middle-align">
			<nav>
				<label class="switch">
					<input type="checkbox" name="searchOffShelf" x-model="searchData.offShelf" value="1">
					<span></span>
				</label>
				<div class="max">
					<div>包含下架產品</div>
				</div>
			</nav>
		</div>
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'shortCode')">
			<input type="text" name="searchShortCode" maxlength="4" x-model="searchData.shortCode" @input="errors.delete('shortCode')">
			<label>產品簡碼(4碼)</label>
			<output class="red-text small">不過濾預購及供應商產品</output>
		</div>
		
		<div class="field label border round field-light-blue" :class="Helper.hasError(errors, 'productName')">
			<input type="text" name="searchProductName" maxlength="30" x-model="searchData.productName" @input="errors.delete('productName')">
			<label>產品名稱</label>
			<output class="red-text small">不過濾預購及供應商產品</output>
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
		@include($viewModel->getPartialView())
	</template>
</div>
@endsection