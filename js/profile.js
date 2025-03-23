document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentElement.querySelector('input');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });

    // Attach form submission handler to all submit buttons
    document.querySelectorAll('.submit-btn').forEach(button => {
        const form = button.closest('form');
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }
    });

    // Validate form input fields
    function validateInput(input) {
        const inputGroup = input.closest('.input-group');
        const icon = inputGroup.querySelector('i:first-child');
        let isValid = true;
        let message = '';

        if (input.name === 'username') {
            const usernameRegex = /^[a-zA-Z0-9_]+$/;
            isValid = usernameRegex.test(input.value);
            message = isValid ? '' : translations.username_invalid;
        } else if (input.name === 'email') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            isValid = emailRegex.test(input.value);
            message = isValid ? '' : translations.email_invalid;
        } else if (input.name === 'password' || input.name === 'new_password') {
            if (input.value.trim() === '') {
                isValid = true;
            } else {
                const passwordRegex = /^(?=.*\d)(?=.*[^a-zA-Z0-9]).{6,}$/;
                isValid = passwordRegex.test(input.value);
                message = isValid ? '' : translations.password_invalid;
            }
        } else if (input.name === 'confirm_password') {
            const password = input.closest('form').querySelector('input[name="password"]').value;
            isValid = input.value === password;
            message = isValid ? '' : translations.passwords_mismatch;
        }

        if (!isValid) {
            inputGroup.style.borderColor = '#ef4444';
            inputGroup.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.1)';
            icon.style.color = '#ef4444';
            showNotification('warning', message);
        } else {
            inputGroup.style.borderColor = '#e0e0e0';
            inputGroup.style.boxShadow = 'none';
            icon.style.color = '#aaa';
        }

        return isValid;
    }

    // Handle form submission with validation
    function handleFormSubmit(event) {
        event.preventDefault();
        
        const form = event.target;
        const inputs = form.querySelectorAll('.input-group input');
        let allValid = true;

        inputs.forEach(input => {
            if (input.name === 'facebook_username' || input.name === 'twitter_username') {
                if (!validateSocial(input)) allValid = false;
            } else {
                if (!validateInput(input)) allValid = false;
            }
        });

        if (allValid) {
            setTimeout(() => {
                form.submit();
            }, 500);
        }
    }
});