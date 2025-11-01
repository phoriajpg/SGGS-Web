<?php
session_start();
session_destroy();
setcookie(session_name(), '', time() - 3600, '/');
echo "Session cleared! <a href='debug_login.php'>Go back to login</a>";
?>