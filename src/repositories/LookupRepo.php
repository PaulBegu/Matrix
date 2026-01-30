<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';

final class LookupRepo {

    public static function getLines(): array {
        $sql = "SELECT id, denumire FROM nom_productie_linii ORDER BY denumire";
        return dbFetchAll($sql);
    }

    public static function getPositions(): array {
        $sql = "SELECT id, denumire FROM doc_pozitii_skill_matrix ORDER BY denumire";
        return dbFetchAll($sql);
    }

    public static function getEmployees(string $q = ''): array {
        // Try to be robust to different column names used in your DB.
        $sql = "
            SELECT
                id,
                COALESCE(
                    NULLIF(denumire,''),
                    NULLIF(nume_complet,''),
                    NULLIF(TRIM(COALESCE(prenume,'') || ' ' || COALESCE(nume,'')), ''),
                    CONCAT('ID ', id::text)
                ) AS denumire
            FROM nom_sal_personal_angajat
            WHERE ($1 = '' OR
                   COALESCE(denumire, nume_complet, prenume, '') ILIKE '%'||$1||'%' OR
                   COALESCE(nume,'') ILIKE '%'||$1||'%')
            ORDER BY denumire
            LIMIT 500
        ";
        return dbFetchAll($sql, [$q]);
    }

    public static function getSkillDetId(int $pozitieId, int $linieId): ?int {
        $sql = "
            SELECT id
            FROM doc_skill_matrix_det
            WHERE pozitie_id = $1 AND linie_id = $2
            ORDER BY id
            LIMIT 1
        ";
        $row = dbFetchOne($sql, [$pozitieId, $linieId]);
        if (!$row) return null;
        return (int)$row['id'];
    }
}
