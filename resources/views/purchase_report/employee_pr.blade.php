 
	<section x-data="employeePrData(@js($viewModel->statisticsData()))" class="employee-pr-list container">
		<div class="data-content">
			<div class="tabs cyan-text">
				<a class="active" data-ui="#tab-employee">員購</a>
				<a data-ui="#tab-pr">公關</a>
			</div>

			<!-- 員購 -->
			<div class="page paddin active" id="tab-employee">
				<section class="statistics-table scrollbar" :class="response.brandCode">
					<table class="">
						<thead>
							<tr>
								<template x-for="(name, idx) in employeeData.header" :key="idx">
									<th x-text="name"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(order, dataIdx) in employeeData.data" :key="dataIdx">
								<tr :class="orderHead(order.orderDate)" class="employee">
									<td x-text="order.orderDate" class="no-wrap"></td>
									<td x-text="order.orderNo"></td>
									<td x-text="order.productName"></td>
									<td x-text="order.shortCode"></td>
									<td x-text="order.unit"></td>
									<td x-text="order.price ? Helper.formatDollar(order.price, 2) : ''"></td>
									<td x-text="order.qty"></td>
									<td x-text="Helper.formatDollar(order.totalAmount, 2)"></td>
									<td x-text="order.factoryName"></td>
									<td x-text="order.memo"></td>
								</tr>
							</template>
							
							<template x-if="employeeData.data.length <= 0">
								<tr><td colspan="10" class="red-text">查無符合資料</td></tr>
							</template>
						</tbody>
					</table>
				</section>
			</div>
			
			<!-- 公關 -->
			<div class="page paddin" id="tab-pr">
				<section class="statistics-table scrollbar" :class="response.brandCode">
					<table class="">
						<thead>
							<tr>
								<template x-for="(name, idx) in prData.header" :key="idx">
									<th x-text="name"></th>
								</template>
							</tr>
						</thead>
						<tbody>
							<template x-for="(order, idx) in prData.data" :key="idx">
								<tr :class="orderHead(order.orderDate)" class="pr">
									<td x-text="order.orderDate" class="no-wrap"></td>
									<td x-text="order.orderNo"></td>
									<td x-text="order.productName"></td>
									<td x-text="order.shortCode"></td>
									<td x-text="order.unit"></td>
									<td x-text="Helper.formatDollar(order.price, 2)"></td>
									<td x-text="order.qty"></td>
									<td x-text="Helper.formatDollar(order.totalAmount, 2)"></td>
									<td x-text="order.factoryName"></td>
									<td x-text="order.memo"></td>
								</tr>
							</template>
							
							<template x-if="prData.data.length <= 0">
								<tr><td colspan="10" class="red-text">查無符合資料</td></tr>
							</template>
						</tbody>
					</table>
				</section>
			</div>
		</div>
	</section>
