<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../src/common.php';
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/repositories/LookupRepo.php';

initUserSession();
$activeUserId = getActiveUserId();
if ($activeUserId === null) {
    http_response_code(401);
    exit('Nu ești autentificat. Accesează cu ?user_id=...');
}

$userName = getUserDisplayName($activeUserId);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkCsrf();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $linieId = (int)($_POST['linie_id'] ?? 0);
        $pozitieId = (int)($_POST['pozitie_id'] ?? 0);
        
        if ($linieId > 0 && $pozitieId > 0) {
            // Check if already exists
            $existing = LookupRepo::getSkillDetId($pozitieId, $linieId);
            if ($existing === null) {
                // Get next ID from sequence
                $nextId = dbFetchOne("SELECT nextval('seq_doc_skill_matrix_det') AS id");
                $newId = (int)$nextId['id'];
                
                // Insert new requirement
                dbQuery("
                    INSERT INTO doc_skill_matrix_det 
                        (id, id_parinte, nom_productie_linii_id, pozitii_de_lucru, creat_de, status)
                    VALUES ($1, 1, $2, $3, $4, 'Activ')
                ", [$newId, $linieId, $pozitieId, (int)$activeUserId]);
                
                $_SESSION['msg'] = 'Requirement adăugat cu succes!';
            } else {
                $_SESSION['msg'] = 'Această combinație există deja.';
            }
        }
    } elseif ($action === 'delete') {
        $detId = (int)($_POST['det_id'] ?? 0);
        if ($detId > 0) {
            dbQuery("DELETE FROM doc_skill_matrix_det WHERE id = $1", [$detId]);
            $_SESSION['msg'] = 'Requirement șters cu succes!';
        }
    } elseif ($action === 'add_all') {
        $linieId = (int)($_POST['linie_id_all'] ?? 0);
        if ($linieId > 0) {
            $positions = LookupRepo::getPositions();
            $added = 0;
            foreach ($positions as $pos) {
                $pozitieId = (int)$pos['id'];
                $existing = LookupRepo::getSkillDetId($pozitieId, $linieId);
                if ($existing === null) {
                    $nextId = dbFetchOne("SELECT nextval('seq_doc_skill_matrix_det') AS id");
                    $newId = (int)$nextId['id'];
                    dbQuery("
                        INSERT INTO doc_skill_matrix_det 
                            (id, id_parinte, nom_productie_linii_id, pozitii_de_lucru, creat_de, status)
                        VALUES ($1, 1, $2, $3, $4, 'Activ')
                    ", [$newId, $linieId, $pozitieId, (int)$activeUserId]);
                    $added++;
                }
            }
            $_SESSION['msg'] = "Adăugate $added requirement-uri pentru linia selectată.";
        }
    }
    
    header('Location: admin_requirements.php');
    exit;
}

$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

// Get all requirements
$requirements = dbFetchAll("
    SELECT 
        d.id,
        d.nom_productie_linii_id,
        d.pozitii_de_lucru,
        l.denumire AS linie_nume,
        p.denumire AS pozitie_nume,
        d.creat_la,
        u.denumire AS creat_de_nume
    FROM doc_skill_matrix_det d
    LEFT JOIN nom_productie_linii l ON l.id = d.nom_productie_linii_id
    LEFT JOIN doc_pozitii_skill_matrix p ON p.id = d.pozitii_de_lucru
    LEFT JOIN sys_users u ON u.id = d.creat_de
    ORDER BY l.denumire, p.denumire
");

$lines = LookupRepo::getLines();
$positions = LookupRepo::getPositions();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrare Requirements - Matrix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .container { max-width: 1400px; }
        .card { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Administrare Requirements (Poziție × Linie)</h1>
            <div>
                <span class="badge bg-primary"><?=h($userName)?></span>
                <a href="index.php" class="btn btn-secondary btn-sm ms-2">← Înapoi</a>
            </div>
        </div>

        <?php if ($msg): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <?=h($msg)?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Adaugă Requirement Individual</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?=h(csrfToken())?>">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="mb-3">
                                <label class="form-label">Linie Producție</label>
                                <select name="linie_id" class="form-select" required>
                                    <option value="">-- Selectează --</option>
                                    <?php foreach ($lines as $l): ?>
                                    <option value="<?=h((string)$l['id'])?>"><?=h((string)$l['denumire'])?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Poziție</label>
                                <select name="pozitie_id" class="form-select" required>
                                    <option value="">-- Selectează --</option>
                                    <?php foreach ($positions as $p): ?>
                                    <option value="<?=h((string)$p['id'])?>"><?=h((string)$p['denumire'])?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">Adaugă</button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Adaugă Toate Pozițiile pentru o Linie</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf" value="<?=h(csrfToken())?>">
                            <input type="hidden" name="action" value="add_all">
                            
                            <div class="mb-3">
                                <label class="form-label">Linie Producție</label>
                                <select name="linie_id_all" class="form-select" required>
                                    <option value="">-- Selectează --</option>
                                    <?php foreach ($lines as $l): ?>
                                    <option value="<?=h((string)$l['id'])?>"><?=h((string)$l['denumire'])?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Adaugă Toate Pozițiile</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Requirements Existente (<?=count($requirements)?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Linie</th>
                                        <th>Poziție</th>
                                        <th>Creat La</th>
                                        <th>Creat De</th>
                                        <th>Acțiuni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($requirements)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Nu există requirements definite.</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($requirements as $r): ?>
                                        <tr>
                                            <td><?=h((string)$r['id'])?></td>
                                            <td><?=h((string)$r['linie_nume'])?></td>
                                            <td><?=h((string)$r['pozitie_nume'])?></td>
                                            <td><?=h((string)($r['creat_la'] ?? ''))?></td>
                                            <td><?=h((string)($r['creat_de_nume'] ?? ''))?></td>
                                            <td>
                                                <form method="post" style="display:inline;" onsubmit="return confirm('Sigur ștergi acest requirement?');">
                                                    <input type="hidden" name="csrf" value="<?=h(csrfToken())?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="det_id" value="<?=h((string)$r['id'])?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">Șterge</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
