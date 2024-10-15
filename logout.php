<?php
    session_start();
    session_destroy();
    header('Location: mainscreen.php');
    exit;
?>