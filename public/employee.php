<?php
/**
 * Matrix Application - Employee profile (skills timeline)
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/common.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/repositories/LookupRepo.php';
require_once __DIR__ . '/../src/repositories/EventsRepo.php';
require_once __DIR__ . '/../src/services/SkillStatusService.php';

ensureDirs();
initUserSession();
$activeUserId = getActiveUserId();
if (!$activeUserId) {
    http_response_code(401);
    exit('Neautentificat. Trimite user_id în URL.');
}

$userDisplayName = getUserDisplayName($activeUserId);

$angajatId = isset($_GET['angajat_id']) && preg_match('/^[0-9]+$/', (string)$_GET['angajat_id']) ? (int)$_GET['angajat_id'] : 0;
$linieId   = isset($_GET['linie_id']) && preg_match('/^[0-9]+$/', (string)$_GET['linie_id']) ? (int)$_GET['linie_id'] : 0;

if ($angajatId <= 0) { http_response_code(400); exit('angajat_id lipsă'); }

$positions = LookupRepo::getPositions();
$lines = LookupRepo::getLines();
if ($linieId === 0 && count($lines) > 0) $linieId = (int)$lines[0]['id'];

$empRow = dbFetchOne("
    SELECT id,
           COALESCE(NULLIF(denumire,''), NULLIF(nume_complet,''), NULLIF(TRIM(COALESCE(prenume,'') || ' ' || COALESCE(nume,'')), ''), CONCAT('ID ', id::text)) AS denumire
    FROM nom_sal_personal_angajat
    WHERE id = $1
    LIMIT 1
", [$angajatId]) ?: ['id'=>$angajatId,'denumire'=>'(necunoscut)'];

// Latest statuses for this employee on selected line
$latestRows = dbFetchAll("
    SELECT DISTINCT ON (e.pozitie_id)
        e.pozitie_id, e.status_nou, e.actiune, e.facut_la
    FROM dock_skill_matrix_event e
    WHERE e.angajat_id = $1 AND e.linie_id = $2
    ORDER BY e.pozitie_id, e.facut_la DESC, e.id DESC
", [$angajatId, $linieId]);
$map = [];
foreach ($latestRows as $r) $map[(int)$r['pozitie_id']] = $r;
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profil angajat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php?user_id=<?=h($activeUserId)?>">Matrix</a>
    <div class="ms-auto text-muted small">Logat: <strong><?=h($userDisplayName ?: $activeUserId)?></strong></div>
  </div>
</nav>

<div class="container py-3">
  <div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h4 class="mb-1"><?=h((string)$empRow['denumire'])?></h4>
      <div class="text-muted small">Angajat ID: <?=h((string)$angajatId)?> • Linie selectată: <?=h((string)$linieId)?></div>
    </div>
    <form method="get" class="d-flex gap-2">
      <input type="hidden" name="user_id" value="<?=h($activeUserId)?>">
      <input type="hidden" name="angajat_id" value="<?=h((string)$angajatId)?>">
      <select class="form-select" name="linie_id">
        <?php foreach ($lines as $l): ?>
          <option value="<?=h((string)$l['id'])?>" <?=$linieId==(int)$l['id']?'selected':''?>><?=h((string)$l['denumire'])?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-primary">Schimbă linia</button>
      <a class="btn btn-outline-secondary" href="matrix.php?user_id=<?=h($activeUserId)?>&linie_id=<?=h((string)$linieId)?>">Înapoi la matrice</a>
    </form>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <h6 class="mb-2">Status curent pe poziții (linia selectată)</h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
          <thead>
            <tr>
              <th>Poziție</th>
              <th>Status</th>
              <th>Ultima acțiune</th>
              <th>Data</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($positions as $p):
              $pid = (int)$p['id'];
              $r = $map[$pid] ?? null;
              $st = $r['status_nou'] ?? null;
          ?>
            <tr>
              <td><?=h((string)$p['denumire'])?></td>
              <td><span class="<?=h(SkillStatusService::cssClassForStatus($st))?>"><?=h(SkillStatusService::labelForStatus($st))?></span></td>
              <td><?=h((string)($r['actiune'] ?? ''))?></td>
              <td><?=h((string)($r['facut_la'] ?? ''))?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="text-muted small">Detalii/timeline pe o poziție se vede în matrice (click pe celulă).</div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
