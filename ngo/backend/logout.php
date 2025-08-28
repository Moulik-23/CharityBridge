<?php
session_start();
session_unset();
session_destroy();
header("Location: /charitybridge/index.html");
exit();

?>
