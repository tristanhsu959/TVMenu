/* Helper JS */
/* V3寫法與V2不同 */

const Helper = {
    isValidPassword(password) {
        const pattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{6,}$/;
        return pattern.test(password);
    },

    isValidEmail(mail) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(mail);
    },
	
	isEmpty(value) {
        return value === null || value === undefined || value.trim() === '';
    },
	
	hasError(errors, key) {
        return errors.has(key) ? 'invalid' : '';
    },
	
	removeError(errors, key) {
        return errors.delete(key);
    },
	
	formatDollar(num, digits) {
		digits = digits || 0;
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
			 maximumFractionDigits: digits
        }).format(num);
    },
};
