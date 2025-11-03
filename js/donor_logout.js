// Donor logout handler
document.addEventListener('DOMContentLoaded', function() {
    // Find all logout links in donor pages
    const logoutLinks = document.querySelectorAll('a[href*="donor_logout.php"]');
    
    logoutLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Clear all localStorage and sessionStorage
            localStorage.clear();
            sessionStorage.clear();
            
            // Clear IndexedDB if present
            if (window.indexedDB) {
                indexedDB.databases().then(databases => {
                    databases.forEach(db => {
                        indexedDB.deleteDatabase(db.name);
                    });
                }).catch(() => {
                    // Ignore errors for browsers that don't support databases()
                });
            }
            
            // Redirect to logout PHP script
            window.location.href = this.href;
        });
    });
});
