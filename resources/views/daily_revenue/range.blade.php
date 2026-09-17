<section class="daily-revenue-list container">
	<article x-show="!response.hasResult" class="secondary-container border">
		<div class="row">
			<i>info</i><div class="max">查無符合資料</div>
		</div>
	</article>
	
	<div x-show="response.hasResult" class="statistics">
		<div class="tabs cyan-text">
			<a class="active" data-ui="#tab-area">區域彙總</a>
			<a data-ui="#tab-shop">店別明細</a>
		</div>
		
		<!-- 區域彙總 -->
		<div x-data="{area:@js($viewModel->statisticsData('area'))}" class="page padding active" id="tab-area">
			<section class="statistics-area">
				<table>
					<thead>
						<tr>
							<th x-text="area.header.areaName"></th>
							<th x-text="area.header.storeCount"></th>
							<template x-for="(date, dateKey) in area.header.dayAmount" :key="dateKey">
								<th x-text="date"></th>
							</template>
						</tr>
					</thead>
					<tbody>
						<template x-for="(areaData, areaId) in area.data" :key="areaId">
						<tr>
							<td x-text="areaData.areaName"></td>
							<td x-text="areaData.storeCount"></td>
							<template x-for="(date, dateKey) in area.header.dayAmount" :key="dateKey">
								<td x-text="Helper.formatDollar(areaData.dayAmount[date] || 0)"></td>
							</template>
						</tr>
						</template>
					</tbody>
				</table>
			</section>
		</div>
	
		<!-- 門店 -->
		<div x-data="{store:@js($viewModel->statisticsData('store'))}" class="page padding" id="tab-shop">
			<section class="statistics-store scrollbar" :class="response.brandCode">
				<table class="stripes">
					<thead>
						<tr>
							<th x-text="store.header.areaName"></th>
							<th x-text="store.header.shopId"></th>
							<th x-text="store.header.storeKey"></th>
							<th x-text="store.header.storeName"></th>
							<th x-text="store.header.storeTypeName"></th>
							<template x-for="(date, dateKey) in store.header.dayAmount" :key="dateKey">
								<th x-text="date"></th>
							</template>
						</tr>
					</thead>
					<tbody>
						<template x-for="(storeData, storeId) in store.data" :key="storeId">
						<tr>
							<td x-text="storeData.areaName"></td>
							<td x-text="storeData.shopId"></td>
							<td x-text="storeData.storeKey"></td>
							<td x-text="storeData.storeName"></td>
							<td x-text="storeData.storeTypeName"></td>
							<template x-for="(date, dateKey) in store.header.dayAmount" :key="dateKey">
								<td x-text="Helper.formatDollar(storeData.dayAmount[date] || 0)"></td>
							</template>
						</tr>
						</template>
					</tbody>
				</table>
			</section>
		</div>
	</div>
		
</section>
	