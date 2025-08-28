document.addEventListener('DOMContentLoaded', function () {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function () {
            mobileMenu.classList.toggle('hidden');
        });
    }

    const searchBtn = document.getElementById('searchBtn');
    const searchInput = document.getElementById('searchInput');
    const resultsContainer = document.getElementById('results-container');

    let ngos = [];

    async function fetchNgos() {
        try {
            let path = '';
            const currentPath = window.location.pathname;
            // Determine the correct path based on the current page's location
            if (currentPath.startsWith('/donor/') || currentPath.startsWith('/ngo/') || currentPath.startsWith('/volunteer/') || currentPath.startsWith('/admin/')) {
                path = '../';
            } else if (currentPath === '/' || currentPath === '/index.html' || currentPath === '/cheritybridge/' || currentPath === '/cheritybridge' || currentPath === '/cheritybridge/index.html') { // Handle root, index.html, and root directory explicitly
                path = ''; // Root path
            }
            const response = await fetch('../data/ngos.json');
            ngos = await response.json();
            displayNgos(ngos);
        } catch (error) {
            console.error('Error fetching NGOs:', error);
        }
    }

    function displayNgos(filteredNgos) {
        resultsContainer.innerHTML = '';
        if (filteredNgos.length > 0) {
            filteredNgos.forEach(ngo => {
                const ngoCard = `
                    <div class="bg-white p-6 rounded-lg shadow-md">
                        <h3 class="text-xl font-bold mb-2">${ngo.name}</h3>
                        <p class="text-gray-700 mb-2"><strong>Cause:</strong> ${ngo.cause}</p>
                        <p class="text-gray-600"><strong>Location:</strong> ${ngo.location}</p>
                    </div>
                `;
                resultsContainer.innerHTML += ngoCard;
            });
        } else {
            resultsContainer.innerHTML = '<p class="text-center col-span-full">No NGOs found matching your search.</p>';
        }
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function () {
            const query = searchInput.value.toLowerCase();
            const filteredNgos = ngos.filter(ngo => {
                return ngo.name.toLowerCase().includes(query) ||
                       ngo.cause.toLowerCase().includes(query) ||
                       ngo.location.toLowerCase().includes(query);
            });
            displayNgos(filteredNgos);
        });
    }

    if (resultsContainer) {
        fetchNgos();
    }

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

    // Logout functionality
    const logoutButtons = document.querySelectorAll('a[href="../index.html"]');
    logoutButtons.forEach(button => {
        if (button.textContent === 'Logout') {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                sessionStorage.removeItem('userRole');
                window.location.href = '../index.html';
            });
        }
    });
});
