/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('searchProduct', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		minDate: '',
		
		init() {
			let minDate = '';
			
			if (this.searchData.releaseId > 0)
				minDate = this.options.newReleaseProducts[this.searchData.releaseId].saleDate;
			
			this.minDate = minDate;
			/* this.$refs.searchStDate.min = minDate;
			this.$refs.searchEndDate.min = minDate; */
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.releaseId == 0)
				this.errors.add('releaseId');
			
			if (this.searchData.stDate == '')
				this.errors.add('stDate');
			
			if (this.searchData.stDate && this.searchData.endDate)
				if (new Date(this.searchData.stDate) > new Date(this.searchData.endDate))
				{
					this.errors.add('endDate');
					Alpine.store('toast').notify('結束日期不可小於開始日期');
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
		
		initSearchStDate(releaseId) {
			let minDate = '';
			
			if (releaseId > 0)
				minDate = this.options.newReleaseProducts[releaseId].saleDate;
		
			/* this.$refs.searchStDate.min = minDate;
			this.$refs.searchEndDate.min = minDate; */
			this.minDate = minDate;
			this.searchData.stDate = minDate; //用$refs...value無法連動
			this.searchData.endDate = ''; //reset
			
			this.errors.delete('releaseId')
			this.errors.delete('stDate');
		},
		
		get setMinDate() {
			return this.minDate;
		},
		
		resetSearch() {
			this.searchData.releaseId = '';
			this.searchData.stDate = '';
			this.searchData.endDate = '';
			this.searchData.areaIds = [];
			this.errors.clear();
		},
    }));
	
});

