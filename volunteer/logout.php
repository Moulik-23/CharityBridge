<?php
session_start();
session_unset();
session_destroy();
header("Location: /Charitybridge2/index.html");
exit();
?>
