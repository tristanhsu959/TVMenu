@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ asset('styles/purchase_sales/detail.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/purchase_sales/detail.js') }}" defer></script>
@endpush

@section('content')
<div x-data="{response:@js($viewModel->responseData())}" class="content-wrapper">
	<header class="page-nav">
		<nav>
			<template x-if="response.exportAction">
			<a :href="`javascript:window.location.href='${response.exportAction}'`" class="button circle extend red" type="button">
				<i>download_2</i>
				<span>下載</span>
			</a>
			</template>
		</nav>
	</header>
	
	<template x-if="!response.status">
		<section class="container">
			<article class="error-container border">
				<div class="row">
					<i>error</i><div class="max">查詢時發生錯誤，請重新查詢</div>
				</div>
			</article>
		</section>
	</template>
	
	<template x-if="response.status && !response.isInit">
		<section x-data="statistics(@js($viewModel->statisticsData()))" class="purchase-sale-list container">
			<article class="store-info border">
				<div class="search-date">
					<div x-text="`${statistics.searchYear}.${statistics.searchMonth}`"></div>
					<div class="day" x-text="statistics.searchDay"></div>
				</div>
				<div class="info">
					<div x-text="statistics.storeInfo['storeName']" class="header"></div>
					<div x-text="statistics.storeInfo['storeKey']"></div>
					<div x-text="statistics.storeInfo['posId']"></div>
				</div>
			</article>
			
			<article x-show="!response.hasResult" class="secondary-container border">
				<div class="row">
					<i>info</i><div class="max">查無符合資料</div>
				</div>
			</article>
			
			<div x-show="response.hasResult" class="statistics">
				<div class="tabs cyan-text">
					<a class="active" data-ui="#tab-purchase">訂貨</a>
					<a data-ui="#tab-sale">銷售</a>
				</div>
				
				<!-- 訂貨資訊 -->
				<div class="page padding active" id="tab-purchase">
					<section class="statistics-list purchase" :class="statistics.brandCode">
						<table class="stripes">
							<thead>
								<tr>
									<template x-for="col in statistics.purchaseData.header" :key="col">
										<th class="s2" x-text="col"></th>
									</template>
								</tr>
							</thead>
							<tbody>
								<template x-for="(data, idx) in statistics.purchaseData.data" :key="idx">
								<tr>
									<td x-text="data['shortCode']"></td>
									<td x-text="data['productName']"></td>
									<td x-text="data['qty']"></td>
									<td x-text="Helper.formatDollar(data['amount'])"></td>
									<td x-text="data['memo']"></td>
								</tr>
								</template>
							</tbody>
						</table>
					</section>
				</div>
				
				<!-- 銷售資訊 -->
				<div class="page padding active" id="tab-sale">
					<section class="statistics-list sale" :class="statistics.brandCode">
						<table class="stripes">
							<thead>
								<tr>
									<template x-for="col in statistics.saleData.header" :key="col">
										<th class="s2" x-text="col"></th>
									</template>
								</tr>
							</thead>
							<tbody>
								<template x-for="(data, idx) in statistics.saleData.data" :key="idx">
								<tr>
									<td x-text="data['erpNo']"></td>
									<td x-text="data['productName']"></td>
									<td x-text="data['qty']"></td>
									<td x-text="Helper.formatDollar(data['amount'])"></td>
								</tr>
								</template>
							</tbody>
						</table>
					</section>
				</div>
			</div>
		</section>
	</template>
@endsection