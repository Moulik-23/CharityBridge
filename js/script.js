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

    // Fetch and display real-time stats on homepage
    async function fetchStats() {
        try {
            console.log('Fetching stats from API...');
            const response = await fetch('api/get_stats.php');
            console.log('Response status:', response.status);
            
            const stats = await response.json();
            console.log('Stats received:', stats);
            
            if (!stats.error) {
                // Animate the numbers
                animateNumber('beneficiaries-count', stats.beneficiaries);
                animateNumber('ngos-count', stats.ngos);
                animateNumber('meals-count', stats.meals);
                animateNumber('volunteer-hours-count', stats.volunteerHours);
            } else {
                console.error('API returned error:', stats.error);
            }
        } catch (error) {
            console.error('Error fetching stats:', error);
        }
    }

    function animateNumber(elementId, targetNumber) {
        const element = document.getElementById(elementId);
        if (!element) return;
        
        const duration = 2000; // 2 seconds
        const startTime = performance.now();
        const startNumber = 0;
        
        function updateNumber(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function for smooth animation
            const easeOutQuad = progress * (2 - progress);
            const currentNumber = Math.floor(startNumber + (targetNumber - startNumber) * easeOutQuad);
            
            element.textContent = currentNumber.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            } else {
                element.textContent = targetNumber.toLocaleString();
            }
        }
        
        requestAnimationFrame(updateNumber);
    }

    // Load stats if we're on the homepage
    if (document.getElementById('beneficiaries-count')) {
        fetchStats();
    }
});
