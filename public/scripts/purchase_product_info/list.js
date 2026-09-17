/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchProductInfo', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			
		},
		
		search() {
			this.errors.clear();
			
			const pattern = /^[\d]{4}$/;
			console.log(this.searchData.shortCode);
			if (! Helper.isEmpty(this.searchData.shortCode) && ! pattern.test(this.searchData.shortCode))
			{
				this.errors.add('shortCode');
				Alpine.store('toast').notify('產品簡碼格式錯誤');
			}
			
			if (this.errors.size == 0)
			{
				this.$store.app.isLoading = true;
				
				setTimeout(() => {
					ui('#searchPanel');
					this.$el.submit();
				}, 50);
			}
			else
				return false;
		},
		
		
		
		resetSearch() {
			this.searchData.type = Object.keys(this.options.type)[0];
			this.searchData.factoryIds = [];
			this.searchData.status = 'all';
			this.searchData.shortCode = '';
			this.searchData.productName = '';
			this.errors.clear();
		},
    }));
	
	Alpine.data('statisticsInfo', (statistics) => ({
		info: {...statistics},
		activeTab: '',
		
		init() {
			if (this.activeTab == '')
				this.activeTab = 'tab-product';
		},
		
		isActiveTab(tabName) {
			return this.activeTab == tabName;
		},
		
    }));
});

