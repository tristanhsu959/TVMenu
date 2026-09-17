 
	<section x-data='statisticsFactory(@json($viewModel->statistics))' class="factory-list container">
		<article x-show="!response.hasResult" class="secondary-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		
		<div x-show="response.hasResult" class="factory-content">
			<div class="tabs cyan-text">
				<a data-ui="#page-qty" class="active">月總量</a>
				<a data-ui="#page-avg">月均量</a>
			</div>
			
			<!-- 工廠 -->
			<template x-for="(data, type) in statistics.data" :key="type">
			<div class="page padding active" :id="`page-${type}`">
				<section class="statistics-factory scrollbar" :class="response.brandCode">
					<table>
						<thead>
							<tr>
								<template x-for="header in statistics.header">
									<th x-text="header"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(rows, idx) in data" :key="idx">
								<tr>
									<template x-for="row in rows">
										<td x-text="row"></td>
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
