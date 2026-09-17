/* JS */

document.addEventListener('alpine:init', () => {
    Alpine.data('roleList', (list, options) => ({
		list: list,
		options: {...options},
		
		confirmDelete(url) {
			Alpine.store('dialog').show('確定要刪除此身份?', true, () => this.deleteRole(url));
		},
		
		deleteRole(url) {
			this.$dispatch('show-loading');
			const form = this.$refs.roleListForm;
            form.action = url;
            form.submit();
		}
    }));
});
