document.addEventListener('DOMContentLoaded', function() {
    const messageDisplay = document.getElementById('messageDisplay');

    function displayMessage(message, type) {
        messageDisplay.textContent = message;
        messageDisplay.classList.remove('hidden', 'bg-green-100', 'text-green-800', 'bg-red-100', 'text-red-800');
        if (type === 'success') {
            messageDisplay.classList.add('bg-green-100', 'text-green-800');
        } else if (type === 'error') {
            messageDisplay.classList.add('bg-red-100', 'text-red-800');
        }
        messageDisplay.classList.remove('hidden');
    }

    document.getElementById('donationForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent default form submission

        const form = event.target;
        const formData = new FormData(form);

        fetch('backend/process_goods_donation.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json()) // Get response as JSON
        .then(data => {
            if (data.success) {
                displayMessage(data.message, 'success');
                if (data.redirect) {
                    // Delay redirect slightly to allow message to be seen
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500); 
                }
            } else {
                displayMessage(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            displayMessage('An error occurred during donation submission.', 'error');
        });
    });
});
