 
	<section x-data="statisticsStore(@js($viewModel->statisticsData()))" class="store-list container">
		<article x-show="!response.hasResult" class="secondary-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		
		<div x-show="response.hasResult" class="store-content">
			<div class="tabs cyan-text">
				<template x-for="(productName, productId) in fixedTabs" :key="productId">
					<a x-text="productName" @click="setActiveTab(productId, productName)" :class="activeTab(productId)"></a>
				</template>
				
				<!-- 更多 -->
				<template x-if="showMoreTabs">
					<a @click.away="openTabMenu = false" @click.stop="openTabMenu = !openTabMenu" class="moreTab" :class="activeMoreTab()">
						<span x-text="moreTabName">更多</span>
						<i>arrow_drop_down</i>
						<!-- 下拉選單容器 -->
						<menu class="no-wrap" :class="{ 'active': openTabMenu }">
							<template x-for="(productName, productId) in moreTabs" :key="productId">
							<li class="align-left full-width">
								<a x-text="productName" @click="setActiveTab(productId, productName)"></a>
							</li>
							</template>
						</menu>
					</a>
				</template>
			</div>

			<!-- 門店 -->
			<template x-for="(name, productId) in statistics.productList" :key="productId">
			<div class="page paddin" :class="{ 'active': activeProduct === productId }">
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
							<template x-for="(store, idx) in statistics.storeList" :key="idx">
							<tr>
								<td x-text="store.areaName"></td>
								<td x-text="store.posId"></td>
								<td x-text="store.storeKey"></td>
								<td x-text="store.storeName"></td>
								<template x-for="date in statistics.dateList">
									<td x-text="statistics.data[productId]?.[store['storeId']]?.[date]?.['qty'] ?? 0"></td>
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
