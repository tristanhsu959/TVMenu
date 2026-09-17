<!-- Dialog -->

<dialog x-data id="modal-dialog">
	<h5 :class="$store.dialog.color">
		<i x-text="$store.dialog.icon"></i>
		<span x-text="$store.dialog.title"></span>
	</h5>
	<div class="message padding" x-text="$store.dialog.message"></div>
	<nav class="right-align no-space">
		<button x-show="!$store.dialog.isConfirm" class="border primary" data-ui="#modal-dialog">確認</button>
		<button x-show="$store.dialog.isConfirm" class="transparent link" data-ui="#modal-dialog">取消</button>
		<button x-show="$store.dialog.isConfirm" @click="$store.dialog.confirm()" class="border primary">確認</button>
	</nav>
</dialog>
