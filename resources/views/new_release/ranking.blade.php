	<!-- 排名 -->
	<div x-data="{top:@js($viewModel->statisticsData('top'))}" class="page padding" id="tab-ranking-asc">
		<section class="statistics-ranking">
			<article class="border ranking-top">
				<ul class="list border">
					<!--只顯示第一家,因數量太多-->
					<template x-for="(items, topRanking) in top" :key="topRanking">
					<li>
						<div class="ranking" x-text="topRanking + 1"></div>
						<div class="info">
							<span x-text="items[0]['areaName']"></span>
							<div class="name" x-text="items[0]['storeName']"></div>
							<span x-text="items[0]['shopId']"></span>
						</div>
						<span class="badge none primary" x-text="items[0]['qty']"></span>
						<div class="max"></div>
						<label x-text="`共  ${items.length} 店家`"></label>
					</li>
					</template>
				</ul>
			</article>
		</section>
	</div>
	<div x-data="{last:@js($viewModel->statisticsData('last'))}" class="page padding" id="tab-ranking-desc">
		<section class="statistics-ranking">
			<article class="border ranking-last">
				<ul class="list border">
					<!--只顯示第一家,因數量太多-->
					<template x-for="(items, lastRanking) in last" :key="lastRanking">
					<li>
						<div class="ranking" x-text="lastRanking + 1"></div>
						<div class="info">
							<span x-text="items[0]['areaName']"></span>
							<div class="name" x-text="items[0]['storeName']"></div>
							<span x-text="items[0]['shopId']"></span>
						</div>
						<span class="badge none secondary" x-text="items[0]['qty']"></span>
						<div class="max"></div>
						<label x-text="`共  ${items.length} 店家`"></label>
					</li>
					</template>
				</ul>
			</article>
		</section>
	</div>
	