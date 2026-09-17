/* Role Create JS */

document.addEventListener('alpine:init', () => {
    Alpine.data('roleForm', (formData, options) => ({
		formData: {...formData},
		options: {...options},
		errors: new Set(),
		
		init(){
			
		},
        validate() {
			this.errors.clear();
			
			if (Helper.isEmpty(this.formData.name))
				this.errors.add('name');
			
			if (this.errors.size == 0)
			{
				this.$dispatch('show-loading');
				this.$el.submit();
			}
			else
				return false;
		},
		
		reset() {
			this.formData.name = '';
			this.formData.permission = [];
			this.formData.area = [];
			this.errors.clear();
		}
    }));
});

/* $(function(){
	$('.btn-reset').click(function(){
		$('#roleForm')[0].reset();
	});
	
	$('.btn-save').click(function(){
		submitForm();
	});
	
	$('#group').change(function(){
		if ($(this).val() == $('#roleForm').data('admin') || $(this).val() == $('#roleForm').data('supervisor'))
		{
			$('.role-permission ul.admin .form-check-input').prop('disabled', false);
			$('.role-permission ul.admin').show();
		}
		else
		{
			$('.role-permission ul.admin .form-check-input').prop('disabled', true);
			$('.role-permission ul.admin').hide();
		}
	}).trigger('change');
	
	$('.permission-group .form-check-input').change(function(){
		let checkAll = false;
		
		if ($(this).is(':checked'))
			checkAll = true;
		
		$(this).closest('.list-group-item').find('.permission-group-items .form-check-input').each(function(){
			$(this).prop('checked', checkAll);
		});
	});
	
	$('.permission-group-items .form-check-input').change(function(){
		if ($(this).closest('.permission-group-items').find('.form-check-input:checked').length == $(this).closest('.permission-group-items').find('.form-check-input').length)
			$(this).closest('.list-group-item').find('.permission-group .form-check-input').prop('checked', true);
		else
			$(this).closest('.list-group-item').find('.permission-group .form-check-input').prop('checked', false);
	}).trigger('change');
});

function submitForm()
{
	//沒有設定權限也可以Submit
	if (validateForm(['#name', '#group']))
	{
		$('#loading').addClass('active');
		$('#roleForm').submit();
	}
	else
		showAlertDialog('身份及權限群組為必填');
} */