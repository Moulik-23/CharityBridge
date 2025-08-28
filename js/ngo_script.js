// ngo_script.js - NGO Registration Form Validation

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('ngo-register-form');
    
    if (form) {
        // Get form elements
        const ngoName = document.getElementById('ngo_name');
        const email = document.getElementById('email');
        const mobile = document.getElementById('mobile');
        const password = document.getElementById('password');
        const orgPan = document.getElementById('org_pan');
        const ownerPan = document.getElementById('owner_pan');
        const darpanId = document.getElementById('darpan_id');
        const ifscCode = document.getElementById('ifsc_code');
        const accNo = document.getElementById('acc_no');
        const certificate = document.getElementById('certificate');
        const ownerName = document.getElementById('owner_name');
        const regNumber = document.getElementById('reg_number');
        const ngoType = document.getElementById('ngo_type');

        // Validation Functions
        
        /**
         * Validates IFSC Code
         * Format: First 4 chars - alphabetic (bank code)
         *         5th char - always '0'
         *         Last 6 chars - alphanumeric (branch code)
         */
        function validateIFSC(ifsc) {
            if (!ifsc || ifsc.length !== 11) {
                return { valid: false, message: 'IFSC code must be exactly 11 characters' };
            }
            
            // Convert to uppercase for validation
            ifsc = ifsc.toUpperCase();
            
            // Check first 4 characters are alphabets
            const bankCode = ifsc.substring(0, 4);
            if (!/^[A-Z]{4}$/.test(bankCode)) {
                return { valid: false, message: 'First 4 characters must be alphabets (Bank code)' };
            }
            
            // Check 5th character is '0'
            if (ifsc.charAt(4) !== '0') {
                return { valid: false, message: '5th character must be 0' };
            }
            
            // Check last 6 characters are alphanumeric
            const branchCode = ifsc.substring(5);
            if (!/^[A-Z0-9]{6}$/.test(branchCode)) {
                return { valid: false, message: 'Last 6 characters must be alphanumeric (Branch code)' };
            }
            
            return { valid: true, message: 'Valid IFSC code' };
        }
        
        /**
         * Validates Darpan ID
         * Format: XX (2 letter state code) + YYYY (4 digit year) + ZZZZZZZ (7 digit unique ID)
         * Total: 13 characters
         */
        function validateDarpanId(darpanId) {
            if (!darpanId || darpanId.length !== 13) {
                return { valid: false, message: 'Darpan ID must be exactly 13 characters' };
            }
            
            // Convert to uppercase for validation
            darpanId = darpanId.toUpperCase();
            
            // Check first 2 characters are alphabets (state code)
            const stateCode = darpanId.substring(0, 2);
            if (!/^[A-Z]{2}$/.test(stateCode)) {
                return { valid: false, message: 'First 2 characters must be alphabets (State code)' };
            }
            
            // Common state codes validation (optional - you can expand this list)
            const validStateCodes = ['GJ', 'MH', 'RJ', 'DL', 'UP', 'MP', 'KA', 'TN', 'AP', 'TS', 'WB', 'OR', 'BR', 'JH', 'CH', 'HR', 'PB', 'UK', 'HP', 'JK', 'GA', 'KL', 'AS', 'TR', 'ML', 'MN', 'MZ', 'NL', 'SK', 'AR'];
            if (!validStateCodes.includes(stateCode)) {
                return { valid: false, message: `Invalid state code: ${stateCode}` };
            }
            
            // Check next 4 characters are digits (year)
            const year = darpanId.substring(2, 6);
            if (!/^\d{4}$/.test(year)) {
                return { valid: false, message: 'Characters 3-6 must be digits (Year of registration)' };
            }
            
            // Validate year range (between 2000 and current year)
            const yearNum = parseInt(year);
            const currentYear = new Date().getFullYear();
            if (yearNum < 2000 || yearNum > currentYear) {
                return { valid: false, message: `Year must be between 2000 and ${currentYear}` };
            }
            
            // Check last 7 characters are digits (unique ID)
            const uniqueId = darpanId.substring(6);
            if (!/^\d{7}$/.test(uniqueId)) {
                return { valid: false, message: 'Last 7 characters must be digits (Unique ID)' };
            }
            
            return { valid: true, message: 'Valid Darpan ID' };
        }
        
        /**
         * Validates PAN Card
         * Format: 10 alphanumeric characters
         * Pattern: 5 alphabets + 4 digits + 1 alphabet (check digit)
         * Example: ABCDE1234F
         */
        function validatePAN(pan) {
            if (!pan || pan.length !== 10) {
                return { valid: false, message: 'PAN must be exactly 10 characters' };
            }
            
            // Convert to uppercase for validation
            pan = pan.toUpperCase();
            
            // PAN pattern: AAAAA9999A
            const panPattern = /^[A-Z]{5}[0-9]{4}[A-Z]$/;
            
            if (!panPattern.test(pan)) {
                return { valid: false, message: 'Invalid PAN format. Format should be: 5 letters + 4 digits + 1 letter (e.g., ABCDE1234F)' };
            }
            
            // Additional validation for the 4th character (entity type)
            const fourthChar = pan.charAt(3);
            const validFourthChars = ['A', 'B', 'C', 'F', 'G', 'H', 'L', 'J', 'P', 'T'];
            if (!validFourthChars.includes(fourthChar)) {
                return { valid: false, message: 'Invalid PAN entity type' };
            }
            
            return { valid: true, message: 'Valid PAN number' };
        }
        
        /**
         * Validates Email
         */
        function validateEmail(email) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                return { valid: false, message: 'Please enter a valid email address' };
            }
            return { valid: true, message: 'Valid email' };
        }
        
        /**
         * Validates Mobile Number (Indian)
         */
        function validateMobile(mobile) {
            if (!mobile || mobile.length !== 10) {
                return { valid: false, message: 'Mobile number must be exactly 10 digits' };
            }
            
            if (!/^[6-9]\d{9}$/.test(mobile)) {
                return { valid: false, message: 'Invalid mobile number. Must start with 6-9' };
            }
            
            return { valid: true, message: 'Valid mobile number' };
        }
        
        /**
         * Validates Password
         */
        function validatePassword(password) {
            if (!password || password.length < 6) {
                return { valid: false, message: 'Password must be at least 6 characters' };
            }
            
            if (!/(?=.*[A-Za-z])(?=.*\d)/.test(password)) {
                return { valid: false, message: 'Password must contain at least one letter and one number' };
            }
            
            return { valid: true, message: 'Valid password' };
        }
        
        /**
         * Validates Bank Account Number
         */
        function validateAccountNumber(accNo) {
            if (!accNo || accNo.length < 9 || accNo.length > 18) {
                return { valid: false, message: 'Account number must be between 9 and 18 digits' };
            }
            
            if (!/^\d+$/.test(accNo)) {
                return { valid: false, message: 'Account number must contain only digits' };
            }
            
            return { valid: true, message: 'Valid account number' };
        }
        
        /**
         * Validates File Upload
         */
        function validateFile(file) {
            if (!file) {
                return { valid: false, message: 'Please upload a certificate' };
            }
            
            const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png'];
            if (!allowedTypes.includes(file.type)) {
                return { valid: false, message: 'Only PDF, JPG, JPEG, and PNG files are allowed' };
            }
            
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                return { valid: false, message: 'File size must be less than 5MB' };
            }
            
            return { valid: true, message: 'Valid file' };
        }
        
        /**
         * Show error message
         */
        function showError(input, message) {
            // Remove any existing error
            clearError(input);
            
            // Create error element
            const errorDiv = document.createElement('div');
            errorDiv.className = 'text-red-500 text-sm mt-1 error-message';
            errorDiv.textContent = message;
            
            // Add error class to input
            input.classList.add('border-red-500');
            
            // Insert error message after input
            input.parentElement.appendChild(errorDiv);
        }
        
        /**
         * Clear error message
         */
        function clearError(input) {
            input.classList.remove('border-red-500');
            const errorDiv = input.parentElement.querySelector('.error-message');
            if (errorDiv) {
                errorDiv.remove();
            }
        }
        
        /**
         * Show success message
         */
        function showSuccess(input) {
            clearError(input);
            input.classList.add('border-green-500');
        }
        
        // Real-time validation event listeners
        
        // IFSC Code validation
        ifscCode.addEventListener('blur', function() {
            const result = validateIFSC(this.value);
            if (!result.valid) {
                showError(this, result.message);
            } else {
                showSuccess(this);
            }
        });
        
        // Darpan ID validation
        darpanId.addEventListener('blur', function() {
            const result = validateDarpanId(this.value);
            if (!result.valid) {
                showError(this, result.message);
            } else {
                showSuccess(this);
            }
        });
        
        // Organization PAN validation
        orgPan.addEventListener('blur', function() {
            const result = validatePAN(this.value);
            if (!result.valid) {
                showError(this, result.message);
            } else {
                showSuccess(this);
            }
        });
        
        // Owner PAN validation
        ownerPan.addEventListener('blur', function() {
            const result = validatePAN(this.value);
            if (!result.valid) {
                showError(this, result.message);
            } else {
                showSuccess(this);
                
                // Check if both PANs are same
                if (orgPan.value && this.value && orgPan.value.toUpperCase() === this.value.toUpperCase()) {
                    showError(this, 'Owner PAN and Organization PAN cannot be the same');
                }
            }
        });
        
        // Email validation
        email.addEventListener('blur', function() {
            const result = validateEmail(this.value);
            if (!result.valid) {
                showError(this, result.message);
            } else {
                showSuccess(this);
            }
        });
        
        // Mobile validation
        mobile.addEventListener('blur', function() {
            const result = validateMobile(this.value);
            if (!result.valid) {
                showError(this, result.message);
            } else {
                showSuccess(this);
            }
        });
        
        // Password validation
        password.addEventListener('blur', function() {
            const result = validatePassword(this.value);
            if (!result.valid) {
                showError(this, result.message);
            } else {
                showSuccess(this);
            }
        });
        
        // Account number validation
        accNo.addEventListener('blur', function() {
            const result = validateAccountNumber(this.value);
            if (!result.valid) {
                showError(this, result.message);
            } else {
                showSuccess(this);
            }
        });
        
        // File validation
        // certificate.addEventListener('change', function() {
        //     if (this.files && this.files[0]) {
        //         const result = validateFile(this.files[0]);
        //         if (!result.valid) {
        //             showError(this, result.message);
        //             this.value = ''; // Clear the file input
        //         } else {
        //             showSuccess(this);
        //         }
        //     }
        // });
        
        // Form submission validation
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            let isValid = true;
            const errors = [];
            
            // Validate all fields
            if (!ngoName.value.trim()) {
                showError(ngoName, 'NGO name is required');
                isValid = false;
            }
            
            const emailResult = validateEmail(email.value);
            if (!emailResult.valid) {
                showError(email, emailResult.message);
                isValid = false;
            }
            
            const mobileResult = validateMobile(mobile.value);
            if (!mobileResult.valid) {
                showError(mobile, mobileResult.message);
                isValid = false;
            }
            
            const passwordResult = validatePassword(password.value);
            if (!passwordResult.valid) {
                showError(password, passwordResult.message);
                isValid = false;
            }
            
            const orgPanResult = validatePAN(orgPan.value);
            if (!orgPanResult.valid) {
                showError(orgPan, orgPanResult.message);
                isValid = false;
            }
            
            const ownerPanResult = validatePAN(ownerPan.value);
            if (!ownerPanResult.valid) {
                showError(ownerPan, ownerPanResult.message);
                isValid = false;
            }
            
            // Check if both PANs are different
            if (orgPan.value && ownerPan.value && orgPan.value.toUpperCase() === ownerPan.value.toUpperCase()) {
                showError(ownerPan, 'Owner PAN and Organization PAN cannot be the same');
                isValid = false;
            }
            
            const darpanResult = validateDarpanId(darpanId.value);
            if (!darpanResult.valid) {
                showError(darpanId, darpanResult.message);
                isValid = false;
            }
            
            const ifscResult = validateIFSC(ifscCode.value);
            if (!ifscResult.valid) {
                showError(ifscCode, ifscResult.message);
                isValid = false;
            }
            
            const accNoResult = validateAccountNumber(accNo.value);
            if (!accNoResult.valid) {
                showError(accNo, accNoResult.message);
                isValid = false;
            }
            
            if (!regNumber.value.trim()) {
                showError(regNumber, 'Registration number is required');
                isValid = false;
            }
            
            if (!ngoType.value) {
                showError(ngoType, 'Please select NGO type');
                isValid = false;
            }
            
            if (!ownerName.value.trim()) {
                showError(ownerName, 'Owner name is required');
                isValid = false;
            }
            
            // if (certificate.files && certificate.files[0]) {
            //     const fileResult = validateFile(certificate.files[0]);
            //     if (!fileResult.valid) {
            //         showError(certificate, fileResult.message);
            //         isValid = false;
            //     }
            // } else {
            //     showError(certificate, 'Certificate is required');
            //     isValid = false;
            // }
            
            // If all validations pass, submit the form
            if (isValid) {
                // Show success message
                alert('Form validated successfully! Submitting...');
                
                // Actually submit the form
                this.submit();
            } else {
                // Scroll to first error
                const firstError = document.querySelector('.border-red-500');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // Auto-capitalize certain fields
        [ifscCode, darpanId, orgPan, ownerPan].forEach(field => {
            field.addEventListener('input', function() {
                this.value = this.value.toUpperCase();
            });
        });
        
        // Restrict mobile input to numbers only
        mobile.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Restrict account number to numbers only
        accNo.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
});

// Export validation functions for testing or external use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validateIFSC,
        validateDarpanId,
        validatePAN,
        validateEmail,
        validateMobile,
        validatePassword,
        validateAccountNumber,
        validateFile
    };
}