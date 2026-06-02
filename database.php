<?php
/**
 * Single database entry point for the whole app.
 */
require_once __DIR__ . '/database/bootstrap.php';

$conn = evsu_db_connect();
