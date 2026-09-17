/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.store('salesProductSetting', {
		tabIndex: Alpine.$persist(0),
		filterCat: {
			1: '',
			2: '',
		},
	});
	
	Alpine.data('salesSettingList', (response) => ({
		settings: response.list,
		options: response.options,
		activeTab: 0,
		
		init() {
			this.activeTab = Alpine.store('salesProductSetting').tabIndex;
			
			if (! this.activeTab)
				this.activeTab = 1;
		},
		
		//filter
		filterSettings(brandId) {
			const searchCat	= Alpine.store('salesProductSetting').filterCat[brandId].toLowerCase();
			const list 		= Object.values(this.settings[brandId]);
			
			const result = list.filter(setting => 
				String(setting.category || '').toLowerCase().includes(searchCat)
			);
			
			return result;
		},
    }));
});

