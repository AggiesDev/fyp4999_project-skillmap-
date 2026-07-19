<?php
// Ends the current Skill Map session and redirects to the login page.

require_once __DIR__ . '/includes/functions.php';

session_destroy();
header('Location: /fyp_skillmapsystem/login.php');
exit;
