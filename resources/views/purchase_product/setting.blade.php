@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
	<!--link href="{{ asset('styles/product/detail.css') }}" rel="stylesheet"-->
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/purchase_product/setting.js') }}" defer></script>
@endpush

@section('content')

<form x-data='purchaseSettingForm(@json($viewModel->formData), @json($viewModel->options))' 
	action="{{ $viewModel->getFormAction() }}" method="post" novalidate @submit.prevent="validate()">
	@csrf
	
	<section class="sales-setting-data container">
		<div class="field label suffix border field-dark-blue w25 prefix" :class="Helper.hasError(errors, 'brandId')">
			<i class="small red-text">asterisk</i>
			<select x-model="formData.brandId" name="brandId" @change="errors.delete('brandId');">
				<template x-for="(name, id) in options.brands" :key="id">
					<option :value="id" x-text="name" :selected="formData.brandId == id"></option>
				</template>
			</select>
			<label>品牌</label>
			<i>arrow_drop_down</i>
		</div>
		
		<p class="medium-text red-text">請勾選啟用產品</p>
		<template x-for="(group, brand) in options.products" :key="brand">
		<div>
			<template x-for="(item, idx) in group" :key="idx">
			<fieldset class="field-dark-blue fieldset" x-show="brand == formData.brandId">
				<legend><i class="small red-text">asterisk</i><span x-text="item.groupName"></span></legend>
					<div class="grid">
						<template x-for="(name, code) in item.products" :key="code">
						<label class="checkbox large s4 check-amber">
							<input type="checkbox" :name="`productCodes[${brand}][]`" x-model="formData.productCodes[brand]" :value="code">
							<span x-text="code + name"></span>
						</label>
						</template>
						<!--div class="space s12"></div-->
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
	</section>
</form>

@endsection