<?php
// cron/archive.php
// Called hourly to move past meetups from 'published' → 'past'
// Also triggered on every public page load, but cron is the safety net.

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/meetups.php';

auto_archive();
echo date('Y-m-d H:i:s') . " archive run OK\n";
