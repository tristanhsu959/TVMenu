
<div x-data="{ msg: @js($msg) }" x-init="Alpine.store('toast').initialize(msg)" class="snackbar" id="notifyMsg">
	<div class="max message"></div>
	<a class="inverse-primary-text btn-close"><i>close</i></a>
</div>