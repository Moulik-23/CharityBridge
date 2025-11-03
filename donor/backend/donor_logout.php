<?php
session_start();
// Destroy all session data
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_unset();
session_destroy();

// Clear any additional cookies
setcookie('charitybridge_session', '', time()-3600, '/');

// Redirect with cache prevention
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Location: ../login.html");
exit();
?>
