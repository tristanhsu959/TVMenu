@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/user/list.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/user/list.js') }}" defer></script>
@endpush

@section('content')
<!-- Content -->
<div x-data="userList(@js($viewModel->listResponseData()), @js($viewModel->listData()))" class="content-wrapper">
	<header class="page-nav">
		<nav>
			<a :href="list.createRoute" class="btn-create button circle"><i>add</i></a>
			
			<nav x-show="response.hasResult" class="no-space filter">
				<div class="field label border prefix field-filter-dark small">
					<i>filter_alt</i>
					<input type="text" x-model="$store.userFilter.filter">
					<label>篩選</label>
				</div>
				<button class="right-round" @click="$store.userFilter.reset()"><i>backspace</i></button>
			</nav>
		</nav>
	</header>
	
	<form x-show="response.status" action="" method="post" x-ref="userListForm">
		@csrf
		<section class="user-list container">
			<article x-show="list.data.length == 0" class="error-container border">
				<div class="row">
					<i>info</i><div class="max">查無符合資料</div>
				</div>
			</article>
			
			<table x-show="list.data.length > 0" class="stripes border odd-cyan">
				<thead>
					<tr>
						<th class="min">#</th>
						<th>帳號</th>
						<th>顯示名稱</th>
						<th>部門</th>
						<th>EMail</th>
						<th>狀態</th>
						<th>最後登入時間</th>
						<th class="right-align">操作</th>
					</tr>
				</thead>
				<tbody>
				<template x-for="(user, idx) in filterUsers" :key="idx">
					<tr>
						<td x-text="idx+1"></td>
						<td><span x-text="user.userAccount"></span><i class="green-text" x-show="user.hasSysPassword">passkey</i></td>
						<td x-text="user.userDisplayName"></td>
						<td x-text="user.department"></td>
						<td x-text="user.email"></td>
						<td>
							<i class="green-text" x-show="user.isActive">check_circle</i>
							<i class="red-text" x-show="! user.isActive">x_circle</i>
						</td>
						<td class="min" x-text="user.accessTime"></td>
						<td class="right-align action">
							<a :href="list.updateRoute.replace('_ID', user.userId)" class="btn-edit button circle small" :disabled="user.roleGroup == list.supervisorGroupId">
								<i class="small">edit</i>
							</a>
							<a @click.prevent="confirmDelete($el.href)" :href="list.deleteRoute.replace('_ID', user.userId)" class="btn-delete button circle small" :disabled="user.roleGroup == list.supervisorGroupId">
								<i class="small">delete</i>
							</a>
						</td>
					</tr>
				</template>
				</tbody>
			</table>
			
		</section>
	</form>

</div>
<!-- Content -->
@endsection