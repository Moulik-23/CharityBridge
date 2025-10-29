function approveNgo(id, rowElement) {
    console.log('Attempting to approve NGO with ID:', id);
    fetch("backend/approve_ngo.php", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success && rowElement) {
            rowElement.remove();
        }
    })
    .catch(err => console.error(err));
}

function rejectNgo(id, rowElement) {
    console.log('Attempting to reject NGO with ID:', id);
    fetch("backend/reject_ngo.php", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}`
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success && rowElement) {
            rowElement.remove();
        }
    })
    .catch(err => console.error(err));
}

document.addEventListener('DOMContentLoaded', function () {
    // Admin Login functionality
    const adminLoginForm = document.querySelector('form');
    if (adminLoginForm && window.location.pathname.includes('admin/login.html')) {
        adminLoginForm.addEventListener('submit', function(event) {
            event.preventDefault();
            const role = document.getElementById('role').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            // Dummy validation
            if (role === 'admin' && email === 'admin@example.com' && password === 'password') {
                sessionStorage.setItem('userRole', 'admin');
                window.location.href = 'dashboard.html';
            } else if (role === 'developer' && email === 'dev@example.com' && password === 'devpassword') {
                sessionStorage.setItem('userRole', 'developer');
                window.location.href = 'dashboard.html';
            } else {
                alert('Invalid credentials');
            }
        });
    }
});
