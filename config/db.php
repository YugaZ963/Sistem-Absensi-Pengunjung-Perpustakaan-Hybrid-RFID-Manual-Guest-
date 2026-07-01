<?php
declare(strict_types=1);

$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_NAME = 'db_absensi_perpus';
$DB_USER = 'root';
$DB_PASS = '';

date_default_timezone_set('Asia/Jakarta');

function db_connect(): mysqli
{
    global $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS;

    $connection = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

    if ($connection->connect_error) {
        throw new RuntimeException('Database connection failed: ' . $connection->connect_error);
    }

    $connection->set_charset('utf8mb4');
    $connection->query("SET time_zone = '+07:00'");

    return $connection;
}
