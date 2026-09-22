
document.addEventListener('alpine:init', () => {
	Alpine.data('mediaForm', () => ({
		formData: {
			mediaName: '',
			uploadFile: '',
			uploadLink: '',
			stDate: '',
			endDate: '',
			type: 1,
			enabled: true
		},
		
		init() {
			
		},
		
		handleFileChange(event) {
			const files = event.target.files;
            
			if (files && files.length > 0) 
                this.formData.uploadFile = files[0]; 
		},
		
		async createMedia() {
			const formData = new FormData();
            formData.append('mediaName', this.formData.mediaName);
			formData.append('uploadFile', this.formData.uploadFile);
			formData.append('uploadLink', this.formData.uploadLink);
			formData.append('stDate', this.formData.stDate);
			formData.append('endDate', this.formData.endDate);
			formData.append('type', this.formData.type);
			formData.append('enabled', this.formData.enabled);
			
            const response = await axios.post('/api/tvMenu/medias', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
			});
			
			console.log(response);
		},
		
    }));
});

