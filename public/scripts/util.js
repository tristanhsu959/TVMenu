/* Util JS */
/* V3寫法與V2不同 */

document.addEventListener('alpine:init', () => {
    /* Alpine.data('toast11', (formData) => ({
		adAccount: formData.account,
		adPassword: formData.password,
		errors: new Set(),
		isLoading: false,
		
        validate() {
			this.errors.clear();
			
			if (Helper.isEmpty(this.adAccount))
				this.errors.add('adAccount');
			if (Helper.isEmpty(this.adPassword))
				this.errors.add('adPassword');
			
			if (this.errors.size == 0)
				this.$el.submit();
			else
				return false;
		},
		
		reset() {
			this.adAccount = '';
			this.adPassword = '';
			this.errors.clear();
			this.isLoading = false;
		}
    })); */
});
