/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.store('ezorderPos', {
		showFilter: Alpine.$persist(false),
		filter: '',
	});
	
	Alpine.data('search', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			if (this.searchData.by == 'store')
				Alpine.store('ezorderPos').showFilter = true;
		},
		
		get showAreaOptions() {
			return (Object.keys(this.options.areaList).length > 0);
		},
		get showStoreName() {
			return (this.searchData.by == 'store');
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
			this.searchData.type = 'ez';
			this.searchData.by = 'store';
			this.searchData.stDate = this.searchData.today;
			this.searchData.endDate = this.searchData.today;
			this.searchData.areaIds = [];
			this.searchData.storeName = '';
			this.errors.clear();
		},
    }));
	
	Alpine.data('statisticsData', (statistics) => ({
		header: {...statistics.header},
		data: {...statistics.data},
		
		init() {
			
		},
		
		get filterStore() {
			const searchKeyword = Alpine.store('ezorderPos').filter.toLowerCase();
			
			const list = Object.values(this.data);
			
			const result = list.filter(store => 
				String(store[0] || '').toLowerCase().includes(searchKeyword) ||
				String(store[1] || '').toLowerCase().includes(searchKeyword) ||
				String(store[2] || '').toLowerCase().includes(searchKeyword)
			);
			
			return result;
		},
	}));
});

