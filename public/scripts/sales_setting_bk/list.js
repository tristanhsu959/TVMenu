/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.store('salesSetting', {
		tabIndex: Alpine.$persist(1),
	});
	
	Alpine.data('salesSettingList', (settings, options) => ({
		settings: {...settings},
		brands: options.brands,
		activeTab: 0,
		
		init() {
			this.activeTab = Alpine.store('salesSetting').tabIndex;
			
			if (! this.activeTab)
				this.activeTab = 1;
		},
		
		confirmDelete(url) {
			Alpine.store('dialog').show('確定要刪除此銷售設定?', true, () => this.deleteSetting(url));
		},
		
		deleteSetting(url) {
			this.$dispatch('show-loading');
			const form = this.$refs.salesSettingListForm;
            form.action = url;
            form.submit();
		},
    }));
});

