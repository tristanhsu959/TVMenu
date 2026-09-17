<section x-data="{area:@js($viewModel->statisticsData('area')), hasResult:@js($viewModel->statisticsData('hasResult'))}" class="ezorder-pos-list container">
	<article x-show="!hasResult" class="secondary-container border">
		<div class="row">
			<i>info</i><div class="max">查無符合資料</div>
		</div>
	</article>
	
	<div x-show="hasResult" class="statistics">
		<!-- 門店 -->
		<div class="statistics-list padding">
			<section class="statistics-area scrollbar" :class="response.brandCode">
				<table class="stripes border">
					<thead>
						<tr>
							<template x-for="(col, idx) in area.header" :key="idx">
								<th x-text="col"></th>
							</template>
						</tr>
					</thead>
					<tbody>
						<template x-for="(areaData, storeIdx) in area.data" :key="storeIdx">
						<tr>
							<template x-for="(values, row) in areaData" :key="row">
								<td x-text="values"></td>
							</template>
						</tr>
						</template>
					</tbody>
				</table>
			</section>
		</div>
	</div>
		
</section>
	