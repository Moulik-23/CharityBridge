<?php
session_start();
session_unset();
session_destroy();

// Always redirect to root index.html
header("Location: /Charitybridge2/index.html");
exit();
?>
