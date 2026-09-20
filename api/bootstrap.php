<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    // Keep session records inside the project instead of relying on a machine-wide PHP temp folder.
    $sessionPath = __DIR__ . '/../storage/sessions';
    if (!is_dir($sessionPath) && !mkdir($sessionPath, 0770, true) && !is_dir($sessionPath)) {
        throw new RuntimeException('Session storage could not be initialized.');
    }
    session_save_path($sessionPath);
    session_name('komikbyte_session');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');

function respond(bool $success, string $message, int $status = 200, array $data = []): never
{
    http_response_code($status);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data), JSON_PRESERVE_ZERO_FRACTION);
    exit;
}

function require_method(string $method): void
{
    if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $method) {
        respond(false, 'Method not allowed.', 405);
    }
}

function body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        respond(false, 'Invalid JSON request body.', 400);
    }
    return $data;
}

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function require_user_id(): int
{
    $userId = current_user_id();
    if ($userId === null) {
        respond(false, 'Authentication required.', 401);
    }
    return $userId;
}

function input_id(mixed $value, string $label = 'ID'): int
{
    if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
        respond(false, "Valid {$label} is required.", 422);
    }
    return (int) $value;
}

function decimal_amount(mixed $value, string $label = 'Amount'): string
{
    if (!is_string($value) && !is_int($value) && !is_float($value)) {
        respond(false, "{$label} is required.", 422);
    }

    $amount = trim((string) $value);
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $amount)) {
        respond(false, "{$label} must be a positive peso value with up to two decimal places.", 422);
    }

    [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
    $whole = ltrim($whole, '0');
    $whole = $whole === '' ? '0' : $whole;
    $fraction = str_pad($fraction, 2, '0');
    $normalized = $whole . '.' . $fraction;
    if (($whole === '0' && $fraction === '00') || strlen($whole) > 5 || (strlen($whole) === 5 && ($whole > '10000' || ($whole === '10000' && $fraction > '00')))) {
        respond(false, "{$label} must be greater than ₱0.00 and no more than ₱10,000.00.", 422);
    }
    return $normalized;
}

function database_error(Throwable $error): never
{
    error_log('[KomikByte] ' . $error->getMessage());
    respond(false, 'A database error occurred. No changes were saved.', 500);
}
