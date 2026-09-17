	<!-- 區域彙總 -->
	<div x-data="{area:@js($viewModel->statisticsData('area'))}" class="page padding active" id="tab-area">
		<section class="statistics-area">
			<table>
				<thead>
					<tr>
						<template x-for="col in area.header" :key="col">
							<th class="s2" x-text="col"></th>
						</template>
					</tr>
				</thead>
				<tbody>
					<template x-for="(areaData, idx) in area.data" :key="idx">
					<tr>
						<template x-for="(col, colKey) in area.header" :key="colKey">
							<td x-text="areaData[colKey]"></td>
						</template>
					</tr>
					</template>
				</tbody>
			</table>
		</section>
	</div>