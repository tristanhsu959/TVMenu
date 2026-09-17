/* Role Create JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('salesSettingForm', (response) => ({
		oriFormData: structuredClone(response.formData), //deep copy
		formData: response.formData,
		options: response.options,
		errors: new Set(),
		
		init() {
			
		},
		
		validate() {
			this.errors.clear();
			
			if (this.formData.brandId == 0)
				this.errors.add('brandId');
			
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

