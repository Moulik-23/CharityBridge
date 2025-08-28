<?php
session_start();
session_unset();
session_destroy();

// Always redirect to root index.html
header("Location: /charitybridge/index.html");
exit();
?>
