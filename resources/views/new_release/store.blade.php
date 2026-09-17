	<!-- 店別明細 -->
	<div x-data="{shop:@js($viewModel->statisticsData('shop'))}" class="page padding" id="tab-shop">
		<section class="statistics-store scrollbar" :class="response.brandCode">
			<table class="stripes">
				<thead>
					<tr>
						<th x-text="shop.header.areaName"></th>
						<th x-text="shop.header.shopId"></th>
						<th x-text="shop.header.storeKey"></th>
						<th x-text="shop.header.storeName"></th>
						<template x-for="date in shop.header.dayQty" :key="date">
							<th x-text="date"></th>
						</template>
						<th x-text="shop.header.totalQty"></th>
						<th x-text="shop.header.totalAvg"></th>
					</tr>
				</thead>
				<tbody>
					<template x-for="(shopData, idx) in shop.data" :key="idx">
					<tr>
						<td x-text="shopData.areaName"></td>
						<td x-text="shopData.shopId"></td>
						<td x-text="shopData.storeKey"></td>
						<td x-text="shopData.storeName"></td>
						<template x-for="date in shop.header.dayQty" :key="date">
							<td x-text="shopData.dayQty[date] || 0"></td>
						</template>
						<td x-text="shopData.totalQty"></td>
						<td x-text="shopData.totalAvg"></td>
					</tr>
					</template>
				</tbody>
			</table>
		</section>
	</div>