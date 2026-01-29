<?php
declare(strict_types=1);

const CONFIG_FILE = __DIR__ . '/../config/config.json';
const LOGS_DIR    = __DIR__ . '/../logs';

function ensureDirs(): void {
    if (!is_dir(LOGS_DIR)) mkdir(LOGS_DIR, 0775, true);
}

function loadConfig(): array {
    if (!file_exists(CONFIG_FILE)) {
        $defaults = [
            'app_name' => 'Matrix',
            'version' => '1.0.0',
            'default_user_id' => ''
        ];
        file_put_contents(CONFIG_FILE, json_encode($defaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $defaults;
    }
    $json = file_get_contents(CONFIG_FILE);
    return json_decode($json ?: '[]', true) ?: [];
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function checkCsrf(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
        http_response_code(400);
        exit('Invalid CSRF token');
    }
}
