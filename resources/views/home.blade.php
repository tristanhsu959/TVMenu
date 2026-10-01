@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/home.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/home.js') }}" defer></script>
@endpush

@section('content')
<section x-data="{testing:false}" class="content-wrapper">
	<h1 class="red-text">TV Menu</h1>
	
<!----- Media ------------------------------------------------------------->
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
			<button @click="listMedia" class="pink4 small-elevate">列表 <code class="round white-text pink10">GET</code> {{url('api/tvMenu/medias') }}</button>
			<button @click="listActiveMedia" class="pink4 small-elevate">列表 <code class="round white-text pink10">GET</code> {{url('api/tvMenu/medias/active')}}</button>
			<code>{{url('api/tvMenu/medias') }}/{active?}</code>
			
			<pre class="blue-border"><code>Request</code><div>N/A</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
    "status": true | false,
    "data": [
        {
            "id": 1,
            "mediaName": "001",
            "mediaUrl": "http://laravel.local:9999/storage/tvMenu/XPoIVwvVejQVjeP8GIDf1Tbp5ZREHCQv62r5nOaJ.png",
            "stDate": "2026-09-23",
            "endDate": "2026-09-30",
            "type": "1",
            "typeName": "圖片",
            "enabled": true
        },.....
    ],
    "msg": ""
}</div></pre>
		</article>
		
		<article>
			<button class="item pink4 small-elevate" @click="createMedia">新增 <code class="round white-text pink10">POST</code> {{url('api/tvMenu/medias')}}</button>
			<code>{{url('api/tvMenu/medias')}}</code>
			
			<pre class="blue-border"><code>Request</code><div>{
    "mediaName": string,
    "uploadFile": file,
    "uploadLink": string,
    "stDate": date [Y-m-d],
    "endDate": date [Y-m-d],
    "type": integer [1:image | 2:video],
    "enabled": boolean
}
</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
    "status": true,
    "data": {
        "id": 22,
        "mediaName": "Test001",
        "mediaUrl": "https://test.co",
        "stDate": null,
        "endDate": null,
        "type": 2,
        "typeName": "影片",
        "enabled": true
    },
    "msg": ""
}</div></pre>
		</article>
		
		<article>
			<button class="item pink4 small-elevate" @click="editMedia">編輯 <code class="round white-text pink10">GET</code> {{url('api/tvMenu/medias/id')}}</button>
			<button class="item pink4 small-elevate" @click="updateMedia">編輯 <code class="round white-text pink10">PUT</code> {{url('api/tvMenu/medias/id')}}</button>
			<code>{{url('api/tvMenu/medias')}}/{id}</code>
			
			<pre class="blue-border"><code>Request GET</code><div>N/A</div></pre>
			<pre class="blue-border"><code>Request PUT</code><div>{
    "mediaName": string
    "stDate": date [Y-m-d],
    "endDate": date [Y-m-d],
    "enabled": boolean
}</div></pre>

			<pre class="pink-border"><code>Response GET|PUT</code><div>{
    "status": true,
    "data": {
        "id": 22,
        "mediaName": "Test001-01",
        "mediaUrl": "https://test.co",
        "stDate": "2026-10-01",
        "endDate": "2026-10-11",
        "type": "2",
        "typeName": "影片",
        "enabled": false
    },
    "msg": ""
}</div></pre>
		</article>
		
		<article>
			<button class="item pink4 small-elevate" @click="deleteMedia">刪除 <code class="round white-text pink10">DELETE</code> {{url('api/tvMenu/medias/id')}}</button>
			<code>{{url('api/tvMenu/medias')}}/{id}</code>
			
			<pre class="blue-border"><code>Request</code><div>N/A</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
    "status": true,
    "data": [],
    "msg": ""
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
				<div class="field label border small">
					<input type="text" x-model="id">
					<label>ID</label>
				</div>
				<div class="field label border fill small max">
					<textarea x-model="mockPayload"></textarea>
					<label class="red-text">Paste JSON here</label>
				</div>
			</nav>
		</article>
		
		<article>
			<button @click="listMenu" class="blue4 small-elevate">列表 <code class="round white-text blue10">GET</code> {{url('api/tvMenu/menus')}}</button>
			<code>{{url('api/tvMenu/menus')}}</code>
			
			<pre class="blue-border"><code>Request</code><div>N/A</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
    "status": true,
    "data": [
        {
            "id": "4",
            "menuName": "八方Menu-1",
            "isDefault": false
        },...
    ]
    "msg": ""
}</div></pre>
		</article>
		
		<article>
			<button class="item blue4 small-elevate" @click="createMenu">新增 <code class="round white-text blue10">POST</code> {{url('api/tvMenu/menus')}}</button>
			<code>{{url('api/tvMenu/menus')}}</code>
			
			<pre class="blue-border"><code>Request</code><div>{
    "menuName": string,
    "isDefault": boolean,
    "medias":[
	    {
		    "id": integer,
		    "duration": integer or 0 for default [default:5],
		    "sort": integer
	    },...
    ]
}
</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
    "status": true,
    "data": [],
    "msg": ""
}</div></pre>
		</article>
		
		<article>
			<button class="item blue4 small-elevate" @click="editMenu">編輯 <code class="round white-text blue10">GET</code> {{url('api/tvMenu/menus/id')}}</button>
			<button class="item blue4 small-elevate" @click="updateMenu">編輯 <code class="round white-text blue10">PUT</code> {{url('api/tvMenu/menus/id')}}</button>
			<code>{{url('api/tvMenu/menus')}}/{id}</code>
			
			<pre class="blue-border"><code>Request GET</code><div>N/A</div></pre>
			<pre class="pink-border"><code>Response GET</code><div>{
    "status": true,
    "data": {
        "id": "14",
        "menuName": "Default-1",
        "isDefault": true,
        "medias": [
            {
                "id": 15,
                "duration": 5,
                "sort": 1
            },.....
        ]
    }
    "msg": ""
}</div></pre>

		<pre class="blue-border"><code>Request PUT</code><div>{
    "menuName": "Default-1-2",
    "isDefault": false,
    "medias":[
        {
            "id": 3,
            "duration": 10,
            "sort": 1
        },.....
    ]
}</div></pre>

			

		<pre class="pink-border"><code>Response PUT</code><div>{
    "status": true,
    "data": [],
    "msg": ""
}</div></pre>
		</article>
		
		<article>
			<button class="item blue4 small-elevate" @click="deleteMenu">刪除 <code class="round white-text blue10">DELETE</code> {{url('api/tvMenu/menus/id')}}</button>
			<code>{{url('api/tvMenu/menus')}}/{id}</code>
			
			<pre class="blue-border"><code>Request</code><div>N/A</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
    "status": true,
    "data": [],
    "msg": ""
}</div></pre>
		</article>
	</details>
	
	
	
<!----- Store ------------------------------------------------------------->
	<div class="space"></div>
	<details x-data="storeMenuForm">
		<summary>
			<button class="green">
				<span>門市 TVMenu</span>
				<i>expand_more</i>
			</button>
		</summary>
		
		<article>
			<nav class="wrap">
				<div class="field label border small">
					<input type="text" x-model="id">
					<label>ID</label>
				</div>
				<div class="field label border small">
					<input type="text" x-model="brand">
					<label>Brand</label>
				</div>
				<div class="field label border fill small max">
					<textarea x-model="mockPayload"></textarea>
					<label class="red-text">Paste JSON here</label>
				</div>
			</nav>
		</article>
		
		<article>
			<button @click="listStore" class="green4 small-elevate">列表 <code class="round white-text green10">GET</code> {{url('api/tvMenu/storeMenus/brand')}}</button>
			<code>{{url('api/tvMenu/storeMenus')}}/{brand?}</code>
			
			<pre class="blue-border"><code>Request</code><div>
	brand: string [8WAY|BUYGOOD] or empty for all
</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
    "status": true,
    "data": [
        "0": {
            "brand": "8WAY",
            "id": "1000001",
            "name": "台北延平南店",
            "tvCount": 0
        },.....
    ]
    "msg": ""
}</div></pre>
		</article>
		
		
		<article>
			<button class="item green4 small-elevate" @click="editMedia">編輯 <code class="round white-text green10">GET</code> {{url('api/tvMenu/storeMenus/storeId')}}</button>
			<button class="item green4 small-elevate" @click="updateMedia">新增 | 編輯<code class="round white-text green10">POST</code> {{url('api/tvMenu/storeMenus/storeId')}}</button>
			<code>{{url('api/tvMenu/storeMenus')}}/storeId</code>
			
			<pre class="blue-border"><code>Request GET</code><div>storeId: string</div></pre>
			<pre class="pink-border"><code>Response GET</code><div>{
    "status": true,
    "data": {
        "brand": "8WAY",
        "id": "1000001",
        "name": "台北延平南店",
        "menus": [
            {
                "tvId": 1,
                "menuId": 4
            },
            {
                "tvId": 1,
                "menuId": 7
            }
        ]
    },
    "msg": ""
}</div></pre>


			<pre class="blue-border"><code>Request POST</code><div>{
    "storeId": "1040009",
    "menus":[
        {
            "tvId": 2,
            "menuId": 5
        },
        {
            "tvId": 3,
            "menuId": 6
        }
    ]
}</div></pre>

			<pre class="pink-border"><code>Response POST</code><div>{
    "status": true,
    "data": [],
    "msg": ""
}</div></pre>
		</article>
		
		<article>
			<button class="item green4 small-elevate" @click="deleteMedia">刪除 <code class="round white-text green10">DELETE</code> \api\tvMenu\storeMenus\{id}</button>
			
			<pre class="blue-border"><code>Request</code><div>N/A</div></pre>
			<pre class="pink-border"><code>Response</code><div>{
    "status": true,
    "data": [],
    "msg": ""
}</div></pre>
		</article>
	</details>
</section>
@endsection
