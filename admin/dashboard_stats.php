<?php
declare(strict_types=1);

require_once __DIR__ . '/_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (auth_user() === null) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'message' => 'Unauthorized',
    ]);
    $db->close();
    exit;
}

try {
    $summary = admin_get_today_visit_summary($db);
    echo json_encode([
        'ok' => true,
        'date' => (string) $summary['date'],
        'total' => (int) $summary['total'],
        'total_mahasiswa' => (int) $summary['total_mahasiswa'],
        'total_guest' => (int) $summary['total_guest'],
        'server_time' => date('Y-m-d H:i:s'),
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'message' => 'Failed to load dashboard stats.',
    ]);
}

$db->close();
