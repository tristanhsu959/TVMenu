/* App JS */

document.addEventListener('alpine:init', () => {
	
	Alpine.store('app', { isLoading: false });
	
	Alpine.store('menu', { active: Alpine.$persist(true) });
	
	Alpine.store('toast', {
		initialize(msg = '') {
            if (msg != '') {
                //等待DOM渲染完畢
				Alpine.nextTick(() => {
					this.notify(msg);
				});
            }
        },
		
		notify(message, status = false) {
			if (window.ui) {
				const el = document.querySelector('#notifyMsg');
				const msgEl = el.querySelector('.message');
				const colorClass = (status == true) ? 'green' : 'error';
				
				//Set or reset to empty
				el.classList.remove('green', 'error', 'white-text');
				msgEl.innerText = message;
				el.classList.add(colorClass, 'white-text');
				
				if (message != '')
					ui('#notifyMsg');
			}
		},
	});
	
	Alpine.store('dialog', {
		title: '',
		icon: '',
		color: 'red',
		message: '',
		isConfirm: false,
		callback: false,

		show(message, isConfirm = false, callback = false) {
			this.title = isConfirm ? 'CONFIRM' : 'ALERT';
			this.icon = isConfirm ? 'verified' : 'release_alert';
			this.color = isConfirm ? 'blue-text' : 'red-text';
			this.message = message;
			this.isConfirm = isConfirm;
			this.callback = callback;
			
			ui('#modal-dialog');
		},

		confirm() {
			if (this.callback) 
				this.callback();
			
			ui('#modal-dialog');
		}
	});
	
	
});
