/* Role Create JS */

document.addEventListener('alpine:init', () => {
	Alpine.data('userProfile', (profile, options) => ({
		profile: {...profile},
		options: options,
		
		init() {
			
		},
    }));
	
	Alpine.data('userProfileEdit', (formData, options) => ({
		profile: structuredClone(formData), //deep copy
		formData: {...formData},
		options: options,
		errors: new Set(),
		fieldEnabled: [],
		showPassword: false,
		
		init() {
			this.formData.userPassword = '';
			this.formData.passwordOnly = '';
			this.showPassword = false;
			this.initFieldEnabled();
		},
		
		initFieldEnabled(){
			/* this.fieldEnabled.push('displayName');
			this.fieldEnabled.push('department');
			this.fieldEnabled.push('email');
			this.fieldEnabled.push('password'); */
		},
		
		validate() {
			this.errors.clear();
			const passwordElement = document.querySelector('#password');
			const emailElement = document.querySelector('#email');

			if (! passwordElement.disabled)
			{
				const pattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/;
				
				if (Helper.isEmpty(this.formData.userPassword) || ! pattern.test(this.formData.userPassword)) 
					this.errors.add('password');
			}
			
			if (! emailElement.disabled)
			{
				const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				
				if (Helper.isEmpty(this.formData.email) || ! pattern.test(this.formData.email)) 
				{
					this.errors.add('email');
					Alpine.store('toast').notify('Email格式錯誤(xxx.xx@domain.com.tw)');
				}
			}
			
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
			this.formData.userPassword = '';
			this.formData.userDisplayName = this.profile.userDisplayName;
			this.formData.department = this.profile.department;
			this.formData.email = this.profile.email;
			this.initFieldEnabled();
		}
    }));
});

