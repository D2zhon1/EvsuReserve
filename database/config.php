<?php
/**
 * Database credentials — edit when deploying to another PC/server.
 * Copy to config.local.php to override without editing this file.
 */
$config = [
    'host'     => getenv('EVSU_DB_HOST') ?: 'localhost',
    'username' => getenv('EVSU_DB_USER') ?: 'root',
    'password' => getenv('EVSU_DB_PASS') ?: '',
    'database' => getenv('EVSU_DB_NAME') ?: 'evsu_reserve',
];

$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $local = require $localFile;
    if (is_array($local)) {
        $config = array_merge($config, $local);
    }
}

return $config;
