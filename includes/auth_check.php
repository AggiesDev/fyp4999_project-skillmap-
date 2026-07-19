<?php
// Authentication gate for protected student and admin pages.

require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: /fyp_skillmapsystem/login.php');
    exit;
}
