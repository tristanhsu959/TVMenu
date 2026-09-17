/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('search', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			if (this.searchData.stDate == '')
				this.searchData.stDate = this.searchData.today;
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.type == 'dayOff' && this.searchData.stDate == '')
				this.errors.add('stDate');
			
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
			this.searchData.type = 'info';
			this.searchData.areaIds = [];
			this.searchData.stDate = this.searchData.today;
			this.errors.clear();
		},
    }));
	
	Alpine.store('merchantInfo', {
		filter: '',
	});
	
	Alpine.data('storeInfo', (data) => ({
		statistics: {...data},
		
		init() { 
		},
		
		get filterStore() {
			const searchKeyword = Alpine.store('merchantInfo').filter.toLowerCase();
			
			const list = this.statistics?.info?.store || [];
			
			const result = list.filter(store => 
				String(store.areaName || '').toLowerCase().includes(searchKeyword) ||
				String(store.posId || '').toLowerCase().includes(searchKeyword) ||
				String(store.storeKey || '').toLowerCase().includes(searchKeyword) ||
				String(store.storeName || '').toLowerCase().includes(searchKeyword) ||
				String(store.vatNumber || '').toLowerCase().includes(searchKeyword) ||
				String(store.areaManager || '').toLowerCase().includes(searchKeyword)
			);
			
			return result;
		},
    }));
	
	Alpine.data('storeDayoff', (data) => ({
		statistics: {...data},
		activeAreaId: 0,
		
		init() { 
			this.activeAreaId = 0;
		},
    }));
});

