/* Role Create JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('productForm', (response) => ({
		initFormData: {...response.formData}, /*避免引用同一object位址*/
		formData: {...response.formData},
		options: {...response.options},
		hasSecondaryNo: false,
		errors: new Set(),
		
		init() {
			this.initErpNoInput();
		},
		
		initErpNoInput() {
			this.errors.delete('brandId');
			this.hasSecondaryNo = (this.formData.brandId == response.formData.buygoodId);
		},
		
		initCategory(){
			this.errors.delete('category');
			this.formData.category = 0;
			this.initErpNoInput();
		},
		
		validate() {
			this.errors.clear();
			
			if (Helper.isEmpty(this.formData.name))
				this.errors.add('name');
			if (this.formData.brandId == 0)
				this.errors.add('brandId');
			if (this.formData.primaryNo == '')
				this.errors.add('primaryNo');
			
			if (this.errors.size == 0)
			{
				this.$dispatch('show-loading');
				this.$el.submit();
			}
			else
				return false;
		},
		
		reset() {
			this.formData = {...this.initFormData};
			this.errors.clear();
		}
    }));
});

