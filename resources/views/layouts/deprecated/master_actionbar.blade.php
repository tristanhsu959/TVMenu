
<nav class="page navbar">
	@hasSection('navHome')
	<span>@yield('navHome')</span>
	@else
	<span class="navbar-head">
		@yield('navBack')
		<span class="material-symbols-outlined filled-icon">bubble_chart</span>
		@yield('navHead')
	</span>
	@endif
	<span class="navbar-action">
		@yield('navAction')
		
		@sectionMissing('navHome')
		<a class="btn btn-home" href="{{ route('home') }}" role="button">
			<span class="material-symbols-outlined">home</span>
		</a>
		@endif
		<a class="btn btn-profile" data-bs-toggle="offcanvas" href="#popup-profile" role="button" aria-controls="popup-profile">
			<span class="material-symbols-outlined">person</span>
		</a>
	</span>
</nav>
<div id="loading" class="loading-wrapper">
	<div class="loading-bar"></div>
</div>
				

