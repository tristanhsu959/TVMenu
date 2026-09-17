<section class="day-revenue-list container">
	<article x-show="!response.hasResult" class="secondary-container border">
		<div class="row">
			<i>info</i><div class="max">查無符合資料</div>
		</div>
	</article>
	
	<div x-show="response.hasResult" class="statistics-list">
		
		<!-- 門店 -->
		<div x-data="{store:@js($viewModel->statisticsData('store')), hasClosingData: @js($viewModel->statisticsData('hasClosingData')), hasHourlyData: @js($viewModel->statisticsData('hasHourlyData'))}" class="padding">
			<section class="statistics-store scrollbar no-tab" :class="response.brandCode">
				<table class="stripes">
					<thead>
						<tr>
							<template x-for="(name, idx) in store.header" :key="idx">
								<th x-text="name"></th>
							</template>
						</tr>
					</thead>
					<tbody>
						<template x-for="(storeData, storeId) in store.data" :key="storeId">
						<tr>
							<td x-text="storeData.areaName"></td>
							<td x-text="storeData.posId"></td>
							<td x-text="storeData.storeKey"></td>
							<td>
								<button x-show="hasHourlyData" class="transparent circle small purple-text" @click="$dispatch('active-store', { id: storeData.storeKey, name: storeData.storeName, details: storeData.hourly })"><i>more_vert</i></button>
								<span x-text="storeData.storeName"></span>
							</td>
							<td x-text="Helper.formatDollar(storeData.totalSales || 0)"></td>
							<td x-text="Helper.formatDollar(storeData.totalExtra || 0)"></td>
							<td x-text="Helper.formatDollar(storeData.totalDischarge || 0)"></td>
							<td x-text="Helper.formatDollar(storeData.totalAmount || 0)"></td>
							<td x-text="Helper.formatDollar(storeData.invoiceAmount || 0)" class="red-text"></td>
							<td x-show="hasClosingData" x-text="Helper.formatDollar(storeData.closingAmount || 0)"></td>
						</tr>
						</template>
					</tbody>
				</table>
			</section>
		</div>
	</div>
	
	<dialog x-data="storeDetail()" @active-store.window="openDetail($event.detail)" id="hourlyData" class="store-detail right scroll">
		<div class="row">
			<div class="left-align max">
				<h6 x-text="detail?.storeName || ''" class="purple-text"></h6>
				<div x-text="detail?.storeKey || ''"></div>
			</div>
			<nav class="right-align">
				<button class="transparent circle" @click="ui('#hourlyData');"><i>close</i></button >
			</nav>
		</div>
		
		<table class="stripes responsive">
			<thead>
				<tr>
					<th>時段</th>
					<th>金額</th>
				</tr>
			</thead>
			<tbody>
				<template x-for="(amount, hour) in detail.hourlyData" :key="hour">
					<tr>
						<td x-text="hour"></td>
						<td x-text="Helper.formatDollar(amount || 0)"></td>
					</tr>
				</template>
			</tbody>
		</table>
		
	</dialog>
</section>
	