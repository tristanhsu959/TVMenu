
document.addEventListener('alpine:init', () => {
	Alpine.data('mediaForm', () => ({
		formData: {
			id: 0,
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
		async listMedia() {
			/* const formData = new FormData(); */
            
            const response = await axios.get('/api/tvMenu/medias');
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
		
		async editMedia() {
			const response = await axios.get(`/api/tvMenu/medias/${this.formData.id}`);
			
			console.log(response);
		},
		
		async updateMedia() {
			
            const response = await axios.put(`/api/tvMenu/medias/${this.formData.id}`, this.formData);
		},
		
		async deleteMedia() {
			
            const response = await axios.delete(`/api/tvMenu/medias/${this.formData.id}`);
		},
		
    }));
	
	Alpine.data('menuForm', () => ({
		mockPayload: '',
		
		init() {
			
		},
		
		async listMenu() {
			const response = await axios.get('/api/tvMenu/menus');
		},
		
		async createMenu() {
			/* const mockPayload = {
				"menuName": "八方Menu",
				"isDefault": true,
				"medias": [
					{
					  "id": 1,
					  "duration": 15,
					  "sort": 1
					},
					{
					  "id": 2,
					  "duration": 30,
					  "sort": 2
					}
				]
			};	 */
			/* const formData = JSON.parse(this.mockPayload); */
			/* console.log(JSON.parse(this.mockPayload)); */
            const response = await axios.post('/api/tvMenu/menus', JSON.parse(this.mockPayload), {
                headers: {
                    'Content-Type': 'application/json'
                }
			});
			
			console.log(response);
		},
		
		async editMedia() {
			const response = await axios.get(`/api/tvMenu/menus/${this.formData.id}`);
			
			console.log(response);
		},
		
		async updateMedia() {
			
            const response = await axios.put(`/api/tvMenu/menus/${this.formData.id}`, this.formData);
		},
		
		async deleteMedia() {
			
            const response = await axios.delete(`/api/tvMenu/menus/${this.formData.id}`);
		},
		
    }));
});

