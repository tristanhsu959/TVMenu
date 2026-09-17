/* JS */

document.addEventListener('alpine:init', () => {
	//統計單位顯示
	Alpine.store('saleEvents', {
		showFilter: Alpine.$persist(false),
		filter: '',
	});
	
	Alpine.data('searchEvents', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			Alpine.store('saleEvents').showFilter = false;
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.stDate == '')
				this.errors.add('stDate');
			
			if (this.searchData.stDate && this.searchData.endDate)
			{
				if (new Date(this.searchData.stDate) > new Date(this.searchData.endDate))
				{
					this.errors.add('endDate');
					Alpine.store('toast').notify('結束日期不可小於開始日期');
				}
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
			this.searchData.stDate = this.searchData.today;
			this.searchData.endDate = this.searchData.today;
			this.searchData.areaIds = [];
			this.errors.clear();
		},
    }));
	
	Alpine.data('statisticsMoonFestival', (response) => ({
		store: {...response.store},
		dayRange: {...response.dayRange},
		productList: {...response.productList},
		
		init() {console.log(this.dayRange);
			this.showFilter();
		},
		
		showFilter() {
			Alpine.store('saleEvents').showFilter = this.store.length > 0;
		},
		
		get sortedDates() {
            return Object.keys(this.dayRange).sort((a, b) => b.localeCompare(a)); 
        },
		
		get filterStore() {
			const searchKeyword = Alpine.store('saleEvents').filter.toLowerCase();
			
			const list = Object.values(this.store);
			
			const result = list.filter(store => 
				String(store.areaName || '').toLowerCase().includes(searchKeyword) ||
				String(store.storeId || '').toLowerCase().includes(searchKeyword) ||
				String(store.storeKey || '').toLowerCase().includes(searchKeyword) ||
				String(store.storeName || '').toLowerCase().includes(searchKeyword)
			);
			
			return result;
		},
	
    }));
});

