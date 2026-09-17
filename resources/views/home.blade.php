@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/home/main.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src=""></script>
@endpush

@section('navHome')
<div class="navbar-home">
	<span class="sales">sales</span><span class="dashboard">Dashboard</span>
</div>
@endsection

@section('content')
<section class="section-wrapper container">
	<section class="container">
		<h6 class="red-text">Dashboard</h6>
		<pre class="red-border"><i class="right-padding">commit</i>版本更新日期：2026-09-03</pre>
		<pre class="red-border"><i class="right-padding">commit</i>因版本更新，查詢可能顯示空白，請按 <code class="grey white-text tiny-padding">Ctrl</code> + <code class="grey white-text tiny-padding">F5</code> 清除瀏覽器暫存</pre>
	
		@if(!$viewModel->hasSetPassword())
		<pre class="red-border">您尚未設定系統密碼，請點擊 <i class="orange circle small">person</i> 進行密碼設定</pre>
		@endif
		
		@if(!$viewModel->hasSetEmail())
		<pre class="red-border red white-text">您尚未設定Mail信箱，請盡速設定完成，以避免無法使用忘記密碼功能</pre>
		@endif
	</section>
	
	<!--section class="container">
		<h6 class="red-text">Release Notes</h6>
		<pre class="red-border"><i class="right-padding">kid_star</i>銷售統計/出貨總量查詢：查詢可跨分類複選</pre>
		<pre class="red-border"><i class="right-padding">kid_star</i>門店營收：新增客單統計查詢</pre>
		<pre class="red-border"><i class="right-padding">kid_star</i>門店進貨及銷售：新增功能(測試中)</pre>
	</section-->
	
{{--
	<div class="purchase dp-2">
		<h6>台北 屯山</h6>
		<ul class="list-group">
			<li class="list-group-item"><span>橙汁排骨</span><span>173 包</span></li>
			<li class="list-group-item"><span>紅燒牛肉調理包</span><span>386 組</span></li>
			<li class="list-group-item"><span>滷肉</span><span>108 包</span></li>
			<li class="list-group-item"><span>非基改雞蛋豆腐</span><span>202 個</span></li>
		</ul>
	</div>
	<div class="purchase dp-2">
		<h6>高雄 二崙</h6>
		<ul class="list-group">
			<li class="list-group-item"><span>橙汁排骨</span><span>173 包</span></li>
			<li class="list-group-item"><span>紅燒牛肉調理包</span><span>386 組</span></li>
			<li class="list-group-item"><span>滷肉</span><span>108 包</span></li>
			<li class="list-group-item"><span>非基改雞蛋豆腐</span><span>202 個</span></li>
		</ul>
	</div>
--}}
</section>
@endsection
