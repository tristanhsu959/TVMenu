	<!-- 門店 -->
	<div x-data="statisticsMoonFestival(@js($viewModel->moonFestivalData()))" class="store-content">
		<section class="statistics-store scrollbar" :class="response.brandCode">
			<table class="stripes">
				<thead>
					<tr>
						<th rowspan="2">區域</th>
						<th rowspan="2">POS店號</th>
						<th rowspan="2">門店代號</th>
						<th rowspan="2">門店名稱</th>
						<template x-for="dateHeader in dayRange" :key="dateHeader">
							<th x-text="dateHeader" colspan="3"></th>
						</template>
						<th rowspan="2">總計</th>
						<th rowspan="2">兌換數</th>
					</tr>
					<tr>
						<template x-for="rangeHeader in dayRange" :key="rangeHeader">
							<template x-for="productName in productList" :key="productName">
								<th x-text="productName" class="purple-text"></th>
							</template>
						</template>
					</tr>
				</thead>
				<tbody>
					<template x-for="(row, idx) in filterStore" :key="idx">
					<tr>
						<td x-text="row.areaName"></td>
						<td x-text="row.storeId"></td>
						<td x-text="row.storeKey"></td>
						<td x-text="row.storeName"></td>
						
						<template x-for="(saleDate, dateKey) in sortedDates" :key="dateKey">
							<template x-for="(name, pId) in productList" :key="pId">
								<td x-text="row.products[saleDate]?.[pId] || 0"></td>
							</template>
						</template>
						
						<td x-text="row.totalQty" class="red-text"></td>
						<td x-text="row.totalUsed" class="red-text"></td>
					</tr>
					</template>
				</tbody>
			</table>
		</section>
	</div>
