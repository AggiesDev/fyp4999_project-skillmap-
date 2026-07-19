<?php
// Ends the current Skill Map session and redirects to the login page.

require_once __DIR__ . '/includes/auth.php';

logout();
header('Location: /fyp_skillmapsystem/login.php');
exit;
