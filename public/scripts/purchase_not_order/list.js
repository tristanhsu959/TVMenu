/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchNotOrder', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.stDate == '')
				this.errors.add('stDate');
			/* if (this.searchData.endDate == '')
				this.errors.add('endDate'); */
			
			/* if (this.searchData.stDate && this.searchData.endDate)
			{
				if (new Date(this.searchData.stDate) > new Date(this.searchData.endDate))
				{
					this.errors.add('endDate');
					Alpine.store('toast').notify('結束日期不可小於開始日期');
				}
			} */
			
			if (this.searchData.type == 'product' && 
					this.searchData.by == 'keyword' && this.searchData.keyword == '')
				this.errors.add('keyword');
			
			if (this.searchData.type == 'product' &&
					this.searchData.by == 'category' && this.searchData.shortCodes.length == 0)
			{
				this.errors.add('shortCodes');
				Alpine.store('toast').notify('請勾選產品');
			}
			
			if (this.errors.size == 0)
			{
				this.$store.app.isLoading = true;
				
				if (this.searchData.by == 'keyword')
				{
					this.searchData.category = '';
					this.searchData.shortCodes = [];
				}
				else
					this.searchData.keyword = '';
				
				setTimeout(() => {
					ui('#searchPanel');
					this.$el.submit();
				}, 50);
			}
			else
				return false;
		},
		
		
		
		resetSearch() {
			this.searchData.type	= Object.keys(this.options.type)[0];
			this.searchData.calc 	= Object.keys(this.options.calc)[0];
			this.searchData.by 		= Object.keys(this.options.by)[0];
			this.searchData.stDate 	= this.searchData.tomorrow;
			//this.searchData.endDate = this.searchData.tomorrow;
			this.searchData.areaIds = [];
			this.searchData.keyword = '';
			this.searchData.category= '';
			this.searchData.shortCodes = [];
			this.errors.clear();
		},
    }));
	
	//Store cache
	Alpine.store('purchaseNotOrder', {
		filter: '',
	});
	
	/*呼叫function要多處理變數*/
	Alpine.data('statisticsNotOrder', (data) => ({
		statistics: {...data},
		
		init() {console.log(this.statistics);
		},
		
		get filterStore() {
			const searchKeyword = Alpine.store('purchaseNotOrder').filter.toLowerCase();
			
			const list = Object.values(this.statistics.data.store);
			
			const result = list.filter(store => 
				String(store.areaName || '').toLowerCase().includes(searchKeyword) ||
				String(store.posId || '').toLowerCase().includes(searchKeyword) ||
				String(store.storeNo || '').toLowerCase().includes(searchKeyword) ||
				String(store.storeName || '').toLowerCase().includes(searchKeyword)
			);
			
			return result;
		},
	
    }));
});

