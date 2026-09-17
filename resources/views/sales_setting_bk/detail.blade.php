@extends('layouts.app')

@push('styles')
	<!--link href="{{ asset('styles/product/detail.css') }}" rel="stylesheet"-->
@endpush

@push('scripts')
    <script src="{{ asset('scripts/sales_setting/detail.js') }}" defer></script>
@endpush

@section('content')

<form x-data='salesSettingForm(@json($viewModel->formData), @json($viewModel->options))' 
	action="{{ $viewModel->getFormAction() }}" method="post" novalidate @submit.prevent="validate()">
	<input type="hidden" name="id" value="{{$viewModel->formData['id']}}" x-model="formData.id">
	@csrf
	
	<section class="sales-setting-data container">
		@if(! empty($viewModel->formData['id']))
			<label class="large-text" x-text="'更新時間：' + formData.updateAt"></label>
		@endif
		<div class="field label suffix border field-dark-blue w20 prefix" :class="Helper.hasError(errors, 'brandId')">
			<i class="small red-text">asterisk</i>
			<select x-model="formData.brandId" name="brandId" @change="errors.delete('brandId'); updateProducts();">
				<template x-for="(name, id) in options.brands" :key="id">
					<option :value="id" x-text="name" :selected="formData.brandId == id"></option>
				</template>
			</select>
			<label>品牌</label>
			<i>arrow_drop_down</i>
		</div>
		
		<div class="field label border field-dark-blue w30 prefix" :class="Helper.hasError(errors, 'name')">
			<i class="small red-text">asterisk</i>
			<input type="text" name="name" maxlength="20" x-model="formData.name" @input="errors.delete('name')">
			<label>產品名稱</label>
		</div>
		
		<fieldset class="field-dark-blue fieldset">
			<legend><i class="small red-text">asterisk</i>選擇對應產品料號</legend>
			<template x-for="cat in products">
				<div class="grid">
					<template x-for="(item, idx) in cat" :key="idx">
					<label class="checkbox large s2 check-amber">
						<input type="checkbox" name="productIds[]" x-model="formData.productIds" :value="item.productId">
						<span x-text="item.productName"></span>
					</label>
					</template>
					<div class="space s12"></div>
				</div>
			</template>
		</fieldset>
		
		<div class="row">
			<label class="switch field-light-green">
				<input x-model="formData.status" :checked="formData.status == 1" @change="formData.status = $el.checked ? 1 : 0" type="checkbox" name="status" value="1">
				<span></span>
				<i class="output">啟用</i>
			</label>
		</div>
		
		<div class="space"></div>
		<nav class="toolbar">
			<button type="submit" class="button btn-save btn-primary slow-ripple">{{ $viewModel->action->label()}}</button>
			<button @click="reset() "type="button" class="button btn-cancel border slow-ripple">重置</button>
		</nav>
	</section>
</form>

@endsection