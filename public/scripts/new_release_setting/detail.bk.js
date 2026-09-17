/* Role Create JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('releaseSettingForm', (formData, options) => ({
		formData: formData,
		options: options,
		products: [],
		errors: new Set(),
		
		init() {
			if (this.formData.brandId)
				this.updateProducts();
		},
		
		updateProducts() {
			const selectedBrandId = this.formData.brandId;
			this.products = this.options.products[selectedBrandId] || [];
		},
		
		validate() {
			this.errors.clear();
			
			if (this.formData.brandId == 0)
				this.errors.add('brandId');
			if (this.formData.productId == 0)
				this.errors.add('productId');
			if (Helper.isEmpty(this.formData.name))
				this.errors.add('name');
			if (Helper.isEmpty(this.formData.saleDate))
				this.errors.add('saleDate');
			
			if (this.errors.size == 0)
			{
				this.$dispatch('show-loading');
				this.$el.submit();
			}
			else
				return false;
		},
		
		reset() {
			this.formData.brandId = 1;
			this.formData.productId = 0;
			this.formData.name = '';
			this.formData.saleDate = '';
			this.formData.tasteKeyWord = '';
			this.formData.status = 1;
			this.errors.clear();
		}
    }));
});

