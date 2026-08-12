<?php
// Admin-facing entry for shared notifications.
require_once __DIR__ . '/../includes/auth_check.php';
skillmap_require_permission('send_notifications');

require __DIR__ . '/../notifications.php';
