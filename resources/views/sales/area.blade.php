	<!-- 區域彙總 -->
	<div x-data="{area:@js($viewModel->statisticsData('area'))}" class="page padding active scroll" id="tab-area">
		<section class="statistics-area">
			<table>
				<thead>
					<tr>
						<th x-text="area.header.areaName"></th>
						<th x-text="area.header.storeCount"></th>
						<template x-for="pName in area.header.products" :key="pName">
							<th x-text="pName"></th>
						</template>
					</tr>
				</thead>
				<tbody>
					<template x-for="(areaData, areaId) in area.data" :key="areaId">
					<tr>
						<td x-text="areaData.areaName"></td>
						<td x-text="areaData.storeCount"></td>
						<template x-for="(pName, pId) in area.header.products" :key="pId">
						<td>
							<span x-show="!$store.sales.showAmount" x-text="areaData.products[pId]?.totalQty || 0"></span>
							<span x-show="$store.sales.showAmount" x-text="Helper.formatDollar(Math.round(areaData.products[pId]?.totalAmount || 0))"></span>
						</td>
						</template>
					</tr>
					</template>
				</tbody>
			</table>
		</section>
	</div>