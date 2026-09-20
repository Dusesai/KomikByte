<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

require_method('POST');
$payload = body();
$username = trim((string) ($payload['username'] ?? ''));
$email = strtolower(trim((string) ($payload['email'] ?? '')));
$password = (string) ($payload['password'] ?? '');

if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
    respond(false, 'Username must be 3–30 letters, numbers, or underscores.', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    respond(false, 'A valid email address is required.', 422);
}
if (strlen($password) < 8 || strlen($password) > 72) {
    respond(false, 'Password must be 8–72 characters.', 422);
}

try {
    $pdo = db();
    $check = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
    $check->execute([$username, $email]);
    if ($check->fetch()) {
        respond(false, 'That username or email is already registered.', 409);
    }

    $insert = $pdo->prepare('INSERT INTO users (username, email, password_hash, wallet_balance) VALUES (?, ?, ?, 0.00)');
    $insert->execute([$username, $email, password_hash($password, PASSWORD_DEFAULT)]);
    $id = (int) $pdo->lastInsertId();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['username'] = $username;

    respond(true, 'Account created successfully.', 201, ['user' => ['id' => $id, 'username' => $username, 'email' => $email, 'wallet_balance' => 0.00]]);
} catch (Throwable $error) {
    database_error($error);
}
