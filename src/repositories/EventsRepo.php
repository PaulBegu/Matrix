<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';

final class EventsRepo {

    public static function listEvents(int $angajatId, int $pozitieId, int $linieId): array {
        $sql = "
            SELECT
                e.id,
                e.actiune,
                e.status_nou,
                e.motiv,
                e.observatii,
                e.facut_la,
                e.facut_de,
                COALESCE(u.denumire, ('UserID: '||e.facut_de::text)) AS facut_de_name
            FROM dock_skill_matrix_event e
            LEFT JOIN sys_users u ON u.id = e.facut_de
            WHERE e.angajat_id = $1
              AND e.pozitie_id = $2
              AND e.linie_id   = $3
            ORDER BY e.facut_la DESC, e.id DESC
            LIMIT 200
        ";
        return dbFetchAll($sql, [$angajatId, $pozitieId, $linieId]);
    }

    public static function insertEvent(array $payload): int {
        $sql = "
            INSERT INTO dock_skill_matrix_event
                (skill_det_id, angajat_id, pozitie_id, linie_id,
                 actiune, status_nou, motiv, observatii, facut_de)
            VALUES
                ($1,$2,$3,$4,$5,$6,$7,$8,$9)
            RETURNING id
        ";
        $row = dbFetchOne($sql, [
            $payload['skill_det_id'],
            $payload['angajat_id'],
            $payload['pozitie_id'],
            $payload['linie_id'],
            $payload['actiune'],
            $payload['status_nou'],
            $payload['motiv'],
            $payload['observatii'],
            $payload['facut_de']
        ]);
        if (!$row) {
            throw new RuntimeException('Insert failed.');
        }
        return (int)$row['id'];
    }

    public static function latestStatusMapForLine(int $linieId): array {
        // Returns rows: angajat_id, pozitie_id, status_nou, actiune, facut_la
        $sql = "
            SELECT DISTINCT ON (e.angajat_id, e.pozitie_id)
                e.angajat_id,
                e.pozitie_id,
                e.status_nou,
                e.actiune,
                e.facut_la
            FROM dock_skill_matrix_event e
            WHERE e.linie_id = $1
            ORDER BY e.angajat_id, e.pozitie_id, e.facut_la DESC, e.id DESC
        ";
        return dbFetchAll($sql, [$linieId]);
    }
}
