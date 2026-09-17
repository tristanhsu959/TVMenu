 
	<section x-data="statisticsNotOrder(@js($viewModel->statisticsData()))" class="store-list container">
		<article x-show="!response.hasResult" class="secondary-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		
		<div x-show="response.hasResult" class="store-content">
			<!-- 門店 -->
			<div class="statistics-list">
				<h5 x-text="`共 ${Object.keys(statistics.data.store).length} 家門店`" class="small red-text"></h5>
				<section x-show="Object.keys(statistics.data.store).length > 0" class="statistics-store scrollbar" :class="response.brandCode">
					<table class="stripes">
						<thead>
							<tr>
								<template x-for="(headName, headKey) in statistics.data.header" :key="headKey">
									<th x-text="headName"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(store, idx) in filterStore" :key="idx">
							<tr>
								<td x-text="store.areaName"></td>
								<td x-text="store.posId"></td>
								<td x-text="store.storeKey"></td>
								<td x-text="store.storeName"></td>
								<template x-for="(name, shortCode) in statistics.productList" :key="shortCode">
									<td x-text="store.productQty?.[shortCode] ?? 0"></td>
								</template>
							</tr>
							</template>
								
						</tbody>
					</table>
				</section>
			</div>
		</div>
	</section>
