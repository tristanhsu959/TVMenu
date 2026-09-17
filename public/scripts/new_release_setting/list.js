/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.store('releaseSetting', {
		tabIndex: Alpine.$persist(0),
	});
	
	Alpine.data('releaseSettingList', (list, brands) => ({
		settings: list,
		brands: brands,
		activeTab: 0,
		
		init() {
			this.activeTab = Alpine.store('releaseSetting').tabIndex;
			
			if (! this.activeTab)
				this.activeTab = 1;
		},
		confirmDelete(url) {
			Alpine.store('dialog').show('確定要刪除此新品設定?', true, () => this.deleteNewRelease(url));
		},
		
		deleteNewRelease(url) {
			this.$dispatch('show-loading');
			const form = this.$refs.releaseSettingListForm;
            form.action = url;
            form.submit();
		},
    }));
});

