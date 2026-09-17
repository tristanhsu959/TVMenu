 
	<section x-data="storeDayoff(@js($viewModel->statisticsData()))" class="store-list container">
		<article x-show="!response.hasResult" class="secondary-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		
		<div x-show="response.hasResult" class="area-content scrollbar">
			<h6 x-text="statistics.startDate" class="small"></h6>
			<!-- 區域 -->
			<section class="statistics-area" :class="response.brandCode">
				<table>
					<thead>
						<tr>
							<template x-for="area in statistics.areaDayoff.header" :key="area">
								<th x-text="area"></th>
							</template>
						</tr>
					</thead>
					<tbody>
						<template x-for="(store, idx) in statistics.areaDayoff.store" :key="idx">
						<tr :class="{ 'red-text': store.areaId == 0 }">
							<td x-text="store.areaName"></td>
							<td x-text="store.total"></td>
							<td>
								<span x-text="store.dayoffCount"></span>
								<button class="transparent square small" @click="activeAreaId = store.areaId;">
									<i>visibility</i>
								</button>
							</td>
							<!--td x-text="store.percent + '%'"></td-->
						</tr>
						</template>
					</tbody>
				</table>
			</section>
			
			<!-- 門店 -->
			<section x-show="response.dayoffCount > 0" class="statistics-store" :class="response.brandCode">
				<table class="stripes">
					<thead>
						<tr>
							<template x-for="header in statistics.dayoff.header" :key="header">
								<th x-text="header"></th>
							</template>
						</tr>
					</thead>
					<tbody>
						<template x-for="(store, idx) in statistics.dayoff.store" :key="idx">
						<tr x-show="store.areaId == activeAreaId || activeAreaId == 0">
							<td x-text="store.areaName"></td>
							<td x-text="store.posId"></td>
							<td x-text="store.storeKey"></td>
							<td x-text="store.storeName"></td>
						</tr>
						</template>
					</tbody>
				</table>
			</section>
			
		</div>
	</section>
