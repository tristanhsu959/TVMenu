@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('styles')
    <link href="{{ HelperLib::versionAsset('styles/home/main.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src=""></script>
@endpush

@section('content')
	<section class="content-wrapper">
		<h1 class="red-text">TV Menu</h1>
	</section>
@endsection
