@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
	<!--link href="{{ asset('styles/product/detail.css') }}" rel="stylesheet"-->
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/product/detail.js') }}" defer></script>
@endpush

@section('content')

	
<section x-data="productForm(@js($viewModel->responseDetail()))" class="product-data container">
	<form :action="formData.formAction" method="post" novalidate @submit.prevent="validate()">
		<input type="hidden" name="id" :value="formData.id" x-model="formData.id">
		@csrf

		<div class="field label suffix border field-dark-blue w25 prefix" :class="Helper.hasError(errors, 'brandId')">
			<i class="small red-text">asterisk</i>
			<select x-model="formData.brandId" name="brandId" @change="initErpNoInput(); initCategory();">
				<option value="">請選擇</option>
				<template x-for="(name, id) in options.brands" :key="id">
					<option :value="id" x-text="name" :selected="formData.brandId == id"></option>
				</template>
			</select>
			<label>品牌</label>
			<i>arrow_drop_down</i>
		</div>
		
		<div class="field label suffix border field-dark-blue w25 prefix">
			<select x-model="formData.category" name="category">
				<option value="">請選擇</option>
				<template x-for="(name, id) in options.categories[formData.brandId]" :key="id">
					<option :value="id" x-text="name" :selected="formData.category == id"></option>
				</template>
			</select>
			<label>分類</label>
			<i>arrow_drop_down</i>
		</div>
		
		<div class="field label border field-dark-blue w40 prefix" :class="Helper.hasError(errors, 'name')">
			<i class="small red-text">asterisk</i>
			<input type="text" name="name" maxlength="15" x-model="formData.name" @input="errors.delete('name')">
			<label>產品名稱</label>
		</div>
		 
		<div class="row top-align">
			<div class="field label border field-dark-blue w40 prefix" :class="Helper.hasError(errors, 'primaryNo')">
				<i class="small red-text">asterisk</i>
				<textarea x-model="formData.primaryNo" @input="errors.delete('primaryNo')" name="primaryNo" rows="15" placeholder=" "></textarea>
				<label>主要ERP No</label>
				<output class="red-text">每個序號以換行分隔</output>
  			</div>
			<div x-show="hasSecondaryNo" class="field label border field-dark-blue w40">
				<textarea x-model="formData.secondaryNo" :disabled="!hasSecondaryNo" name="secondaryNo" rows="15" placeholder=" "></textarea>
				<label>複合店ERP No</label>
				<output class="red-text">每個序號以換行分隔</output>
  			</div>
		</div>
		
		<div class="space"></div>
		<nav class="toolbar">
			<button type="submit" class="button btn-save btn-primary slow-ripple">{{ $viewModel->action->label()}}</button>
			<button @click="reset() "type="button" class="button btn-cancel border slow-ripple">重置</button>
		</nav>
	</form>
</section>

@endsection