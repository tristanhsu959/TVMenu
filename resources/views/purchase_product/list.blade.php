@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/purchase_product/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/purchase_product/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
	<header class="page-nav">
		<nav>
			<a href="{{ route('purchase_product.update') }}" class="btn-create button circle"><i>settings</i></a>
		</nav>
	</header>
	
@if($viewModel->status() === TRUE)
	@if($viewModel->isDataEmpty())
	<article class="error-container border list-msg">
		<div class="row">
			<i>info</i><div class="max">尚無產品設定</div>
		</div>
	</article>
	@else
	<section x-data='purchaseSettingList(@json($viewModel->list), @json($viewModel->options) )' class="setting-list container">
		<div>
			<div class="tabs">
				<template x-for="(brand, key) in options.brands" :key="key">
					<a :data-ui="'#page-' + key" :class="activeTab == key ? 'active':''" @click="activeTab = key">
						<span x-text="brand"></span>
						<span x-text="settings[key].length" class="chip round fill"></span>
					</a>
				</template>
			</div>
			
			<template x-for="(brand, key) in options.brands" :key="'list_' + key">
			<div class="page padding" :id="'page-' + key" :class="activeTab == key ? 'active':''">
				<ul class="list border">
					<template x-for="item in settings[key]">
					<li>
						<i class="fill extra green-text">check_circle</i>
						<div class="max">
							<h6 x-text="item.productName"></h6>
							<div class="small grey-text" x-text="item.productCode"></div>
						</div>
					</li>
					</template>
				</ul>
			</div>
			</template>
		</div>
	</section>
	@endif
@endif
<!-- Content -->
@endsection