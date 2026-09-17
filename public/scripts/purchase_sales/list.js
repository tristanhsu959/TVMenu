/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('search', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			this.$refs.searchDate.max = this.searchData.today;
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.date == '')
				this.errors.add('date');
			
			if (this.searchData.type == 'area' && this.searchData.areaId == 0)
				this.errors.add('areaId');
			
			if (this.searchData.type == 'storeName' && this.searchData.storeName == '')
				this.errors.add('storeName');
				
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
			this.searchData.date = this.searchData.today;
			this.searchData.storeName = '';
			this.errors.clear();
		},
    }));
	
	Alpine.data('storeList', (statistics) => ({
		//searchData: {...searchData},
		statistics: {...statistics},
		formData : {
			storeId: 0,
			date: '',
		},
		listHeader: '',
		
		init() { 
			if (this.statistics.searchType == 'all')
				this.listHeader = '所有門店';
			else if (this.statistics.searchType == 'area')
				this.listHeader = '區域：' + statistics.searchAreaName;
			else if (this.statistics.searchType == 'storeName')
				this.listHeader = '店名：' + statistics.searchStoreName;
			
			this.formData.date = this.statistics.searchDate;
		},
		
		getDetail(storeId) {
			this.formData.storeId = storeId;
			
			this.$nextTick(() => {
				this.$refs.detailForm.submit();
			});
		},
    }));
});

