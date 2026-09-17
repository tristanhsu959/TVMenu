@extends('layouts.app')
@use('App\Enums\Brand')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/new_release_setting/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/new_release_setting/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
	<header class="page-nav">
		<nav>
			<a href="{{ route('new_release_setting.create') }}" class="btn-create button circle"><i>add</i></a>
		</nav>
	</header>
	
@if($viewModel->status() === TRUE)
	@if($viewModel->isDataEmpty())
	<article class="error-container border list-msg">
		<div class="row">
			<i>info</i><div class="max">尚無新品設定</div>
		</div>
	</article>
	@else
	<form x-data='releaseSettingList(@json($viewModel->list),@json($viewModel->options["brands"]) )' action="" method="post" x-ref="releaseSettingListForm">
		@csrf
		<section class="setting-list container">
			<div>
				<div class="tabs">
					<template x-for="(brand, key) in brands" :key="key">
						<a :data-ui="'#page-' + key" :class="activeTab == key ? 'active':''" @click="$store.releaseSetting.tabIndex = key">
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
							<i class="fill extra" x-text="item.releaseStatus ? 'check_circle':'cancel'" :class="item.releaseStatus ? 'green-text':'red-text'"></i>
							<div class="max">
								<h6 x-text="item.releaseName"></h6>
								<div class="small sale-date" x-text="'發售日 ' + item.releaseSaleDate"></div>
							</div>
							<div>
								<a :href="'{{ route('new_release_setting.update', ['_ID_']) }}'.replace('_ID_', item.releaseId)" class="btn-edit button circle small">
									<i class="small">edit</i>
								</a>
								<a :href="'{{ route('new_release_setting.delete', ['_ID_']) }}'.replace('_ID_', item.releaseId)" @click.prevent="confirmDelete($el.href)" class="btn-delete button circle small">
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
@endif
<!-- Content -->
@endsection