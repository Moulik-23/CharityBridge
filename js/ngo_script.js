document.addEventListener('DOMContentLoaded', function() {
    const ngoRegisterForm = document.getElementById('ngo-register-form');
    const ngoRegisterButton = document.getElementById('ngo-register-button');

    if (ngoRegisterButton) {
        ngoRegisterButton.addEventListener('click', async function(e) { // Made async
            e.preventDefault(); // Prevent default form submission initially

            // Clear previous custom validity messages
            const allInputs = ngoRegisterForm.querySelectorAll('input, select, textarea');
            allInputs.forEach(input => input.setCustomValidity(''));

            const isValid = await validateForm(); // Await the asynchronous validation

            if (isValid) {
                ngoRegisterForm.submit(); // If validation passes, submit the form
            } else {
                // If validation fails, report validity to show browser's native validation messages
                ngoRegisterForm.reportValidity();
            }
        });
    }

    async function checkDuplicate(field, value, inputElement) {
        const response = await fetch('../../ngo/backend/check_duplicate.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `field=${encodeURIComponent(field)}&value=${encodeURIComponent(value)}`
        });
        const data = await response.json();
        if (data.isDuplicate) {
            inputElement.setCustomValidity(data.message);
            return false;
        }
        return true;
    }

    async function validateForm() { // Made async
        let isValid = true;
        const duplicateChecks = [];

        // Basic Information
        const ngoName = document.getElementById('ngo_name');
        const email = document.getElementById('email');
        const mobile = document.getElementById('mobile');
        const password = document.getElementById('password');

        if (ngoName.value.trim() === '') {
            ngoName.setCustomValidity('NGO Name is required.');
            isValid = false;
        }

        if (email.value.trim() === '') {
            email.setCustomValidity('Email Address is required.');
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
            email.setCustomValidity('Invalid email format.');
            isValid = false;
        } else {
            duplicateChecks.push(checkDuplicate('email', email.value, email));
        }

        if (mobile.value.trim() === '') {
            mobile.setCustomValidity('Mobile Number is required.');
            isValid = false;
        } else if (!/^[0-9]{10}$/.test(mobile.value)) {
            mobile.setCustomValidity('Mobile number must be 10 digits.');
            isValid = false;
        } else {
            duplicateChecks.push(checkDuplicate('mobile', mobile.value, mobile));
        }

        if (password.value.trim() === '') {
            password.setCustomValidity('Password is required.');
            isValid = false;
        } else if (password.value.length < 6 || !/[a-zA-Z]/.test(password.value) || !/[0-9]/.test(password.value)) {
            password.setCustomValidity('Password must be at least 6 characters, with 1 letter and 1 number.');
            isValid = false;
        }

        // Organization Details
        const orgPan = document.getElementById('org_pan');
        const regNumber = document.getElementById('reg_number');
        const ngoType = document.getElementById('ngo_type');
        const darpanId = document.getElementById('darpan_id');
        const ownerPan = document.getElementById('owner_pan');
        const ownerName = document.getElementById('owner_name');

        if (orgPan.value.trim() === '') {
            orgPan.setCustomValidity('Organization PAN Number is required.');
            isValid = false;
        } else if (!/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(orgPan.value.toUpperCase())) {
            orgPan.setCustomValidity('Invalid Organization PAN format (e.g., ABCDE1234F).');
            isValid = false;
        } else {
            duplicateChecks.push(checkDuplicate('org_pan', orgPan.value.toUpperCase(), orgPan));
        }

        if (regNumber.value.trim() === '') {
            regNumber.setCustomValidity('Registration Number is required.');
            isValid = false;
        } else {
            duplicateChecks.push(checkDuplicate('reg_number', regNumber.value, regNumber));
        }

        if (ngoType.value === '') {
            ngoType.setCustomValidity('NGO Type is required.');
            isValid = false;
        }

        if (darpanId.value.trim() === '') {
            darpanId.setCustomValidity('Darpan ID is required.');
            isValid = false;
        } else if (!/^GJ[0-9]{11}$/.test(darpanId.value.toUpperCase())) {
            darpanId.setCustomValidity('Invalid Darpan ID format (e.g., GJ20201234567).');
            isValid = false;
        } else {
            duplicateChecks.push(checkDuplicate('darpan_id', darpanId.value.toUpperCase(), darpanId));
        }

        if (ownerPan.value.trim() === '') {
            ownerPan.setCustomValidity('Owner PAN Number is required.');
            isValid = false;
        } else if (!/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/.test(ownerPan.value.toUpperCase())) {
            ownerPan.setCustomValidity('Invalid Owner PAN format (e.g., ABCDE1234F).');
            isValid = false;
        } else {
            duplicateChecks.push(checkDuplicate('owner_pan', ownerPan.value.toUpperCase(), ownerPan));
        }

        if (ownerName.value.trim() === '') {
            ownerName.setCustomValidity('Owner Full Name is required.');
            isValid = false;
        }

        // Custom validation: Owner PAN and Organization PAN should not be the same
        if (orgPan.value.trim() !== '' && ownerPan.value.trim() !== '' && orgPan.value.toUpperCase() === ownerPan.value.toUpperCase()) {
            orgPan.setCustomValidity('Organization PAN and Owner PAN cannot be the same.');
            ownerPan.setCustomValidity('Organization PAN and Owner PAN cannot be the same.');
            isValid = false;
        }

        // Documents Section
        const certificate = document.getElementById('certificate');
        if (certificate.files.length === 0) {
            certificate.setCustomValidity('12A or 80G Certificate is required.');
            isValid = false;
        } else {
            const file = certificate.files[0];
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (file.size > maxSize) {
                certificate.setCustomValidity('File size exceeds 5MB limit.');
                isValid = false;
            }
            const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                certificate.setCustomValidity('Only JPG, PNG, or PDF files are allowed.');
                isValid = false;
            }
        }

        // Bank Details Section
        const accNo = document.getElementById('acc_no');
        const ifscCode = document.getElementById('ifsc_code');

        if (accNo.value.trim() === '') {
            accNo.setCustomValidity('Bank Account Number is required.');
            isValid = false;
        } else if (!/^[0-9]{9,18}$/.test(accNo.value)) { // Assuming 9 to 18 digits for account number
            accNo.setCustomValidity('Invalid Bank Account Number (9-18 digits).');
            isValid = false;
        } else {
            duplicateChecks.push(checkDuplicate('acc_no', accNo.value, accNo));
        }

        if (ifscCode.value.trim() === '') {
            ifscCode.setCustomValidity('IFSC Code is required.');
            isValid = false;
        } else if (!/^[A-Z]{4}0[A-Z0-9]{6}$/.test(ifscCode.value.toUpperCase())) {
            ifscCode.setCustomValidity('Invalid IFSC Code format (e.g., HDFC0001234).');
            isValid = false;
        } else {
            duplicateChecks.push(checkDuplicate('ifsc_code', ifscCode.value.toUpperCase(), ifscCode));
        }

        // Terms and Conditions
        const termsCheckbox = document.getElementById('terms_checkbox');
        if (!termsCheckbox.checked) {
            termsCheckbox.setCustomValidity('You must agree to the Terms of Service and Privacy Policy.');
            isValid = false;
        }

        // Wait for all duplicate checks to complete
        const duplicateResults = await Promise.all(duplicateChecks);
        if (duplicateResults.includes(false)) {
            isValid = false;
        }

        return isValid;
    }
});
