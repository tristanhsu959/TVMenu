 
	<section x-data="storeInfo(@js($viewModel->statisticsData()))" class="store-info container">
		<article x-show="!response.hasResult" class="secondary-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
			
		<div x-show="response.hasResult" class="store-content">
			<!-- 門店 -->
			<button class="chip round purple-text no-border" x-text="`共 ${statistics.info.store.length} 家門店`"></button>
				
			<section class="statistics-store scrollbar" :class="response.brandCode">
				<table class="stripes">
					<!--caption class="left-align" x-text="`共${statistics.info.store.length}家門店`"></caption-->
					<thead>
						<tr>
							<template x-for="(name, idx) in statistics.info.header" :key="idx">
								<th x-text="name"></th>
							</template>
						</tr>
					</thead>
					<tbody>
						<template x-for="(store, idx) in filterStore" :key="idx">
						<tr>
							<template x-for="(row, rowIdx) in store" :key="rowIdx">
								<td x-text="row"></td>
							</template>
						</tr>
						</template>
					</tbody>
				</table>
			</section>
		</div>
	</section>
