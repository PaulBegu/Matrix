<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../src/common.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/repositories/LookupRepo.php';

ensureDirs();
initUserSession();

header('Content-Type: application/json; charset=utf-8');

$activeUserId = getActiveUserId();
if (!$activeUserId) {
    http_response_code(401);
    echo json_encode(['error' => 'Neautentificat (lipsește user_id).'], JSON_UNESCAPED_UNICODE);
    exit;
}

$type = trim((string)($_GET['type'] ?? ''));
$q    = trim((string)($_GET['q'] ?? ''));

try {
    if ($type === 'lines') {
        echo json_encode(['items' => LookupRepo::getLines()], JSON_UNESCAPED_UNICODE); exit;
    }
    if ($type === 'positions') {
        echo json_encode(['items' => LookupRepo::getPositions()], JSON_UNESCAPED_UNICODE); exit;
    }
    if ($type === 'employees') {
        echo json_encode(['items' => LookupRepo::getEmployees($q)], JSON_UNESCAPED_UNICODE); exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'type invalid. Folosește: lines | positions | employees'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
