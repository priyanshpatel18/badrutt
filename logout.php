<?php
require_once 'config.php';

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to login page with a message
redirectWithMessage("login.php", "You have been successfully logged out.");
