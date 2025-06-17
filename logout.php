<?php
session_start();
session_destroy();
header('Location: events.php');
exit;
?>