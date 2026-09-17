/* JS */

document.addEventListener('alpine:init', () => {
	Alpine.store('userFilter', {
		filter: '',
		
		reset(){
			this.filter = '';
		}
	});
	
    Alpine.data('userList', (response, list) => ({
		response: response,
		list: {...list},
		
		init(){},
		
		confirmDelete(url) {
			Alpine.store('dialog').show('確定要刪除此帳號?', true, () => this.deleteUser(url));
		},
		
		get filterUsers() {
			const searchKeyword = Alpine.store('userFilter').filter.toLowerCase();
			const list = Object.values(this.list.data);
			
			const result = list.filter(user => 
				String(user.userAccount || '').toLowerCase().includes(searchKeyword) ||
				String(user.userDisplayName || '').toLowerCase().includes(searchKeyword) ||
				String(user.department || '').toLowerCase().includes(searchKeyword) ||
				String(user.isActive ?? 0).toLowerCase().includes(String(searchKeyword).toLowerCase())
			);
			
			return result;
		},
		
		deleteUser(url) {
			this.$dispatch('show-loading');
			const form = this.$refs.userListForm;
            form.action = url;
            form.submit();
		}
    }));
});

