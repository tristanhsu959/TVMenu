/* Login JS */

document.addEventListener('alpine:init', () => {
    Alpine.data('setPassword', (response) => ({
		formData: {...response.formData},
		errors: new Set(),
		showPassword: false,
		showConfirmPassword: false,
		
		init(){
		},
		validate() {
			this.errors.clear();
			
			if (Helper.isEmpty(this.formData.password))
				this.errors.add('password');
			if (Helper.isEmpty(this.formData.confirmPassword))
				this.errors.add('confirmPassword');
			
			if (this.errors.size > 0)
				return false;
			
			if (! Helper.isValidPassword(this.formData.password))
			{
				this.errors.add('password');
				Alpine.store('toast').notify('密碼格式不符規則');
				return false;
			}
			
			if (this.formData.password != '' && this.formData.confirmPassword != '' &&
					this.formData.password != this.formData.confirmPassword)
			{
				this.errors.add('confirmPassword');
				Alpine.store('toast').notify('新密碼與確認密碼不符');
				return false;
			}
			
			if (this.errors.size == 0)
			{
				this.$el.action = this.formData.formAction;
				this.$el.submit();
			}
			else
				return false;
		},
		
		reset() {
			this.formData.password = '';
			this.formData.confirmPassword = '';
			this.errors.clear();
		}
    }));
});
