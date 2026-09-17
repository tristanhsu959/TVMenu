<section x-data="statisticsData(@js($viewModel->statisticsData('store')))" class="ezorder-pos-list container">
	<article x-show="!response.hasResult" class="secondary-container border">
		<div class="row">
			<i>info</i><div class="max">查無符合資料</div>
		</div>
	</article>
	
	<div x-show="response.hasResult" class="statistics">
		<!-- 門店 -->
		<div class="statistics-list">
			<section class="statistics-store scrollbar no-tab" :class="response.brandCode">
				<table class="stripes border">
					<thead>
						<tr>
							<template x-for="(col, idx) in header" :key="idx">
								<th x-text="col"></th>
							</template>
						</tr>
					</thead>
					<tbody>
						<template x-for="(storeData, storeIdx) in filterStore" :key="storeIdx">
						<tr>
							<template x-for="(values, row) in storeData" :key="row">
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
	