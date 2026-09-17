
<dialog x-data='app.profile(@json($initData))' class="left" id="profile">
	<header>
		<nav>
			<h6 class="max">Profile</h6>
			<button class="transparent circle large" data-ui="#profile"><i>close</i></button>
		</nav>
	</header>
	<div class="space"></div>
	<div class="dialog-body">
		<div class="section info-head">
			<i class="fill">person_pin</i>
			<p x-text="employeeId"></p>
			<p class="name" x-text="displayName"></p>
			<p class="mail" x-text="mail"></p>
		</div>
		<div class="section info-body">
			<p x-text="department"></p>
			<p x-text="title"></p>
		</div>
	</div>
	<div class="section info-body">
			<p>
				<span>{{ $currentUser->department }}</span>
				<span>{{ $currentUser->title }}</span>
			</p>
			<p>
				<span>管理區域</span>
				@if(empty($currentUser->roleArea))
					<span class="text-danger">未設定</span>
				@else
					<div class="user-area">
					@foreach($currentUser->roleArea as $area)
						<span class="badge">{{ Area::getLabelByValue($area) }}</span>
					@endforeach
					</div>
				@endif
			</p>
			<p>{{ $currentUser->company }}</p>
		</div>
	<a href="{{ route('logout') }}" class="btn-logout button extend circle">
		<i>logout</i>
		<span>登出</span>
	</a>
</dialog>
