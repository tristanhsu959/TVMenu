@extends('layouts.app')

@push('styles')
	<!--link href="{{ asset('styles/user/detail.css') }}" rel="stylesheet"-->
@endpush

@push('scripts')
    <script src="{{ asset('scripts/user/detail.js') }}" defer></script>
@endpush

@section('content')

<form x-data='userForm(@json($viewModel->formData), @json($viewModel->options))' action="{{ $viewModel->getFormAction() }}" method="post" novalidate @submit.prevent="validate()">
	<input type="hidden" name="id" :value="formData.id" x-model="formData.id">
	@csrf
	
	<section class="user-data container">
		<label x-show="formData.id > 0" class="large-text" x-text="`更新時間：${formData.updateAt}`"></label>
		
		<div class="field label border field-purple w30 prefix" :class="Helper.hasError(errors, 'ad')">
			<i class="small red-text">asterisk</i>
			<input type="text" name="adAccount" maxlength="15" required x-model="formData.ad" @input="errors.delete('ad')">
			<label>AD帳號</label>
		</div>
		
		<div class="field label border field-purple w30">
			<input type="text" name="displayName" maxlength="15" x-model="formData.name">
			<label>顯示名稱</label>
		</div>
		
		<div class="field label suffix border field-purple w30 prefix" :class="Helper.hasError(errors, 'roleId')">
			<i class="small red-text">asterisk</i>
			<select x-model="formData.roleId" name="roleId"  @change="errors.delete('roleId')">
				<option value="">請選擇</option>
				<template x-for="(name, id) in options.roleList" :key="id">
					<option :value="id" x-text="name" :selected="formData.roleId == id"></option>
				</template>
			</select>
			<label>身份</label>
			<i>arrow_drop_down</i>
		</div>
		
		<div class="space"></div>
		<nav class="toolbar">
			<button type="submit" class="button btn-save btn-primary slow-ripple">{{ $viewModel->action->label()}}</button>
			<button @click="reset() "type="button" class="button btn-cancel border slow-ripple">重置</button>
		</nav>
	</section>
</form>

@endsection