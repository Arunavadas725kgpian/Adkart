<?php
session_start();
session_destroy(); // ইউজারের সেশন মুছে দেওয়া
header("Location: login.php");
exit();
?>