<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../src/common.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/repositories/LookupRepo.php';
require_once __DIR__ . '/../src/repositories/EventsRepo.php';
require_once __DIR__ . '/../src/services/SkillStatusService.php';

ensureDirs();
initUserSession();

header('Content-Type: application/json; charset=utf-8');

$activeUserId = getActiveUserId();
if (!$activeUserId) {
    http_response_code(401);
    echo json_encode(['error' => 'Neautentificat (lipsește user_id).'], JSON_UNESCAPED_UNICODE);
    exit;
}

function bad(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $angajatId = isset($_GET['angajat_id']) ? (int)$_GET['angajat_id'] : 0;
        $pozitieId = isset($_GET['pozitie_id']) ? (int)$_GET['pozitie_id'] : 0;
        $linieId   = isset($_GET['linie_id']) ? (int)$_GET['linie_id'] : 0;

        if ($angajatId <= 0 || $pozitieId <= 0 || $linieId <= 0) {
            bad('Parametri lipsă: angajat_id, pozitie_id, linie_id.');
        }

        $items = EventsRepo::listEvents($angajatId, $pozitieId, $linieId);
        echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        checkCsrf();

        $angajatId = isset($_POST['angajat_id']) ? (int)$_POST['angajat_id'] : 0;
        $pozitieId = isset($_POST['pozitie_id']) ? (int)$_POST['pozitie_id'] : 0;
        $linieId   = isset($_POST['linie_id']) ? (int)$_POST['linie_id'] : 0;
        $actiune   = SkillStatusService::normalizeAction((string)($_POST['actiune'] ?? ''));
        $statusNou = SkillStatusService::normalizeStatus((string)($_POST['status_nou'] ?? ''));
        $motiv     = trim((string)($_POST['motiv'] ?? ''));
        $obs       = trim((string)($_POST['observatii'] ?? ''));

        if ($angajatId <= 0 || $pozitieId <= 0 || $linieId <= 0) bad('ID invalid.');
        if ($actiune === null) bad('Acțiune invalidă.');
        if ($statusNou === null) bad('Status invalid.');

        if (mb_strlen($motiv) > 255) bad('Motiv prea lung (max 255).');
        if (mb_strlen($obs) > 500) bad('Observații prea lungi (max 500).');

        $skillDetId = LookupRepo::getSkillDetId($pozitieId, $linieId);
        if ($skillDetId === null) {
            bad('Nu există doc_skill_matrix_det pentru combinația poziție/linie. (Trebuie definit requirement-ul înainte.)');
        }

        $payload = [
            'skill_det_id' => $skillDetId,
            'angajat_id'   => $angajatId,
            'pozitie_id'   => $pozitieId,
            'linie_id'     => $linieId,
            'actiune'      => $actiune,
            'status_nou'   => $statusNou,
            'motiv'        => ($motiv === '') ? null : $motiv,
            'observatii'   => ($obs === '') ? null : $obs,
            'facut_de'     => (int)$activeUserId,
        ];

        $id = EventsRepo::insertEvent($payload);
        echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
        exit;
    }

    bad('Metodă neacceptată', 405);

} catch (Throwable $e) {
    bad($e->getMessage(), 500);
}
