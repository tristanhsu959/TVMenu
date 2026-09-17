@extends('layouts.app')
@use('App\Libraries\HelperLib')

@push('scripts')
    <script src="{{ HelperLib::versionAsset('scripts/signin.js') }}" defer></script>
@endpush

@section('content')

<div x-data="login(@js($viewModel->responseData()))" class="content-wrapper">
	<form :action="formData.formAction" method="post" class="row" novalidate @submit.prevent="validate()" >
		@csrf
		
		<div class="content-left">
			<img src="{{ asset('images/logo.svg') }}"  @dblclick="showForgetPasswordButton = !showForgetPasswordButton"/>
			<span class="copyright">Bafang<i>&copy;</i>2025</span>
		</div>

		<!--div class="divider vertical"></div-->
		
		<div class="content-right">
			<div x-show="!isForgetPassword" class="header">
				<img src="{{ asset('images/microsoft_logo.png') }}" @dblclick="showOidcButton = !showOidcButton" />
				<span class="title">Sign In</span>
				<h6>登入至DASHBOARD</h6>
			</div>
			<div x-show="isForgetPassword" class="header">
				<img src="{{ asset('images/microsoft_logo.png') }}" />
				<span class="title">Forgot Password</span>
				<h6>忘記密碼？</h6>
			</div>
			
			<div class="field label border" :class="Helper.hasError(errors, 'account')">
				<input x-model="formData.account" type="text" name="account" maxlength="20" @input="errors.delete('account')">
				<label>Account</label>
				<!--span class="domain">@8way.com.tw</span-->
			</div>
			<div x-show="!isForgetPassword" class="field label border" :class="Helper.hasError(errors, 'password')">
				<input x-model="formData.password" type="password" name="password" maxlength="20" @input="errors.delete('password')">
				<label>Password</label>
			</div>
			<p x-show="isForgetPassword" class="red-text">系統會發送密碼設定連結至此帳號所屬信箱，請由該連結進入密碼重設頁面</p>
			<div class="field label border captcha">
				<input type="text" name="captcha" maxlength="10">
				<label>Captcha</label>
			</div>
			<nav class="group split">
				<button x-show="!isForgetPassword"  type="submit" class="btn-red left-round max">
					<span>Sign In</span>
				</button>
				<button x-show="isForgetPassword"  type="submit" class="btn-light-blue left-round max">
					<span>送出</span>
				</button>
				<button type="button" class="right-round square btn-cancel" @click="reset()">
					<i>close</i>
				</button>
			</nav>
			<nav>
				<div class="max"></div>
				<button type="button" class="right pink-text transparent" @click="isForgetPassword = !isForgetPassword">
					<i x-show="isForgetPassword">password_2_off</i>
					<i x-show="!isForgetPassword">password_2</i>
					<span>忘記密碼</span>
				</button>
			</nav>
		</div>
	</form>
	
	<a x-show="showOidcButton" :href="formData.oethRedirect" class="button extend circle light-blue10 btn-oidc">
		<!--i>fingerprint</i-->
		<img class="responsive" src="{{ asset('images/oeth.png') }}">
		<span>OETH Auth</span>
	</a>
</div>

@endsection