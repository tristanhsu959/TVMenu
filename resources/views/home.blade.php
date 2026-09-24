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
	<details x-data="mediaForm">
		<summary>
			<button class="pink">
				<span>媒體庫</span>
				<i>expand_more</i>
			</button>
		</summary>
		
		<article>
			<nav class="wrap">
				<div class="field label border small">
					<input type="text" x-model="formData.id">
					<label>ID</label>
				</div>
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
		
		<article>
			<button @click="listMedia" class="pink4 small-elevate">列表 <code class="round white-text pink10">GET</code> \api\tvMenu\medias</button>
			<pre class="blue-border"><code>Request</code><div>N/A</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
	status: true|false
	data: [
		{
			id: integer
			mediaName: string,
			mediaUrl: string,
			stDate: date,
			endDate: date,
			type: integer,
			typeName: string,
			enabled: boolean
		}, .....
	]
	msg:
}</div></pre>
		</article>
		
		<article>
			<button class="item pink4 small-elevate" @click="createMedia">新增 <code class="round white-text pink10">POST</code> \api\tvMenu\medias</button>
			<pre class="blue-border"><code>Request</code><div>{
	mediaName: string,
	uploadFile: file,
	uploadLink: string,
	stDate: date,
	endDate: date,
	type: integer[1:image, 2:video],
	enabled: boolean
}
</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
	status: true|false
	data: {
		id: integer
		mediaName: string,
		mediaUrl: string,
		stDate: date,
		endDate: date,
		type: integer,
		typeName: string,
		enabled: boolean
	}
	msg:
}</div></pre>
		</article>
		
		<article>
			<button class="item pink4 small-elevate" @click="editMedia">編輯 <code class="round white-text pink10">GET</code> \api\tvMenu\medias\{id}</button>
			<button class="item pink4 small-elevate" @click="updateMedia">編輯 <code class="round white-text pink10">PUT</code> \api\tvMenu\medias\{id}</button>
			
			<pre class="blue-border"><code>Request GET</code><div>N/A</div></pre>
			<pre class="blue-border"><code>Request PUT</code><div>{
	mediaName: string,
	stDate: date,
	endDate: date,
	enabled: boolean
}</div></pre>

			<pre class="pink-border"><code>Response</code><div>{
	status: true|false
	data: {
		id: integer
		mediaName: string,
		mediaUrl: string,
		stDate: date,
		endDate: date,
		type: integer,
		typeName: string,
		enabled: boolean
	}
	msg:
}</div></pre>
		</article>
		
		<article>
			<button class="item pink4 small-elevate" @click="deleteMedia">刪除 <code class="round white-text pink10">DELETE</code> \api\tvMenu\medias\{id}</button>
			
			<pre class="blue-border"><code>Request</code><div>N/A</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
	status: true|false
	data: []
	msg:
}</div></pre>
		</article>
	</details>
	
	
	
<!----- Menu ------------------------------------------------------------->
	<div class="space"></div>
	<details x-data="menuForm">
		<summary>
			<button class="blue">
				<span>Menu</span>
				<i>expand_more</i>
			</button>
		</summary>
		
		<article>
			<nav class="wrap">
				<div class="field border fill small">
					<textarea x-model="mockPayload"></textarea>
					<output class="red-text">Paste JSON here.</output>
				</div>
			</nav>
		</article>
		
		<article>
			<button @click="listMenu" class="blue4 small-elevate">列表 <code class="round white-text blue10">GET</code> \api\tvMenu\menus</button>
			<pre class="blue-border"><code>Request</code><div>N/A</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
	status: true|false
	data: [
		{
			id: integer
			menuName: string,
			isDefault: boolean
		}, .....
	]
	msg:
}</div></pre>
		</article>
		
		<article>
			<button class="item blue4 small-elevate" @click="createMenu">新增 <code class="round white-text blue10">POST</code> \api\tvMenu\menus</button>
			<pre class="blue-border"><code>Request</code><div>{
	menuName: string,
	isDefault: boolean,
	medias:[
		{
			id: integer,
			duration: integer,
			sort: integer
		}, ......
	]
}
</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
	status: true|false
	data: []
	msg:
}</div></pre>
		</article>
		
		<article>
			<button class="item blue4 small-elevate" @click="editMenu">編輯 <code class="round white-text blue10">GET</code> \api\tvMenu\menus\{id}</button>
			<button class="item blue4 small-elevate" @click="updateMenu">編輯 <code class="round white-text blue10">PUT</code> \api\tvMenu\menus\{id}</button>
			
			<pre class="blue-border"><code>Request GET</code><div>N/A</div></pre>
			<pre class="blue-border"><code>Request PUT</code><div>{
	menuName: string,
	isDefault: boolean,
	medias:[
		{
			id: integer,
			duration: integer,
			sort: integer
		}, ......
	]
}</div></pre>

			<pre class="pink-border"><code>Response</code><div>{
	status: true|false
	data: {
		id: integer
		mediaName: string,
		mediaUrl: string,
		stDate: date,
		endDate: date,
		type: integer,
		typeName: string,
		enabled: boolean
	}
	msg:
}</div></pre>
		</article>
		
		<article>
			<button class="item blue4 small-elevate" @click="deleteMenu">刪除 <code class="round white-text blue10">DELETE</code> \api\tvMenu\menus\{id}</button>
			
			<pre class="blue-border"><code>Request</code><div>N/A</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
	status: true|false
	data: []
	msg:
}</div></pre>
		</article>
	</details>
</section>
@endsection
