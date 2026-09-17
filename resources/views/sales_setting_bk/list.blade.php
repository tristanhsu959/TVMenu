@extends('layouts.app')
@use('App\Enums\Brand')

@push('styles')
    <link href="{{ asset('styles/sales_setting/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('scripts/sales_setting/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
	<header class="page-nav">
		<nav>
			<a href="{{ route('sales_setting.create') }}" class="btn-create button circle"><i>add</i></a>
		</nav>
	</header>
	
@if($viewModel->status() === TRUE)
	<form x-data='salesSettingList(@json($viewModel->list), @json($viewModel->options) )' action="" method="post" x-ref="salesSettingListForm">
		@csrf
		<section class="setting-list container">
			<div>
				<div class="tabs">
					<template x-for="(brand, key) in brands" :key="key">
						<a :data-ui="'#page-' + key" :class="activeTab == key ? 'active':''" @click="$store.salesSetting.tabIndex = key">
							<span x-text="brand"></span>
							<span x-text="settings[key].length" class="chip round fill"></span>
						</a>
					</template>
				</div>
				
				<template x-for="(brand, key) in brands">
				<div class="page padding" :id="'page-' + key" :class="activeTab == key ? 'active':''">
					<ul class="list border">
						<template x-for="item in settings[key]">
						<li>
							<i class="fill extra" x-text="item.salesStatus ? 'check_circle':'cancel'" :class="item.salesStatus ? 'green-text':'red-text'"></i>
							<div class="max">
								<h6 x-text="item.salesName"></h6>
								<div class="small sale-date"></div>
							</div>
							<div>
								<a :href="'{{ route('sales_setting.update', ['_ID_']) }}'.replace('_ID_', item.salesId)" class="btn-edit button circle small">
									<i class="small">edit</i>
								</a>
								<a :href="'{{ route('sales_setting.delete', ['_ID_']) }}'.replace('_ID_', item.salesId)" @click.prevent="confirmDelete($el.href)" class="btn-delete button circle small">
									<i class="small">delete</i>
								</a>
							</div>
						</li>
						</template>
					</ul>
				</div>
				</template>
			</div>
		</section>
	</form>

@endif
<!-- Content -->
@endsection