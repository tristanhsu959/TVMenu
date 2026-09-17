
<header x-data='@json($initData)' class="orange top-nav">
	<nav>
		<header x-show="!$store.menu.active" class="orange">
			<img src="{{ asset('images/logo.png') }}" />
		</header>
		
		<a :href="backUrl" x-show="backUrl" class="button circle transparent">
			<i>arrow_back</i>
		</a>
		<h6 class="max small" x-html="breadcrumb.join('<i>chevron_right</i>')"></h6>
		
		<button class="circle transparent menu" @click="$store.menu.active = !$store.menu.active">
			<i x-show="!$store.menu.active">menu</i>
			<i x-show="$store.menu.active">menu_open</i>
		</button>
		<a :href="homeRoute" x-show="!isHome" class="button circle transparent">
			<i>home</i>
		</a>
		<button class="circle transparent" data-ui="#profile">
			<i>person</i>
		</button>
	</nav>
</header>