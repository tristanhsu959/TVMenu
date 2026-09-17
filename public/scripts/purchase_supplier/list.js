/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchSupplier', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			
		},
		
		get showAreaOptions() {
			return (Object.keys(this.options.areaList).length > 0);
		},
		get showWhereOptions() {
			return this.searchData.type == 'total';
		},
		get showProductName() {
			return (this.searchData.type == 'total' && this.searchData.where == 'keyword');
		},
		get showCategory() {
			return (this.searchData.type == 'total' && this.searchData.where == 'category');
		},
		get showProduct() {
			return (catId) => (this.searchData.type == 'total' && this.searchData.where == 'category' 
							&& this.searchData.category == catId);
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.stDate == '')
				this.errors.add('stDate');
			if (this.searchData.endDate == '')
				this.errors.add('endDate');
			
			if (this.searchData.stDate && this.searchData.endDate)
			{
				if (new Date(this.searchData.stDate) > new Date(this.searchData.endDate))
				{
					this.errors.add('endDate');
					Alpine.store('toast').notify('結束日期不可小於開始日期');
				}
			}
			
			if (this.searchData.type == 'total' && this.searchData.where == 'keyword' && this.searchData.keyword == '')
				this.errors.add('keyword');
			
			if (this.searchData.type == 'total' && this.searchData.where == 'category' && this.searchData.productIds.length == 0)
			{
				this.errors.add('productIds');
				Alpine.store('toast').notify('請勾選產品');
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
			this.searchData.stDate = this.searchData.tomorrow;
			this.searchData.endDate = this.searchData.tomorrow;
			this.searchData.areaIds = [];
			this.searchData.where = Object.keys(this.options.where)[0];
			this.searchData.keyword = '';
			this.searchData.category = '';
			this.searchData.productIds = [];
			this.errors.clear();
		},
    }));
	
	/*呼叫function要多處理變數*/
	Alpine.data('statisticsStore', (data) => ({
		statistics: {...data},
		activeProduct: '',
		activeProductName: '',
		openTabMenu: false,
		fixedTabsCount: 4,
		
		init() { 
			/* Set active tab */
			const keys = Object.keys(this.statistics.productList);
			if (keys.length > 0)
				this.activeProduct = keys[0];
		},
		
		activeTab(productId) {
			return (this.activeProduct == productId) ? 'active' : '';
		},
		setActiveTab(productId, productName){
			this.activeProduct = productId;
			this.activeProductName = productName;
		},
		activeMoreTab(){
			const list = Object.keys(this.statistics.productList).slice(this.fixedTabsCount);
			
			return list.includes(this.activeProduct) ? 'active' : '';
		},
		
		get fixedTabs() {
			//st,end
			return Object.fromEntries(Object.entries(this.statistics.productList).slice(0, this.fixedTabsCount));
		},
		get showMoreTabs() {
			return Object.keys(this.statistics.productList).length > this.fixedTabsCount;
		},
		get moreTabs() {
			return Object.fromEntries(Object.entries(this.statistics.productList).slice(this.fixedTabsCount));
		},
		get moreTabName(){
			return (this.activeMoreTab() == 'active') ? this.activeProductName : '更多';
		},
		
		/* get filterStore() {
			const searchKeyword = Alpine.store('shipmentStore').filter.toLowerCase();
			
			const list = Object.values(this.statistics.storeList);
			
			const result = list.filter(store => 
				String(store.posId || '').toLowerCase().includes(searchKeyword) ||
				String(store.areaName || '').toLowerCase().includes(searchKeyword) ||
				String(store.storeNo || '').toLowerCase().includes(searchKeyword) ||
				String(store.storeName || '').toLowerCase().includes(searchKeyword)
			);
			
			return result;
		}, */
	
    }));
	
	
});

