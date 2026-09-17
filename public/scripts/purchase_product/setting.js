/* Role Create JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('purchaseSettingForm', (formData, options) => ({
		oriFormData: structuredClone(formData), //deep copy
		formData: formData,
		options: options,
		//brandProducts: [], //current product list
		errors: new Set(),
		
		init() {
		},
		
		validate() {
			this.errors.clear();
			
			if (this.formData.brandId == 0)
				this.errors.add('brandId');
			
			//可reset全部為disabled, 故不用判別
			/* if (! Object.values(this.formData.productCodes).some(arr => arr.length > 0))
			{
				this.errors.add('productCodes');
				Alpine.store('toast').notify('請勾選產品');
			} */
			
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
			this.formData = JSON.parse(JSON.stringify(this.oriFormData));
		}
    }));
});

