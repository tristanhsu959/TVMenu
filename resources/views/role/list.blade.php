@extends('layouts.app')
@use('App\Enums\RoleGroup')
@use('App\Enums\Area')
@use('App\Libraries\HelperLib')

@push('styles')
	<link href="{{ HelperLib::versionAsset('styles/role/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/role/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
	<header class="page-nav">
		<nav>
			<a href="{{ route('role.create') }}" class="btn-create button circle"><i>add</i></a>
		</nav>
	</header>
	
@if($viewModel->status() === TRUE)	
	<form x-data='roleList(@json($viewModel->list), @json($viewModel->options))' action="" method="post" x-ref="roleListForm">
		@csrf
		<section class="role-list container">
			<article x-show="list.length == 0" class="error-container border">
				<div class="row">
					<i>info</i><div class="max">查無符合資料</div>
				</div>
			</article>
			
			<table x-show="list.length > 0" class="stripes border odd-purple">
				<thead>
					<tr>
						<th class="min">#</th>
						<th>身份</th>
						<th>權限群組</th>
						<th>管理區域</th>
						<th>更新時間</th>
						<th class="right-align">操作</th>
					</tr>
				</thead>
				<tbody>
				<template x-for="(item, idx) in list" :key="idx">
					<tr>
						<td x-text="idx + 1"></td>
						<td x-text="item.roleName"></td>
						<td x-text="options.roleGroup[item.roleGroup]"></td>
						<td class="col-area">
							<template x-for="areaId in item.roleArea" :key="areaId">
								<div class="chip round pink4 white-text" x-text="options.areas[areaId]"></div>
							</template>
						</td>
						<td x-text="item.updateAt"></td>
						<td class="right-align action">
							<a :href="'{{ route('role.update', ['ROLE_ID']) }}'.replace('ROLE_ID', item.roleId)" class="btn-edit button circle small" :disabled="options.supervisorGroupId == item.roleGroup">
								<i class="small">edit</i>
							</a>
							<a @click.prevent="confirmDelete($el.href)" :href="'{{ route('role.delete', ['ROLE_ID']) }}'.replace('ROLE_ID', item.roleId)" class="btn-delete button circle small" :disabled="options.supervisorGroupId == item.roleGroup">
								<i class="small">delete</i>
							</a>
						</td>
					</tr>
				</template>
				</tbody>
			</table>

		</section>
	</form>
@endif
<!-- Content -->
@endsection