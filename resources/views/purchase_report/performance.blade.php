 
	<section x-data="statisticsPerformance(@js($viewModel->statisticsData()))" class="performance-list container">
		<article x-show="!response.hasResult" class="secondary-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		
		<div x-show="response.hasResult" class="store-content">
			<div class="tabs cyan-text">
				<template x-for="(sheetName, sheetId) in statistics.report.sheets" :key="sheetId">
					<a x-text="sheetName" @click="activeSheet = sheetId" :data-ui="`#page-${sheetId}`" :class="activeSheet == sheetId ? 'active':''"></a>
				</template>
			</div>
			
			<!-- 門店 -->
			<template x-for="(sheetName, sheetId) in statistics.report.sheets" :key="sheetId">
			<div class="page padding" :id="`page-${sheetId}`" >
				<section class="statistics-store scrollbar" :class="response.brandCode">
					<table class="stripes">
						<thead>
							<tr>
								<template x-for="(header, idx) in statistics.report.header" :key="idx">
									<th x-text="header"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(rowData, rowIdx) in statistics.report.data[sheetName]" :key="rowIdx">
							<tr>
								<template x-for="(row, rowKey) in rowData" :key="rowKey">
 									<td>
										<template x-if="statistics.report.amountFields.includes(rowKey)">
											<span x-text="Helper.formatDollar(row)" class="pink-text"></span>
										</template>
										<template x-if="!statistics.report.amountFields.includes(rowKey)">
											<span x-text="row"></span>
										</template>
									</td>
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
