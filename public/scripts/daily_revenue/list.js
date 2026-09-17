/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('search', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			this.searchData.stDate = (this.searchData.stDate == '') ? this.setDefaultDate : this.searchData.stDate;
			this.searchData.endDate = (this.searchData.endDate == '') ? this.setDefaultDate : this.searchData.endDate;
		},
		
		changeType() {
			this.searchData.stDate = this.setDefaultDate;
			this.searchData.endDate = this.setDefaultDate;
		},
		
		get showCalc() {
			return (this.searchData.type == 'day');
		},
		get dateType() {
			if (this.searchData.type == 'aov')
				return 'month';
			else
				return 'date';
		},
		get setDefaultDate() {
			if (this.searchData.type == 'aov')
				return this.searchData.thisMonth;
			else
				return this.searchData.today;
		},
		get showEndDate() {
			return (this.searchData.type != 'day');
		},
		get showStoreName() {
			return (this.searchData.type != 'aov');
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
			this.searchData.type = 'day';
			this.searchData.calc = [];
			this.searchData.stDate = this.setDefaultDate;
			this.searchData.endDate = this.setDefaultDate;
			this.searchData.storeType = this.searchData.defaultStoreTypes;
			this.searchData.areaIds = [];
			this.searchData.storeName = '';
			this.errors.clear();
		},
    }));
	
	Alpine.data('aovStatistics', (statistics) => ({
		statisticsData: {...statistics.data},
		expansion: new Set(),
		
		init() {
			this.expansion.clear();;
		},
		
		addExpansion(typeKey, month) {
			const key = `${typeKey}-${month}`;
			
			if (this.expansion.has(key))
				this.expansion.delete(key);
			else
				this.expansion.add(key);
		},
		
		showDetail(typeKey, month) {
			const key = `${typeKey}-${month}`;
			
			return this.expansion.has(key);
		},
		
    }));
	
	/* Hourly revenue */
	Alpine.data('storeDetail', () => ({
		detail: {
			storeKey: '',
			storeName: '',
			hourlyData: {},
		},
		
		init() { 
		},
		
		openDetail(eventData) {
			this.detail.storeKey 	= eventData.id;
			this.detail.storeName 	= eventData.name;
			this.detail.hourlyData 	= eventData.details;
				
			ui('#hourlyData');
		},
	
    }));
});

