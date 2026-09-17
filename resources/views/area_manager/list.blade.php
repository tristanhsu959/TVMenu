	
	<section x-show="!showUpdateForm" x-data="{storeList:@js($viewModel->listData())}" x-init="$nextTick(() => { ui('#tab-0'); })" class="store-list container">
		<article x-show="!response.hasResult" class="secondary-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		
		<div x-show="response.hasResult" class="store-content">
			<div class="tabs cyan-text">
				<template x-for="(sheetName, sheetIdx) in Object.keys(storeList)" :key="sheetIdx">
					<a x-text="sheetName" :data-ui="`#tab-${sheetIdx}`"></a>
				</template>
			</div>

			<!-- 門店 -->
			<template x-for="(sheetName, sheetKey) in Object.keys(storeList)" :key="sheetKey">
			<div :id="`tab-${sheetKey}`" class="page paddin">
				<section class="area-store scrollbar">
					<table class="stripes">
						<thead>
							<tr>
								<th>區域</th>
								<th>門店代號</th>
								<th>門店名稱</th>
								<th>加盟主</th>
								<th>督導</th>
							</tr>
						</thead>
						<tbody>
							<template x-for="(store, idx) in storeList[sheetName]" :key="idx">
							<tr>
								<td x-text="store.areaName"></td>
								<td x-text="store.storeKey"></td>
								<td x-text="store.storeName"></td>
								<td x-text="store.bossName"></td>
								<td x-text="store.areaManager"></td>
							</tr>
							</template>
								
						</tbody>
					</table>
				</section>
			</div>
			</template>
		</div>
	</section>
	
