<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Small DB helpers (pg_query_params wrappers).
 * We keep it tiny (no framework) but consistent.
 */

function dbConn(): \PgSql\Connection {
    // Skill Matrix tables are in SMW database in this project.
    // If you move them to another DB later, change this to DB::getConnMatrix().
    $conn = DB::getConnSmw();
    if (!$conn) {
        throw new RuntimeException('Database connection failed.');
    }
    return $conn;
}

function dbQuery(string $sql, array $params = []): \PgSql\Result {
    $conn = dbConn();
    $res = @pg_query_params($conn, $sql, $params);
    if ($res === false) {
        $err = pg_last_error($conn) ?: 'Unknown DB error';
        throw new RuntimeException($err);
    }
    return $res;
}

function dbFetchAll(string $sql, array $params = []): array {
    $res = dbQuery($sql, $params);
    $rows = [];
    while ($row = pg_fetch_assoc($res)) {
        $rows[] = $row;
    }
    return $rows;
}

function dbFetchOne(string $sql, array $params = []): ?array {
    $res = dbQuery($sql, $params);
    $row = pg_fetch_assoc($res);
    return $row ?: null;
}
