/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchReport', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			this.initDateInput();
		},
		
		changeDateInput(){
			this.initDateInput();
			this.searchData.stDate = '';
			this.searchData.endDate = '';
		},
		
		initDateInput(){
			if (this.searchData.range == 'year')
			{
				this.$refs.searchStDate.disabled  = true;
				this.$refs.searchEndDate.disabled  = true;
			}
			else
			{
				this.$refs.searchStDate.disabled  = false;
				this.$refs.searchEndDate.disabled  = false;
				this.$refs.searchStDate.type = this.searchData.range;
				this.$refs.searchEndDate.type = this.searchData.range;
			}
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.range != 'year' && this.searchData.stDate == '')
				this.errors.add('stDate');
			if (this.searchData.range != 'year' && this.searchData.endDate == '')
				this.errors.add('endDate');
			
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
			this.searchData.type = Object.keys(this.options.type)[0];
			this.searchData.range = Object.keys(this.options.range)[0];
			this.changeDateInput();
			this.errors.clear();
		},
    }));
	
	//Factory
	Alpine.data('statisticsFactory', (data) => ({
		statistics: {...data},
		activeProduct: 'qty',
		
		init(data) { 
			this.activeProduct = 'qty';
			this.$nextTick(() => ui('#page-qty'));
		},
    }));
	
	//Store
	Alpine.data('statisticsStore', (data) => ({
		statistics: {...data},
		activeProduct: '',
		
		init() { 
			const keys = Object.keys(this.statistics.sheets);
			if (keys.length > 0)
				this.activeProduct = keys[0];
			
			this.$nextTick(() => ui(`#page-${this.activeProduct}`));
		},
    }));
});

