<?php
/**
 * Logout Page
 */

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';

logout();
setFlash('success', 'You have been logged out successfully.');
redirect('/');
