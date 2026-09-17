 
	<section x-data="extraData(@js($viewModel->statisticsData()))" class="extra-order-list container">
		<div class="data-content">
			<div class="tabs cyan-text">
				<a class="active" data-ui="#tab-extra-order">追加</a>
			</div>

			<!-- 員購 -->
			<div class="page paddin active" id="tab-extra-order">
				<section class="statistics-table scrollbar" :class="response.brandCode">
					<table class="">
						<thead>
							<tr>
								<template x-for="(name, idx) in extraOrder.header" :key="idx">
									<th x-text="name"></th>
								</template>
							</tr>
						</thead>
						<template x-for="(order, ohIdx) in extraOrder.data" :key="ohIdx">
							<tbody>
								<tr class="extra-order order-header">
									<td x-text="order.areaName"></td>
									<td x-text="order.storeKey"></td>
									<td x-text="order.storeName"></td>
									<td x-text="order.orderDate" class="no-wrap"></td>
									<td x-text="order.orderNo"></td>
									<td x-text="order.productName"></td>
									<td x-text="order.shortCode"></td>
									<td x-text="order.unit"></td>
									<td x-text="order.price"></td>
									<td x-text="order.qty"></td>
									<td x-text="Helper.formatDollar(order.totalAmount, 2)"></td>
									<td x-text="order.factoryName"></td>
									<td x-text="order.memo"></td>
								</tr>
							
								<template x-for="(item, idx) in order.items" :key="idx">
									<tr class="extra-order">
										<td x-text="item.areaName??''"></td>
										<td x-text="item.storeKey??''"></td>
										<td x-text="item.storeName??''"></td>
										<td x-text="item.orderDate" class="no-wrap"></td>
										<td x-text="item.orderNo"></td>
										<td x-text="item.productName"></td>
										<td x-text="item.shortCode"></td>
										<td x-text="item.unit"></td>
										<td x-text="item.price"></td>
										<td x-text="item.qty"></td>
										<td x-text="Helper.formatDollar(item.totalAmount, 2)"></td>
										<td x-text="item.factoryName"></td>
										<td x-text="item.memo"></td>
									</tr>
								</template>
							</tbody>
						</template>
						
						<template x-if="extraOrder.data.length <= 0">
							<tbody>						
								<tr><td colspan="13" class="red-text">查無符合資料</td></tr>
							</tbody>
						</template>
					</table>
				</section>
			</div>
			
		</div>
	</section>
