/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchReport', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
			
		},
		
		get showBrandList(){
			return (this.searchData.type == 'performance');
		},
		get showOpCenterList(){
			return Object.keys(this.options.opCenterList).length > 0;
		},
		get showAreaList(){
			return (Object.keys(this.options.areaList).length > 0 && (this.searchData.type == 'performance' || this.searchData.type == 'extraOrder'));
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
			this.searchData.stDate = this.searchData.today;
			this.searchData.endDate = this.searchData.today;
			this.searchData.opCenterIds = []; 
			this.searchData.areaIds = []; 
			this.errors.clear();
		},
    }));
	
	//營運概況
	Alpine.data('statisticsPerformance', (data) => ({
		statistics: {...data},
		activeSheet: '',
		
		init() { 
			const keys = Object.keys(this.statistics.report.sheets);
			
			if (keys.length > 0)
				this.activeSheet = keys[0];
			
			this.$nextTick(() => ui(`#page-${this.activeSheet}`));
		},
    }));
	
	
	/* 員購公關 */
	Alpine.data('employeePrData', (statistics) => ({
		employeeData: {...statistics.employee},
		prData: {...statistics.pr},
		
		init() {
		},
		
		orderHead(dateString){
			return (dateString.length == 10) ? 'order-header' : '';
		},
    }));
	
	/* 追加 */
	Alpine.data('extraData', (statistics) => ({
		extraOrder: {...statistics.extraOrder},
		
		init() {
		},
    }));
});

