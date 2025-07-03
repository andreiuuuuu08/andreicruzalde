<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Destroy the session
session_destroy();

// Redirect to login page
redirect(SITE_URL . '/login.php');
?>
