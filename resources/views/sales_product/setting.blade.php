@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
	<!--link href="{{ asset('styles/product/detail.css') }}" rel="stylesheet"-->
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/sales_product/setting.js') }}" defer></script>
@endpush

@section('content')


	
<section x-data="salesSettingForm(@js($viewModel->responseDetail()))" class="sales-product-data container">
	<form :action="formData.formAction" method="post" novalidate @submit.prevent="validate()">
	@csrf
		<div class="field label suffix border field-dark-blue w25 prefix" :class="Helper.hasError(errors, 'brandId')">
			<i class="small red-text">asterisk</i>
			<select x-model="formData.brandId" name="brandId" @change="errors.delete('brandId');">
				<template x-for="(name, brandId) in options.brands" :key="brandId">
					<option :value="brandId" x-text="name" :selected="formData.brandId == brandId"></option>
				</template>
			</select>
			<label>品牌</label>
			<i>arrow_drop_down</i>
		</div>
		
		<p class="medium-text red-text">請勾選啟用產品</p>
		<template x-for="(groupList, brandId) in options.products" :key="brandId">
		<div>
			<template x-for="(group, groupId) in groupList" :key="groupId">
			<fieldset class="field-dark-blue fieldset" x-show="brandId == formData.brandId">
				<legend><i class="small red-text">asterisk</i><span x-text="group.catName"></span></legend>
					<div class="grid">
						<template x-for="(product, idx) in group.products" :key="idx">
						<label class="checkbox large s3 check-pink">
							<input type="checkbox" :name="`productIds[${brandId}][]`" x-model="formData.productIds[brandId]" :value="product.id">
							<span x-text="product.name"></span>
						</label>
						</template>
					</div>
			</fieldset>
			</template>
		</div>
		</template>
		
		<div class="space"></div>
		<nav class="toolbar">
			<button type="submit" class="button btn-save btn-primary slow-ripple">儲存設定</button>
			<button @click="reset() "type="button" class="button btn-cancel border slow-ripple">重置</button>
		</nav>
	</form>
</section>


@endsection