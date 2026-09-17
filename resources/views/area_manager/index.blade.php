@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/area_manager/list.css') }}" rel="stylesheet"/>
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/area_manager/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Search panel -->
<dialog x-data="search(@js($viewModel->searchFormData()))" id="searchPanel" class="right">
	<form :action="searchData.formAction" method="post" id="searchForm" novalidate @submit.prevent="search()">
	@csrf
		<h5>查詢</h5>
		<div class="field middle-align">
			<nav class="wrap">
				<template x-for="(name, id) in options.brandList" :key="id">
					<label class="radio field-red">
						<input type="radio" name="searchBrandId" x-model="searchData.brandId" :value="id">
						<span x-text="name"></span>
					</label>
				</template>
			</nav>
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
			<output class="red-text small">選擇欲更新督的區域</output>
		</fieldset>
		
		<div class="space"></div>
		<nav class="right-align group split">
			<button type="submit" class="btn-search left-round large"><i>search</i>查詢</button>
			<button @click="resetSearch()" type="button" class="btn-search-reset right-round square large"><i>backspace</i></button>
		</nav>
	</form>
</dialog>

<!-- Search panel end -->
<div x-data="list(@js($viewModel->responseData()))" class="content-wrapper">
	<header x-show="!showUpdateForm" class="page-nav">
		<nav>
			<button type="button" class="btn-show-search button circle extend" data-ui="#searchPanel">
				<i>search</i>
				<span>查詢範本</span>
			</button>
			
			<template x-if="response.exportAction">
				<a :href="`javascript:window.location.href='${response.exportAction}'`" class="button circle extend red" type="button">
					<i>download</i>
					<span>下載範本</span>
				</a>
			</template>
			
			<button type="button" class="button circle extend green" @click="isUpdate = true">
				<i>upload</i>
				<span>更新門店督導</span>
			</button>
		</nav>
	</header>
	
	<template x-if="response.status && response.isInit">
		<section x-show="!showUpdateForm" class="container">
			<pre><i>arrow_warm_up</i>點擊查詢取得督導更新範本</pre>
		</section>
	</template>
	
	<template x-if="response.msg">
		<section class="container">
			<article class="error-container border">
				<div class="row">
					<i>error</i><div class="max" x-text="response.msg"></div>
				</div>
			</article>
		</section>
	</template>
	
	<template x-if="response.status && !response.isInit">
		@include($viewModel->getPartialView())
	</template>
	
	<section x-show="showUpdateForm" class="update-form container">
		<form method="post" :action="response.updateAction" id="updateForm" enctype="multipart/form-data" novalidate @submit.prevent="validateUpdate()">
		@csrf
			<p class="pink-text"><i class="fill">brightness_alert</i> Excel上傳檔案須為系統提供的範本格式</p>
			<p class="pink-text"><i class="fill">brightness_alert</i> 督導欄位若空白則將被清空</p>
			<p class="pink-text"><i class="fill">brightness_alert</i> 若不變更某門店督導，可將該筆門店自範本中刪除或將門店代碼清空</p>
			
			<div class="field label prefix border" :class="Helper.hasError(errors, 'uploadFile')">
				<i>file_open</i>
				<input type="file" name="uploadFile" accept=".xlsx" @change="validateFile($event);" x-ref="fileInput">
				<input type="text" :value="uploadFileName">
				<label>上傳Excel</label>
			</div>
			
			<nav class="group split">
				<button type="submit" class="left-round light-green large">
					<i>upload_file</i>
					<span>更新</span>
				</button>
				
				<button type="button" class="right-round black large btn-clear" @click="clearFileInput()">
					<i>backspace</i>
				</button>
				
				<button type="button" class="circle secondary large" @click="isUpdate = false" >
					<i>arrow_forward</i>
				</button>
			</nav>
		</form>
	</section>
	
</div>
@endsection