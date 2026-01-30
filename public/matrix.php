<?php
/**
 * Matrix Application - Skill Matrix
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
$csrf = csrfToken();

$linieId = isset($_GET['linie_id']) && preg_match('/^[0-9]+$/', (string)$_GET['linie_id']) ? (int)$_GET['linie_id'] : 0;
$q = trim((string)($_GET['q'] ?? ''));

$lines = LookupRepo::getLines();
if ($linieId === 0 && count($lines) > 0) {
    $linieId = (int)$lines[0]['id'];
}

$positions = LookupRepo::getPositions();
$employees = LookupRepo::getEmployees($q);

// Build status map for selected line
$statusRows = $linieId ? EventsRepo::latestStatusMapForLine($linieId) : [];
$statusMap = []; // [angajat_id][pozitie_id] => ['status_nou'=>..,'actiune'=>..,'facut_la'=>..]
foreach ($statusRows as $r) {
    $aid = (int)$r['angajat_id'];
    $pid = (int)$r['pozitie_id'];
    $statusMap[$aid][$pid] = $r;
}
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Skill Matrix</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .matrix-table { font-size: 0.92rem; }
    .matrix-table th { position: sticky; top: 0; z-index: 2; background: #fff; }
    .matrix-table th.sticky-col, .matrix-table td.sticky-col { position: sticky; left: 0; z-index: 1; background: #fff; }
    .matrix-table th.sticky-col { z-index: 3; }
    .cell-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: .35rem; }
    .cell-btn small { opacity: .7; }
    .table-responsive { max-height: 75vh; }
    .nowrap { white-space: nowrap; }
  </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php?user_id=<?=h($activeUserId)?>">Matrix</a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <div class="text-muted small">Logat: <strong><?=h($userDisplayName ?: $activeUserId)?></strong></div>
    </div>
  </div>
</nav>

<div class="container-fluid py-3">
  <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-3">
    <div>
      <h4 class="mb-1">Skill Matrix</h4>
      <div class="text-muted small">Linie selectată + status curent (ultimul eveniment).</div>
    </div>

    <form class="d-flex gap-2" method="get" action="matrix.php">
      <input type="hidden" name="user_id" value="<?=h($activeUserId)?>">
      <select class="form-select" name="linie_id" style="min-width: 240px;">
        <?php foreach ($lines as $l): ?>
          <option value="<?=h((string)$l['id'])?>" <?=$linieId==(int)$l['id']?'selected':''?>>
            <?=h((string)$l['denumire'])?>
          </option>
        <?php endforeach; ?>
      </select>
      <input class="form-control" name="q" value="<?=h($q)?>" placeholder="Caută angajat (max 500)">
      <button class="btn btn-primary">Aplică</button>
    </form>
  </div>

  <div class="card shadow-sm">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle matrix-table">
          <thead>
            <tr>
              <th class="sticky-col nowrap">Angajat</th>
              <?php foreach ($positions as $p): ?>
                <th class="text-center nowrap"><?=h((string)$p['denumire'])?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($employees as $e): 
              $aid = (int)$e['id'];
          ?>
            <tr>
              <td class="sticky-col">
                <a class="text-decoration-none" href="employee.php?user_id=<?=h($activeUserId)?>&linie_id=<?=h((string)$linieId)?>&angajat_id=<?=h((string)$aid)?>">
                  <?=h((string)$e['denumire'])?>
                </a>
                <div class="text-muted small">ID: <?=h((string)$aid)?></div>
              </td>
              <?php foreach ($positions as $p):
                    $pid = (int)$p['id'];
                    $cell = $statusMap[$aid][$pid] ?? null;
                    $st = $cell['status_nou'] ?? null;
              ?>
                <td class="text-center">
                  <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm cell-btn"
                    data-angajat-id="<?=h((string)$aid)?>"
                    data-pozitie-id="<?=h((string)$pid)?>"
                    data-linie-id="<?=h((string)$linieId)?>"
                    data-angajat-name="<?=h((string)$e['denumire'])?>"
                    data-pozitie-name="<?=h((string)$p['denumire'])?>"
                  >
                    <span class="<?=h(SkillStatusService::cssClassForStatus($st))?>"><?=h(SkillStatusService::labelForStatus($st))?></span>
                    <?php if ($cell && !empty($cell['facut_la'])): ?>
                      <small><?=h(substr((string)$cell['facut_la'], 0, 10))?></small>
                    <?php endif; ?>
                  </button>
                </td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="text-muted small mt-2">
        Click pe o celulă → vezi istoric + adaugi un eveniment (Start/Final/Validare etc).
      </div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="cellModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-1" id="cellTitle">Detalii</h5>
          <div class="text-muted small" id="cellSubtitle"></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Închide"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12 col-lg-5">
            <div class="card">
              <div class="card-body">
                <h6 class="mb-3">Adaugă eveniment</h6>
                <form id="eventForm">
                  <input type="hidden" name="csrf" value="<?=h($csrf)?>">
                  <input type="hidden" name="angajat_id" id="f_angajat_id">
                  <input type="hidden" name="pozitie_id" id="f_pozitie_id">
                  <input type="hidden" name="linie_id" id="f_linie_id">

                  <label class="form-label">Acțiune</label>
                  <select class="form-select mb-2" name="actiune" id="f_actiune">
                    <?php foreach (SkillStatusService::ACTIONS as $a): ?>
                      <option value="<?=h($a)?>"><?=h($a)?></option>
                    <?php endforeach; ?>
                  </select>

                  <label class="form-label">Status nou</label>
                  <select class="form-select mb-2" name="status_nou" id="f_status_nou">
                    <?php foreach (SkillStatusService::STATUSES as $s): ?>
                      <option value="<?=h($s)?>"><?=h($s)?></option>
                    <?php endforeach; ?>
                  </select>

                  <label class="form-label">Motiv (opțional)</label>
                  <input class="form-control mb-2" name="motiv" id="f_motiv" maxlength="255">

                  <label class="form-label">Observații (opțional)</label>
                  <textarea class="form-control mb-3" name="observatii" id="f_observatii" rows="3" maxlength="500"></textarea>

                  <button class="btn btn-primary w-100" type="submit">Salvează eveniment</button>
                  <div id="formMsg" class="small mt-2"></div>
                </form>
              </div>
            </div>
          </div>

          <div class="col-12 col-lg-7">
            <h6 class="mb-2">Istoric evenimente</h6>
            <div id="eventsList" class="list-group small"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" id="openEmployee" class="btn btn-outline-secondary">Deschide profil angajat</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Închide</button>
      </div>
    </div>
  </div>
</div>

<script>
const ACTIVE_USER_ID = <?=json_encode($activeUserId)?>;

function qs(sel, root=document){ return root.querySelector(sel); }
function qsa(sel, root=document){ return Array.from(root.querySelectorAll(sel)); }

async function fetchJson(url, options={}) {
  const res = await fetch(url, options);
  const txt = await res.text();
  let data;
  try { data = JSON.parse(txt); } catch(e) { throw new Error(txt || ('HTTP ' + res.status)); }
  if (!res.ok) throw new Error(data?.error || ('HTTP ' + res.status));
  return data;
}

function renderEvents(items) {
  const list = qs('#eventsList');
  list.innerHTML = '';
  if (!items || items.length === 0) {
    list.innerHTML = '<div class="text-muted">Nu există evenimente.</div>';
    return;
  }
  for (const ev of items) {
    const el = document.createElement('div');
    el.className = 'list-group-item';
    const when = (ev.facut_la || '').replace('T',' ').slice(0,19);
    el.innerHTML = `
      <div class="d-flex justify-content-between gap-2">
        <div>
          <div><strong>${escapeHtml(ev.actiune || '')}</strong> → ${escapeHtml(ev.status_nou || '')}</div>
          <div class="text-muted">${escapeHtml(ev.motiv || '')}${ev.observatii ? ' • ' + escapeHtml(ev.observatii) : ''}</div>
        </div>
        <div class="text-end text-muted">
          <div>${escapeHtml(when)}</div>
          <div>${escapeHtml(ev.facut_de_name || '')}</div>
        </div>
      </div>`;
    list.appendChild(el);
  }
}

function escapeHtml(s) {
  return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

let currentCtx = null;

async function openCellModal(ctx) {
  currentCtx = ctx;
  qs('#cellTitle').textContent = ctx.angajatName + ' • ' + ctx.pozitieName;
  qs('#cellSubtitle').textContent = 'Linie ID: ' + ctx.linieId + ' | Angajat ID: ' + ctx.angajatId + ' | Poziție ID: ' + ctx.pozitieId;

  qs('#f_angajat_id').value = ctx.angajatId;
  qs('#f_pozitie_id').value = ctx.pozitieId;
  qs('#f_linie_id').value   = ctx.linieId;
  qs('#formMsg').textContent = '';

  qs('#openEmployee').href = `employee.php?user_id=${encodeURIComponent(ACTIVE_USER_ID)}&linie_id=${encodeURIComponent(ctx.linieId)}&angajat_id=${encodeURIComponent(ctx.angajatId)}`;

  const data = await fetchJson(`events_api.php?user_id=${encodeURIComponent(ACTIVE_USER_ID)}&angajat_id=${ctx.angajatId}&pozitie_id=${ctx.pozitieId}&linie_id=${ctx.linieId}`);
  renderEvents(data.items || []);
  const modal = bootstrap.Modal.getOrCreateInstance(qs('#cellModal'));
  modal.show();
}

qsa('.cell-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    openCellModal({
      angajatId: btn.dataset.angajatId,
      pozitieId: btn.dataset.pozitieId,
      linieId: btn.dataset.linieId,
      angajatName: btn.dataset.angajatName,
      pozitieName: btn.dataset.pozitieName
    }).catch(err => alert(err.message));
  });
});

qs('#eventForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  const form = e.currentTarget;
  const fd = new FormData(form);

  try {
    const data = await fetchJson('events_api.php?user_id=' + encodeURIComponent(ACTIVE_USER_ID), {
      method: 'POST',
      body: fd
    });
    qs('#formMsg').className = 'small mt-2 text-success';
    qs('#formMsg').textContent = 'Salvat (id ' + data.id + '). Reîncarc…';

    // reload events + refresh page to update badges (simple MVP)
    const ctx = currentCtx;
    const data2 = await fetchJson(`events_api.php?user_id=${encodeURIComponent(ACTIVE_USER_ID)}&angajat_id=${ctx.angajatId}&pozitie_id=${ctx.pozitieId}&linie_id=${ctx.linieId}`);
    renderEvents(data2.items || []);

    setTimeout(() => window.location.reload(), 500);
  } catch (err) {
    qs('#formMsg').className = 'small mt-2 text-danger';
    qs('#formMsg').textContent = err.message;
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
