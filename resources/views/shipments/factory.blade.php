 
	<section x-data="statisticsFactory(@js($viewModel->statisticsData()))" class="factory-list container">
		<article x-show="!response.hasResult" class="secondary-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		
		<div x-show="response.hasResult" class="factory-content">
			<div class="tabs cyan-text">
				<template x-for="(item, erpNo) in statistics.productList" :key="erpNo">
					<a @click="activeProduct = erpNo" :class="{ 'active': activeProduct === erpNo }">
						<span x-text="item.productName"></span>
						<div class="tooltip bottom" x-text="item.memo" x-show="item.memo.trim() != ''"></div>
					</a>
				</template>
			</div>
			
			<!-- 工廠 -->
			<template x-for="(name, erpNo) in statistics.productList" :key="erpNo">
			<div class="page padding" :class="{ 'active': activeProduct === erpNo }">
				<section class="statistics-factory scrollbar" :class="statistics.brandCode">
					<table>
						<thead>
							<tr>
								<th>出貨工廠</th>
								<template x-for="(name, id) in statistics.dateList" :key="id">
									<th x-text="name"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(factoryName, factoryId) in statistics.factoryList" :key="factoryId">
							<tr>
								<th x-text="factoryName"></th>
								<template x-for="(date, idx) in statistics.dateList" :key="idx">
									<td x-text="statistics.data[erpNo]?.[factoryId]?.[date]?.['qty'] ?? 0"></td>
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
