<section class="aov-list container">
	<article x-show="!response.hasResult" class="secondary-container border">
		<div class="row">
			<i>info</i><div class="max">查無符合資料</div>
		</div>
	</article>
	
	<div x-data="aovStatistics(@js($viewModel->statisticsData()))" x-show="response.hasResult" class="statistics">
		<template x-for="(typeName, typeKey) in statisticsData.storeType" :key="typeKey">
			<section x-data="{mainData:statisticsData.total[typeKey], detailData:statisticsData.subTotal[typeKey]}" x-show="mainData" class="statistics-aov">
				<h6 x-text="typeName"></h6>
				<article class="primary-container fill">
					<table>
						<thead>
							<tr>
								<template x-for="(header, headerIdx) in statisticsData.header" :key="headerIdx">
									<th x-text="header"></th>
								</template>
							</tr>
						</thead>
						
						<template x-for="(data, month) in mainData" :key="month">
						<tbody>
							<tr>
								<td x-text="data['saleMonth']"></td>
								<td>
									<button class="small blue" @click="addExpansion(typeKey, month)">
										<span x-text="data['areaName']"></span>
										<i>list_arrow</i>
									</button>
								</td>
								<td x-text="data['storeCount']"></td>
								<td x-text="Helper.formatDollar(data['amount'])"></td>
								<td x-text="Helper.formatDollar(data['avgStoreAmount'])"></td>
								<td x-text="data['visitors']"></td>
								<td x-text="data['avgVisitors']"></td>
								<td x-text="Helper.formatDollar(data['avgOrderValue'])"></td>
							</tr>
							
							<template x-for="(areaData, areaId) in detailData[month]" :key="areaId">
								<tr x-show="showDetail(typeKey, month)" class="area-detail">
									<td x-text="areaData['saleMonth']"></td>
									<td x-text="areaData['areaName']"></td>
									<td x-text="areaData['storeCount']"></td>
									<td x-text="Helper.formatDollar(areaData['amount'])"></td>
									<td x-text="Helper.formatDollar(areaData['avgStoreAmount'])"></td>
									<td x-text="areaData['visitors']"></td>
									<td x-text="areaData['avgVisitors']"></td>
									<td x-text="Helper.formatDollar(areaData['avgOrderValue'])"></td>
								</tr>
							</template>
						</tbody>
						</template>
					</table>
				</article>
			</section>
		</template>
	</div>
		
</section>
	