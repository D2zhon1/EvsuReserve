<?php
/**
 * Single database entry point for the whole app.
 * Including this file auto-creates the database and tables when missing.
 */
require_once __DIR__ . '/database/bootstrap.php';

$conn = evsu_db_connect();
evsu_run_migrations($conn);
