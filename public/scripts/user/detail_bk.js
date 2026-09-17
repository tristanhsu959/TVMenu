/* Role Create JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('userForm', (formData, options) => ({
		formData: {...formData},
		options: options,
		errors: new Set(),
		
		validate() {
			this.errors.clear();
			
			if (Helper.isEmpty(this.formData.ad))
				this.errors.add('ad');
			if (this.formData.roleId == 0)
				this.errors.add('roleId');
			
			if (this.errors.size == 0)
			{
				this.$dispatch('show-loading');
				this.$el.submit();
			}
			else
				return false;
		},
		
		reset() {
			this.formData.ad = '';
			this.formData.name = '';
			this.formData.roleId = 0;
		}
    }));
});

