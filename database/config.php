<?php
/**
 * Database credentials — edit when deploying to another PC/server.
 * Environment variables override these defaults (optional).
 */
return [
    'host'     => getenv('EVSU_DB_HOST') ?: 'localhost',
    'username' => getenv('EVSU_DB_USER') ?: 'root',
    'password' => getenv('EVSU_DB_PASS') ?: '',
    'database' => getenv('EVSU_DB_NAME') ?: 'evsu_reserve',
];
