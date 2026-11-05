<?php
/**
 * SSRINI Handcrafts - Centralized Environment Loader
 * Robust path detection across standalone and nested setups with memoization.
 */
function get_app_env(): array {
    static $env = null;
    if ($env !== null) {
        return $env;
    }

    $candidates = [
        __DIR__ . '/../../.env',              // Ssrini_Project_Master/.env
        __DIR__ . '/../../../.env',           // client/.env
        dirname(__DIR__, 2) . '/.env',
        dirname(__DIR__, 3) . '/.env',
        ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/.env',
        ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/../.env'
    ];

    foreach ($candidates as $path) {
        if (!empty($path) && file_exists($path) && is_readable($path)) {
            $parsed = @parse_ini_file($path);
            if ($parsed !== false && is_array($parsed)) {
                $env = $parsed;
                return $env;
            }
        }
    }

    $env = [];
    return $env;
}
