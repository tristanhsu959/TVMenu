@use('App\Libraries\HelperLib')

<!DOCTYPE html>
<html lang="en" translate="no">
	<head>
		<meta charset="UTF-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title>八方雲集{{ empty(env('APP_ENV_HEAD')) ? '': '-' . env('APP_ENV_HEAD')}}</title>
		
		<link rel="icon" type="image/x-icon" href="{{ asset('images/favicon.ico') }}">
		
		<!-- Styles & Font -->
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet" />
		<link href="https://fonts.googleapis.com/css?family=Roboto&display=swap" rel="stylesheet" />
		<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400..900&display=swap" rel="stylesheet">
		<link href="https://fonts.googleapis.com/css2?family=Poiret+One&display=swap" rel="stylesheet">
		<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet" />
		<link href="https://cdn.jsdelivr.net/npm/beercss@4.0.7/dist/cdn/beer.min.css" rel="stylesheet">
		<link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet" >
		<link href="{{ HelperLib::versionAsset('styles/include.css') }}" rel="stylesheet" />
		<link href="{{ HelperLib::versionAsset('styles/app.css') }}" rel="stylesheet" />
		
		<!-- Scripts -->
		<script type="module" src="https://cdn.jsdelivr.net/npm/beercss@4.0.7/dist/cdn/beer.min.js" defer></script>
		<script type="module" src="https://cdn.jsdelivr.net/npm/material-dynamic-colors@1.1.4/dist/cdn/material-dynamic-colors.min.js" defer></script>
		<script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>
		<script src="{{ HelperLib::versionAsset('scripts/util.js') }}" defer></script>
		<script src="{{ HelperLib::versionAsset('scripts/helper.js') }}" defer></script>
		<script src="{{ HelperLib::versionAsset('scripts/app.js') }}" defer></script>
		<script src="{{ HelperLib::versionAsset('scripts/forget_password.js') }}" defer></script>
		@vite(['resources/js/app.js'])
	</head>

	<body x-cloak>
		<main class="responsive forget-password">
			<div x-data="setPassword(@js($viewModel->responseData()))" class="content-wrapper">
				<form :action="formData.formAction" method="post" class="row" novalidate @submit.prevent="validate()" >
					@csrf
					<input type="hidden" name="id" :value="formData.id" />
					<input type="hidden" name="account" :value="formData.account" />
					<input type="hidden" name="name" :value="formData.name" />
					<div class="content-left">
						<img src="{{ asset('images/logo.svg') }}" />
						<span class="copyright">Bafang<i>&copy;</i>2025</span>
					</div>

					<div class="divider"> </div>

					<div class="content-right">
						<div class="header">
							<span class="title"><i class="fill">encrypted</i>密碼設定</span>
							<h6 class="purple-text">設定密碼後，請重新登入</h6>
						</div>
						
						<div>
							<b x-text="formData.name"></b>
							<span x-text="`(${formData.account})`"></span>
						</div>
						
						<div class="field label border field-light-green suffix" :class="Helper.hasError(errors, 'password')">
							<input x-model="formData.password" :type="showPassword ? 'text':'password'" name="password" maxlength="20" @input="errors.delete('password')">
							<label>新密碼</label>
							<span class="red-text small-text">英文+數字六個字元以上</span>
							<i class="btn-icon">
								<button type="button" class="large square" @click="showPassword = !showPassword">
									<i x-show="!showPassword">visibility</i>
									<i x-show="showPassword">visibility_off</i>
								</button>
							</i>
						</div>
						<div class="field label border field-light-green suffix" :class="Helper.hasError(errors, 'confirmPassword')">
							<input x-model="formData.confirmPassword" :type="showConfirmPassword ? 'text':'password'" name="confirmPassword" maxlength="20" @input="errors.delete('confirmPassword')">
							<label>確認密碼</label>
							<span class="red-text small-text">再輸入一次新密碼</span>
							<i class="btn-icon">
								<button type="button" class="large square" @click="showConfirmPassword = !showConfirmPassword">
									<i x-show="!showConfirmPassword">visibility</i>
									<i x-show="showConfirmPassword">visibility_off</i>
								</button>
							</i>
						</div>
						
						<nav class="group split">
							<button type="submit" class="green left-round max">
								<span>儲存</span>
							</button>
							<button type="button" class="right-round square btn-cancel" @click="reset()">
								<i>close</i>
							</button>
						</nav>
					</div>
				</form>
			</div>
		</main>
		
		<x-toast :msg="$viewModel->msg()"/>
	</body>
</html>

