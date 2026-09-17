/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('statistics', (statistics) => ({
		statistics: {...statistics},
		
		init() {console.log(this.statistics);
			let searchDate = this.statistics.searchDate;
			
			objDate = new Date(searchDate);
			this.statistics.searchYear = objDate.getFullYear();
			this.statistics.searchMonth = (objDate.getMonth() + 1).toString().padStart(2, '0');
			this.statistics.searchDay = objDate.getDate().toString();
		},
    }));
});

