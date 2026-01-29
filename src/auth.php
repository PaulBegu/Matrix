<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Preia și validează user_id din GET, îl memorează în sesiune
 */
function initUserSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Preluăm user_id din GET și îl memorăm în sesiune
    if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
        $getUserId = trim((string)$_GET['user_id']);
        if (preg_match('/^[0-9]+$/', $getUserId)) {
            $_SESSION['user_id'] = $getUserId;
        }
    }
}

/**
 * Returnează user_id din sesiune sau null dacă nu există
 */
function getActiveUserId(): ?string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_id'] ?? null;
}

/**
 * Lookup user's display name from SMW sys_users by ID
 * Returns empty string if not found
 */
function getUserDisplayName(string $userId): string {
    try {
        $conn = DB::getConnSmw();
        if (!$conn) {
            return '';
        }
        $sql = "SELECT denumire AS d FROM sys_users WHERE id = $1 LIMIT 1";
        $res = @pg_query_params($conn, $sql, [$userId]);
        if ($res !== false) {
            $row = pg_fetch_assoc($res) ?: [];
            $d = trim((string)($row['d'] ?? ''));
            if ($d !== '') {
                return $d;
            }
        }
    } catch (\Throwable $e) {
        // ignore lookup failures
    }
    return '';
}

/**
 * Verifică dacă utilizatorul este autentificat
 */
function isAuthenticated(): bool {
    return getActiveUserId() !== null;
}
