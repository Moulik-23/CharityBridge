document.addEventListener('DOMContentLoaded', function() {
    // JavaScript for Accept/Reject Food Donations
    window.acceptFoodDonation = function(foodPostId, buttonElement) {
        console.log('Attempting to accept food donation with ID:', foodPostId);
        fetch("../backend/accept_food_donation.php", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `food_post_id=${foodPostId}`
        })
        .then(res => {
            if (!res.ok) {
                console.error('Server responded with an error status:', res.status, res.statusText);
                return res.text().then(text => { throw new Error(text); });
            }
            const contentType = res.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return res.json();
            } else {
                return res.text().then(text => { throw new Error('Expected JSON, but received: ' + text); });
            }
        })
        .then(jsonResponse => {
            console.log('Server JSON response:', jsonResponse);
            alert(jsonResponse.message);
            if (jsonResponse.success) {
                // Update the UI dynamically instead of reloading
                // This would involve finding the row and updating its status and actions
                // For now, we'll just reload for simplicity, but this is where dynamic updates would go.
                location.reload(); 
            }
        })
        .catch(err => {
            alert('An unexpected error occurred. Check console for details.');
            console.error('Fetch error:', err);
        });
    }

    window.rejectFoodDonation = function(foodPostId, buttonElement) {
        console.log('Attempting to reject food donation with ID:', foodPostId);
        fetch("../backend/reject_food_donation.php", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `food_post_id=${foodPostId}`
        })
        .then(res => {
            if (!res.ok) {
                console.error('Server responded with an error status:', res.status, res.statusText);
                return res.text().then(text => { throw new Error(text); });
            }
            const contentType = res.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return res.json();
            } else {
                return res.text().then(text => { throw new Error('Expected JSON, but received: ' + text); });
            }
        })
        .then(jsonResponse => {
            console.log('Server JSON response:', jsonResponse);
            alert(jsonResponse.message);
            if (jsonResponse.success) {
                // Update the UI dynamically instead of reloading
                location.reload();
            }
        })
        .catch(err => {
            alert('An unexpected error occurred. Check console for details.');
            console.error('Fetch error:', err);
        });
    }
});
