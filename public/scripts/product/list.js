/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.store('productSetting', {
		tabIndex: Alpine.$persist(0),
		filterCat: {
			1: '',
			2: '',
		},
	});
	
	Alpine.data('productList', (response) => ({
		products: response.list,
		brands: response.options.brands,
		categories: response.options.categories,
		activeTab: 0,
		
		init() {
			this.activeTab = Alpine.store('productSetting').tabIndex;
			
			if (! this.activeTab)
				this.activeTab = 1;
		},
		confirmDelete(url) {
			Alpine.store('dialog').show('確定要刪除此產品?', true, () => this.deleteProduct(url));
		},
		
		deleteProduct(url) {
			this.$dispatch('show-loading');
			const form = this.$refs.productListForm;
            form.action = url;
            form.submit();
		},
		
		//filter
		filterProducts(brandId) {
			const searchCat	= Alpine.store('productSetting').filterCat[brandId].toLowerCase();
			const list 		= Object.values(this.products[brandId]);
			
			const result = list.filter(product => 
				String(product.categoryName || '').toLowerCase().includes(searchCat)
			);
			
			return result;
		},
    }));
});

