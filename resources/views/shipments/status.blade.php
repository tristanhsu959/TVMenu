 
<section x-data="StatisticsStatus(@js($viewModel->statisticsData()))" class="store-list container">
	<article x-show="!response.hasResult" class="secondary-container border">
		<div class="row">
			<i>info</i><div class="max">查無符合資料</div>
		</div>
	</article>
	
	<div x-show="response.hasResult" class="store-content">
		<!-- 門店 -->
		<div class="statistics-list status-list">
			<section class="statistics-store scrollbar no-tab" :class="response.brandCode">
				<table class="stripes">
					<thead>
						<tr>
							<th>區域</th>
							<th>門店代號</th>
							<th>門店名稱</th>
							<template x-for="(name, id) in statistics.dateList" :key="id">
								<th x-text="name"></th>
							</template>
						</tr>
					</thead>
					<tbody>
						<template x-for="(store, idx) in statistics.data" :key="idx">
						<tr>
							<td x-text="store.areaName"></td>
							<td x-text="store.storeKey"></td>
							<td>
								<button class="transparent circle small purple-text" @click="$dispatch('active-store', { id: store.storeKey, name: store.storeName, details: store.detail, dateList: statistics.dateList })"><i>more_vert</i></button>
								<span x-text="store.storeName"></span>
							</td>
							<template x-for="(date, idx) in statistics.dateList" :key="idx">
								<td>
									<i x-show="(store.total[date]) > 0" class="green-text">check_circle</i>
									<i x-show="(store.total[date]) <= 0" class="red-text">cancel</i>
								</td>
							</template>
						</tr>
						</template>
							
					</tbody>
				</table>
			</section>
		</div>
	</div>
	
	<dialog x-data="storeDetail()" @active-store.window="openDetail($event.detail)" id="detailData" class="store-detail right scroll">
		<div class="row">
			<div class="left-align max">
				<h6 x-text="detail?.storeName || ''" class="purple-text"></h6>
				<div x-text="detail?.storeKey || ''"></div>
			</div>
			<nav class="right-align">
				<button class="transparent circle" @click="ui('#detailData');"><i>close</i></button >
			</nav>
		</div>
		
		<table class="stripes responsive">
			<thead>
				<tr>
					<th>產品</th>
					<template x-for="(date, idx) in detail.dateList" :key="idx">
						<th x-text="date"></th>
					</template>
				</tr>
			</thead>
			<tbody>
				<template x-for="(orderData, productName) in detail.data" :key="productName">
					<tr>
						<td x-text="productName"></td>
						<template x-for="(date, idx) in detail.dateList" :key="idx">
							<td x-text="orderData[date]?? 0"></td>
						</template>
					</tr>
				</template>
			</tbody>
		</table>
		
	</dialog>
</section>
