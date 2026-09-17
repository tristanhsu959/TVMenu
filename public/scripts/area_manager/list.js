/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('search', (searchData) => ({
		searchData: {...searchData.search},
		options: {...searchData.options},
		errors: new Set(),
		
		init() {
		},
		
		search() {
			this.errors.clear();
			
			if (this.searchData.areaIds.length <= 0)
			{
				this.errors.add('areaIds');
				Alpine.store('toast').notify('請勾選查詢區域');
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
			this.searchData.areaIds = [];
			this.errors.clear();
		},
    }));
	
	Alpine.data('list', (response) => ({
		response: {...response},
		isUpdate: false,
		uploadFileName: '',
		errors: new Set(),
		
		init() {
		},
		
		get showUpdateForm(){
			return this.isUpdate;
		},
		
		validateFile(event) {
			console.log(1);
			
			const file = event.target.files[0];
			
			if (! file)
			{
				this.errors.clear();
				return;
			}

			// 定義 .xlsx 的標準 MIME 類型
			const xlsxType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
			
			if (file.type === xlsxType) 
				return true;
			else 
			{
				Alpine.store('toast').notify('上傳檔案格式須為Excel');
				event.target.value = ''; 
				this.uploadFileName = '';
				this.errors.clear();
			}
		},
		
		clearFileInput(){
			this.errors.clear();
			this.$refs.fileInput.value = '';
			this.uploadFileName = '';
			ui(this.$refs.fileInput); //這樣才會refresh ui
			
			console.log(this.errors);
		},
		
		validateUpdate(){
			
			if (this.$refs.fileInput.value == '')
				this.errors.add('uploadFile');
			
			if (this.errors.size == 0)
			{
				this.$store.app.isLoading = true;
				setTimeout(() => {
					ui('#updateForm');
					this.$el.submit();
				}, 50);
			}
			else
				return false;
				
		},
		
    }));
});

