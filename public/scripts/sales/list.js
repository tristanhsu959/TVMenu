/* JS */

document.addEventListener('alpine:init', () => {
	//統計單位顯示
	Alpine.store('sales', {
		showAmount: Alpine.$persist(false),
        showFilter: Alpine.$persist(false),
		filter: '',
		
		storeDetail: [],
		
		toggle() {
           this.showAmount = ! this.showAmount;
        }
	});
	
	Alpine.data('searchSales', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			Alpine.store('sales').showFilter = false;
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
			
			if (this.searchData.type == 'store' && this.searchData.storeName == '')
				this.errors.add('storeName');
			
			if (this.searchData.category == '')
				this.errors.add('category');
			
			if (this.searchData.productIds.length == 0)
			{
				this.errors.add('productIds');
				Alpine.store('toast').notify('請勾選欲查詢產品');
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
			this.searchData.storeName = '';
			this.searchData.category = '';
			this.searchData.productIds = [];
			this.errors.clear();
		},
    }));
	
	Alpine.data('statisticsStore', (store, detail) => ({
		store: {...store},
		
		init() { 
		},
		
		get filterStore() {
			const searchKeyword = Alpine.store('sales').filter.toLowerCase();
			
			const list = Object.values(this.store.data);
			
			const result = list.filter(store => 
				String(store.shopId || '').toLowerCase().includes(searchKeyword) ||
				String(store.areaName || '').toLowerCase().includes(searchKeyword) ||
				String(store.storeKey || '').toLowerCase().includes(searchKeyword) ||
				String(store.storeName || '').toLowerCase().includes(searchKeyword)
			);
			
			return result;
		},
	
    }));
	
	Alpine.data('storeDetail', () => ({
		detail: {
			storeKey: '',
			storeName: '',
			header: {},
			products: {},
		},
		
		init() { 
		},
		
		openDetail(eventData) {
			this.detail.storeKey 	= eventData.id;
			this.detail.storeName 	= eventData.name;
			this.detail.header 		= eventData.details.header;
			this.detail.products 	= eventData.details.data;
				
			ui('#salesDetail');
		},
	
    }));
});

