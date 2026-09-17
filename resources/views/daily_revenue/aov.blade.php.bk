<section class="aov-list container">
	<article x-show="!response.hasResult" class="secondary-container border">
		<div class="row">
			<i>info</i><div class="max">查無符合資料</div>
		</div>
	</article>
	
	<div x-data="{statistics:@js($viewModel->statisticsData())}" x-init="console.log(statistics.data.storeType)" x-show="response.hasResult" class="statistics">
		<template x-for="(typeName, typeKey) in statistics.data.storeType" :key="typeKey">
			<section x-data="{mainData:statistics.data.total[typeKey], detailData:statistics.data.subTotal[typeKey]}" x-show="mainData" class="statistics-aov">
				<h6 x-text="typeName"></h6>
				<article class="primary-container fill">
					<ul class="list border">
						<li>
							<table>
								<thead>
									<tr>
										<template x-for="(header, headerIdx) in statistics.data.header" :key="headerIdx">
											<th x-text="header"></th>
										</template>
									</tr>
								</thead>
								<tbody>
									<tr>
										<td x-text="mainData['saleMonth']"></td>
										<td x-text="mainData['areaName']"></td>
										<td x-text="mainData['storeCount']"></td>
										<td x-text="Helper.formatDollar(mainData['amount'])"></td>
										<td x-text="Helper.formatDollar(mainData['avgStoreAmount'])"></td>
										<td x-text="mainData['visitors']"></td>
										<td x-text="mainData['avgVisitors']"></td>
										<td x-text="Helper.formatDollar(mainData['avgOrderValue'])"></td>
									</tr>
								</tbody>
							</table>
						</li>
						<li>
							<details>
								<summary>
									<button>
										<i>more_horiz</i>
										<span>More</span>
									</button>
								</summary>
								<ul class="list border">
									<li>
										<table>
											<thead>
												<tr>
													<template x-for="(header, headerIdx) in statistics.data.header" :key="headerIdx">
														<th x-text="header"></th>
													</template>
												</tr>
											</thead>
											<tbody>
												<template x-for="(detail, detailIdx) in detailData" :key="detailIdx">
												<tr>
													<td x-text="detail['saleMonth']"></td>
													<td x-text="detail['areaName']"></td>
													<td x-text="detail['storeCount']"></td>
													<td x-text="Helper.formatDollar(detail['amount'])"></td>
													<td x-text="Helper.formatDollar(detail['avgStoreAmount'])"></td>
													<td x-text="detail['visitors']"></td>
													<td x-text="detail['avgVisitors']"></td>
													<td x-text="Helper.formatDollar(detail['avgOrderValue'])"></td>
												</tr>
												</template>
											</tbody>
										</table>
									</li>
								</ul>
							</details>
						</li>
					</ul>
				</article>
			</section>
		</template>
	</div>
		
</section>
	