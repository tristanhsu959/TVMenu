 
	<section x-data="statisticsStore(@js($viewModel->statisticsData()))" class="store-list container">
		<article x-show="!response.hasResult" class="secondary-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		
		<div x-show="response.hasResult" class="store-content">
			<div class="tabs cyan-text">
				<template x-for="(item, shortCode) in statistics.productList" :key="shortCode">
					<a @click="activeProduct = shortCode" :class="{ 'active': activeProduct === shortCode }">
						<span x-text="item.productName"></span>
						<div class="tooltip bottom" x-text="item.memo" x-show="item.memo.trim() != ''"></div>
					</a>
				</template>
			</div>
			
			<!-- 門店 -->
			<template x-for="(name, shortCode) in statistics.productList" :key="shortCode">
			<div class="page paddin" :class="{ 'active': activeProduct === shortCode }">
				<section class="statistics-store scrollbar" :class="response.brandCode">
					<table class="stripes">
						<thead>
							<tr>
								<th>區域</th>
								<th>POS店號</th>
								<th>門店代號</th>
								<th>門店名稱</th>
								<template x-for="(name, id) in statistics.dateList" :key="id">
									<th x-text="name"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<!--template x-for="(store, storeId) in statistics.header['storeList']" :key="storeId"-->
							<template x-for="(store, idx) in filterStore" :key="idx">
							<tr>
								<td x-text="store.areaName"></td>
								<td x-text="store.posId"></td>
								<td x-text="store.storeKey"></td>
								<td x-text="store.storeName"></td>
								<template x-for="(date, idx) in statistics.dateList" :key="idx">
									<td x-text="statistics.data[shortCode]?.[store['storeKey']]?.[date]?.['qty'] ?? 0"></td>
								</template>
							</tr>
							</template>
								
						</tbody>
					</table>
				</section>
			</div>
			</template>
		</div>
	</section>
