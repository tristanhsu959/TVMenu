 
	<section x-data="statisticsInfo(@js($viewModel->statisticsData()))" class="statistics-list container">
		<article x-show="!info.hasResult" class="secondary-container border">
			<div class="row">
				<i>info</i><div class="max">查無符合資料</div>
			</div>
		</article>
		
		<div x-show="info.hasResult" class="list-content">
			<div class="tabs cyan-text">
				<a data-ui="#tab-product" class="active">一般產品</a>
				<a data-ui="#tab-preorder" x-show="info.hasPreorder">預購產品</a>
				<a data-ui="#tab-supplier" x-show="info.hasSupplier">供應商產品</a>
			</div>
				
			<!-- Product list -->
			<div class="page paddin active" id="tab-product">
				<section class="statistics-table scrollbar" :class="response.brandCode">
					<table class="stripes">
						<thead>
							<tr>
								<template x-for="(name, idx) in info.product.header" :key="idx">
									<th x-text="name"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(data, productIdx) in info.product.list" :key="productIdx">
							<tr>
								<td x-text="data.productName"></td>
								<td x-text="data.erpNo"></td>
								<td x-text="data.shortCode"></td>
								<td x-text="Helper.formatDollar(data.price)"></td>
								<td x-text="data.unit"></td>
								<td x-text="data.opCenter"></td>
								<td x-text="data.factoryName"></td>
								<td x-text="`${data.warehouseNo} ${data.warehouse}`"></td>
								<td>
									<i x-show="data.status" class="green-text fill">check_circle</i>
									<i x-show="!data.status" class="red-text fill">cancel</i>
								</td>
								<td x-text="data.memo || ''"></td>
							</tr>
							</template>
						</tbody>
					</table>
				</section>
			</div>
			
			<!-- 預購產品 -->
			<div class="page paddin" id="tab-preorder" x-show="info.hasPreorder">
				<section class="statistics-table scrollbar" :class="response.brandCode">
					<table class="stripes">
						<thead>
							<tr>
								<template x-for="(name, idx) in info.preorder.header" :key="idx">
									<th x-text="name"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(data, productIdx) in info.preorder.list" :key="productIdx">
							<tr>
								<td x-text="data.productName"></td>
								<td x-text="data.shortCode"></td>
								<td x-text="data.unit"></td>
								<td x-text="data.opCenter"></td>
								<td>
									<i x-show="data.status" class="green-text fill">check_circle</i>
									<i x-show="!data.status" class="red-text fill">cancel</i>
								</td>
								<td x-text="data.memo || ''"></td>
							</tr>
							</template>
						</tbody>
					</table>
				</section>
			</div>
			
			<!-- 供應商產品 -->
			<div class="page paddin" id="tab-supplier" x-show="info.hasSupplier">
				<section class="statistics-table scrollbar" :class="response.brandCode">
					<table class="stripes">
						<thead>
							<tr>
								<template x-for="(name, idx) in info.supplier.header" :key="idx">
									<th x-text="name"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(data, productIdx) in info.supplier.list" :key="productIdx">
							<tr>
								<td x-text="data.productName"></td>
								<td x-text="data.erpNo"></td>
								<td x-text="data.shortCode"></td>
								<td x-text="data.supplierNo"></td>
								<td x-text="data.supplierName"></td>
								<td x-text="Helper.formatDollar(data.price)"></td>
								<td x-text="Helper.formatDollar(data.purchasePrice)"></td>
								<td x-text="data.unit"></td>
								<td x-text="data.opCenter"></td>
								<td x-text="data.factoryName"></td>
								<td>
									<i x-show="data.status" class="green-text fill">check_circle</i>
									<i x-show="!data.status" class="red-text fill">cancel</i>
								</td>
								<td x-text="data.memo || ''"></td>
							</tr>
							</template>
						</tbody>
					</table>
				</section>
			</div>
		</div>
	</section>
