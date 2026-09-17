	<!-- 門店 -->
	<div x-data="statisticsStore(@js($viewModel->statisticsData('store')))" class="page padding" id="tab-shop">
		<section class="statistics-store scrollbar" :class="response.brandCode">
			<table class="stripes">
				<thead>
					<tr>
						<th x-text="store.header.areaName"></th>
						<th x-text="store.header.shopId"></th>
						<th x-text="store.header.storeKey"></th>
						<th x-text="store.header.storeName"></th>
						<template x-for="pName in store.header.products" :key="pName">
							<th x-text="pName"></th>
						</template>
					</tr>
				</thead>
				<tbody>
					<template x-for="(storeData, idx) in filterStore" :key="idx">
					<tr>
						<td x-text="storeData.areaName"></td>
						<td x-text="storeData.shopId"></td>
						<td x-text="storeData.storeKey"></td>
						<td><button class="transparent circle small purple-text" @click="$dispatch('active-store', { id: storeData.storeKey, name: storeData.storeName, details: storeData.details })"><i>more_vert</i></button><span x-text="storeData.storeName"></span></td>
						
						<template x-for="(pName, pId) in store.header.products" :key="pId">
							<td>
								<span x-show="!$store.sales.showAmount" x-text="storeData.products[pId]?.totalQty || 0"></span>
								<span x-show="$store.sales.showAmount" x-text="Helper.formatDollar(Math.round(storeData.products[pId]?.totalAmount || 0))"></span>
							</td>
						</template>
					</tr>
					</template>
				</tbody>
			</table>
		</section>
	</div>
	
	<dialog x-data="storeDetail()" @active-store.window="openDetail($event.detail)" id="salesDetail" class="store-detail bottom scroll">
		<div class="row">
			<div class="left-align max">
				<h6 x-text="detail?.storeName || ''" class="purple-text"></h6>
				<div x-text="detail?.storeKey || ''"></div>
			</div>
			<nav class="right-align">
				<button class="transparent circle" @click="ui('#salesDetail');"><i>close</i></button >
			</nav>
		</div>
		
		<table class="stripes responsive">
			<thead>
				<tr>
					<template x-for="(headName, hidx) in detail.header" :key="hidx">
						<th x-text="headName"></th>
					</template>
				</tr>
			</thead>
			<tbody>
				<template x-for="(rowData, rowIdx) in detail.products" :key="rowIdx">
				<tr>
					<template x-for="(colData, colIdx) in ($store.sales.showAmount ? rowData['amount'] : rowData['qty'])" :key="colIdx">
					<td>
						<span x-show="!$store.sales.showAmount || colIdx == 0" x-text="colData"></span>
						<span x-show="$store.sales.showAmount && colIdx > 0" x-text="Helper.formatDollar(Math.round(colData))"></span>
					</td>
					</template>
				</tr>
				</template>
			</tbody>
		</table>
		
	</dialog>

