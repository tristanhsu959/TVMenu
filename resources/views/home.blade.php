@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/home.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/home.js') }}" defer></script>
@endpush

@section('content')
<section class="content-wrapper">
	<h1 class="red-text">TV Menu</h1>
	<details x-data="mediaForm" open>
		<summary>
			<button class="pink">
				<span>媒體庫</span>
				<i>expand_more</i>
			</button>
		</summary>
		
		<article>
			<button @click="listMedia">列表 <code class="round white-text light-blue">GET</code> \api\tvMenu\medias</button>
			<blockquote class="blue-border">
				<code>Request</code>
				<div>N/A</div>
			</blockquote>
			<blockquote class="pink-border">
				<code>Response</code>
				<div>
				{
					mediaName: string,
					mediaFile: string,
					mediaFileUrl: string,
					mediaLink: string,
					stDate: date,
					endDate: date,
					type: integer,
					typeName: string,
					enabled: boolean
				}
				</div>
			</blockquote>
		</article>
		
		<article>
			<button class="item" @click="createMedia">新增 <code class="round white-text light-blue">POST</code> \api\tvMenu\medias</button>
			
			<blockquote class="blue-border">
				<code>Request</code>
				<div>
				{
					mediaName: string,
					uploadFile: file,
					uploadLink: string,
					stDate: date,
					endDate: date,
					type: integer[1:image, 2:video],
					enabled: boolean
				}
				</div>
			</blockquote>
			<blockquote class="pink-border">
				<code>Response</code>
				<div>
				{
					id: integer
					mediaName: string,
					mediaUrl: string,
					stDate: date,
					endDate: date,
					type: integer,
					typeName: string,
					enabled: boolean
				}
				</div>
			</blockquote>
			
			<nav class="wrap">
				<div class="field label border small">
					<input type="text" x-model="formData.mediaName">
					<label>媒體名稱</label>
				</div>
				<div x-show="formData.type == 1" class="field label border small" style="width:400px">
					<input type="file" @change="handleFileChange" accept="image/png, image/jpeg">
					<input type="text">
					<label>上傳圖檔</label>
				</div>
				<div  x-show="formData.type == 2" class="field label border small" style="width:400px">
					<input type="text" x-model="formData.uploadLink">
					<label>影片連結</label>
				</div>
				<div class="field label border small">
					<input type="date" maxlength="10" x-model="formData.stDate">
					<label>開始日期</label>
				</div>
				<div class="field label border small">
					<input type="date" maxlength="10" x-model="formData.endDate">
					<label>結束日期</label>
				</div>
				<nav>
					<label class="radio">
						<input type="radio" x-model="formData.type" value="1">
						<span>圖檔</span>
					</label>
					<label class="radio">
						<input type="radio" name="type" x-model="formData.type" value="2">
						<span>影片</span>
					</label>
				</nav>
				
				<label class="switch">
					<input type="checkbox" x-model="formData.enabled" value="1">
					<span>啟用</span>
				</label>
			</nav>
		</article>
	</details>
</section>
@endsection
