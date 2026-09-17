/* Role Create JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('releaseSettingForm', (formData, options) => ({
		oriFormData: {...formData}, //keep
		formData: {...formData},
		options: {...options},
		products: [],
		errors: new Set(),
		
		init() {
			this.updateProducts();
		},
		
		changeProducts() {
			//onchange一律清空
			Object.keys(this.formData.productIds).forEach(key => {
				this.formData.productIds[key] = [];
			});
			this.updateProducts();
		},
		updateProducts() {
			const selectedBrandId = this.formData.brandId;
			this.products = this.options.products[selectedBrandId] || [];
			
			if (selectedBrandId != this.oriFormData.brandId)
				this.formData.productIds = [];
		},
		
		validate() {
			this.errors.clear();
			
			if (this.formData.brandId == 0)
				this.errors.add('brandId');
			if (this.formData.productIds.length == 0)
				this.errors.add('productIds');
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
			this.errors.clear();
			this.updateProducts();
			this.formData = {...this.oriFormData};
		}
    }));
});

