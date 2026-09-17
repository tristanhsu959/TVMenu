@extends('layouts.app')
@use('App\Enums\Brand')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/product/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/product/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
<div x-data="{response:@js($viewModel->responseData())}" class="content-wrapper">
	<header class="page-nav">
		<nav>
			<a :href="response.createFormAction" class="btn-create button circle"><i>add</i></a>
			
			<template x-for="(brand, brandId) in response.options.brands">
				<nav x-show="brandId == $store.productSetting.tabIndex" class="no-space filter">
					<div class="field label suffix border field-filter-dark small">
						<select x-model="$store.productSetting.filterCat[brandId]" id="filterCat">
							<option value="">全部</option>
							<template x-for="(catName, catId) in response.options.categories[brandId]" :key="catId">
								<option x-text="catName" :value="catName"></option>
							</template>
						</select>
						<label>篩選分類</label>
						<i>arrow_drop_down</i>
					</div>
				</nav>
			</template>
		</nav>
	</header>
	
	<template x-if="!response.status">
		<section class="container">
			<article class="error-container border">
				<div class="row">
					<i>error</i><div class="max">讀取產品料號設定時發生錯誤，請重新查詢</div>
				</div>
			</article>
		</section>
	</template>
	
	<template x-if="response.status">
		<section class="product-list container">
			<article x-show="!response.hasResult" class="secondary-container border">
				<div class="row">
					<i>info</i><div class="max">查無產品設定</div>
				</div>
			</article>
			
			<form x-data="productList(@js($viewModel->responseList()))" action="" method="post" x-ref="productListForm">
			@csrf
			<div x-show="response.hasResult" class="list-wrapper">
				<div class="tabs">
					<template x-for="(brand, brandId) in brands" :key="brandId">
						<a :data-ui="`#page-${brandId}`" :class="activeTab == brandId ? 'active':''" @click="$store.productSetting.tabIndex = brandId" class="tab-pink">
							<span x-text="brand"></span>
							<span x-text="products[brandId].length" class="chip round fill"></span>
						</a>
					</template>
				</div>
					
				<template x-for="(brand, brandId) in brands">
					<div class="page padding" :id="`page-${brandId}`" :class="activeTab == brandId ? 'active':''">
						<ul class="list border">
							<template x-for="item in filterProducts(brandId)">
							<li>
								<span class="brand-label" x-text="brand.substring(0, 1)" :class="item.productBrandId == 1 ? 'bf':'bg'"></span>
								<div class="max">
									<h6 x-text="item.productName"></h6>
									<div class="small" x-text="item.categoryName"></div>
								</div>
								<div>
									<a :href="'{{ route('product.update', ['_ID_']) }}'.replace('_ID_', item.productId)" class="btn-edit button circle small">
										<i class="small">edit</i>
									</a>
									<a :href="'{{ route('product.delete', ['_ID_']) }}'.replace('_ID_', item.productId)" @click.prevent="confirmDelete($el.href)" class="btn-delete button circle small">
										<i class="small">delete</i>
									</a>
								</div>
							</li>
							</template>
						</ul>
					</div>
					</template>
				</div>
			</form>
		</section>
	</template>
</div>
<!-- Content -->
@endsection