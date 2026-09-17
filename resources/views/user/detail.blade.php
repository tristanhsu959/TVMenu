@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
	<link href="{{ HelperLib::versionAsset('styles/user/detail.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/user/detail.js') }}" defer></script>
@endpush

@section('content')

<div x-data="userForm(@js($viewModel->detailResponseData()), @js($viewModel->detailData()))" class="content-wrapper">
<form :action="formAction" method="post" novalidate @submit.prevent="validate()">
	<input type="hidden" name="id" :value="formData.id" x-model="formData.id">
	@csrf
	
	<section class="user-data container">
		<label x-show="formData.id > 0" class="large-text" x-text="`更新時間：${formData.updateAt}`"></label>
		
		<div class="grid">
			<div class="field label border field-purple prefix s3" :class="Helper.hasError(errors, 'account')">
				<i class="small red-text">asterisk</i>
				<input type="text" name="account" maxlength="20" required x-model="formData.account" @input="errors.delete('account')">
				<label>帳號</label>
			</div>
		
			<div class="field label border field-purple prefix suffix s4" :class="Helper.hasError(errors, 'password')">
				<i class="small red-text">asterisk</i>
				<input :type="showPassword ? 'text':'password'" name="password" maxlength="15" required x-model="formData.password" @input="errors.delete('password')">
				<label>密碼</label>
				<output class="red-text">英文+數字六個字元以上</output>
				<i class="btn-icon">
					<button type="button" class="large square" @click="showPassword = !showPassword">
						<i x-show="!showPassword">visibility</i>
						<i x-show="showPassword">visibility_off</i>
					</button>
				</i>
			</div>
			<div class="s5 password-hint">
				<div x-show="formData.hasSetPassword && formData.id > 0" class="green-text"><i>check</i><span>系統密碼已設定</span></div>
				<div x-show="! formData.hasSetPassword && formData.id > 0" class="red-text"><i>close</i><span>尚未設定系統密碼</span></div>
			</div>
			
			<div class="field label border field-purple s3">
				<input type="text" name="displayName" maxlength="15" x-model="formData.displayName">
				<label>顯示名稱</label>
			</div>
		
			<div class="field label border field-purple s4">
				<input type="text" name="department" maxlength="15" required x-model="formData.department">
				<label>部門</label>
			</div>
			
			<div class="field label border field-purple s5">
				<input type="text" name="email" maxlength="50" required x-model="formData.email">
				<label>EMail</label>
			</div>
			
			<div class="field label border field-purple s7">
				<input type="text" name="description" maxlength="50" required x-model="formData.description">
				<label>說明</label>
			</div>
			
			<label class="switch field-light-green s2">
				<input x-model="formData.isActive" :checked="formData.isActive == 1" @change="formData.isActive = $el.checked ? 1 : 0" type="checkbox" name="isActive" value="1">
				<span></span>
				<i class="output">啟用</i>
			</label>
		</div>
		
		<!-- Tabs -->
		<article class="border">
			<div class="tabs cyan-text">
				<template x-for="(groups, groupName) in options.functions" :key="groupName">
					<a :data-ui="`#page-${groupName}`" x-text="groupName" :class="activeTab == groupName ? 'active':''" ></a>
				</template>
				
				<a data-ui="#page-sales-area" :class="activeTab == 'sales-area' ? 'active':''">銷售區域權限</a>
				<a data-ui="#page-purchase-area" :class="activeTab == 'purchase-area' ? 'active':''">訂貨區域權限</a>
			</div>
			
			<template x-for="(groups, groupName) in options.functions" :key="`list-${groupName}`">
			<div class="page padding" :id="`page-${groupName}`" :class="activeTab == groupName ? 'active':''">
				<fieldset class="role-permission field-blue fieldset required surface-container-high">
					<ul class="list border">
						<template x-for="(item, idx) in groups" :key="idx">
						<li class="">
							<div class="max">
								<h6 class="small"></h6>
								<div x-text="item.name"></div>
							</div>
							<label class="switch field-dark-blue">
								<input x-model="formData.permission" type="checkbox" name="permission[]" :value="item.code">
								<span></span>
							</label>
						</li>
						</template>
					</ul>
				</fieldset>
			</div>
			</template>
			
			<!--區域權限-->
			<div class="page padding" id="page-sales-area" :class="activeTab == 'sales-area' ? 'active':''">
				<fieldset class="area-permission field-blue fieldset required surface-container-high">
					<ul class="list border">
						<template x-for="(areaName, areaId) in options.salesAreas" :key="areaId">
						<li>
							<div class="max">
								<h6 class="small"></h6>
								<div x-text="areaName"></div>
							</div>
							<label class="switch field-dark-blue">
								<input x-model="formData.area.sales" type="checkbox" name="area[sales][]" :value="areaId">
								<span></span>
							</label>
						</li>
						</template>
					</ul>
				</fieldset>
				
				<!--fieldset class="area-permission field-blue fieldset required">
					<template x-for="(areaName, areaId) in options.areas" :key="areaId">
						<label class="form-check-label" :for="`area-${areaId}`">
							<input x-model="formData.area" class="form-check-input" type="checkbox" name="area[]" :id="`area-${areaId}`" :value="areaId">
							<span x-text="areaName"></span>
						</label>
					</template>
				</fieldset-->
			</div>

			<div class="page padding" id="page-purchase-area" :class="activeTab == 'purchase-area' ? 'active':''">
				<fieldset class="area-permission field-red fieldset required surface-container-high">
					<legend>指定營運中心(主要影響中彰投區門店|未指定則依品牌判別)</legend>
					<ul class="list border">
						<template x-for="(opName, opId) in options.opCenters" :key="opId">
						<li class="red-text">
							<div class="max">
								<h6 class="small"></h6>
								<div x-text="opName"></div>
							</div>
							<label class="switch field-red">
								<input x-model="formData.area.opCenter" type="checkbox" name="area[opCenter][]" :value="opId">
								<span></span>
							</label>
						</li>
						</template>
					</ul>
				</fieldset>
				<fieldset class="area-permission field-blue fieldset required surface-container-high">
					<ul class="list border">
						<template x-for="(areaName, areaId) in options.purchaseAreas" :key="areaId">
						<li>
							<div class="max">
								<h6 class="small"></h6>
								<div x-text="areaName"></div>
							</div>
							<label class="switch field-dark-blue">
								<input x-model="formData.area.purchase" type="checkbox" name="area[purchase][]" :value="areaId">
								<span></span>
							</label>
						</li>
						</template>
					</ul>
				</fieldset>
			</div>				
		</article>
		
		<div class="space"></div>
		<nav class="toolbar">
			<button type="submit" class="button btn-save btn-primary slow-ripple" x-text="actionLabel"></button>
			<button @click="reset() "type="button" class="button btn-cancel border slow-ripple">重置</button>
			<!--label class="checkbox check-red">
				<input type="checkbox" name="passwordOnly" value="1">
				<span class="red-text">僅變更密碼</span>
			</label-->
		</nav>
	</section>
</form>
</div>

@endsection