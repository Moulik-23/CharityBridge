<?php
session_start();
session_destroy();
header('Location: ../auth/restaurant_login.html');
exit();
?>
