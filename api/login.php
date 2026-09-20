<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

require_method('POST');
$payload = body();
$identity = trim((string) ($payload['identity'] ?? ''));
$password = (string) ($payload['password'] ?? '');

if ($identity === '' || $password === '') {
    respond(false, 'Email or username and password are required.', 422);
}

try {
    $statement = db()->prepare('SELECT id, username, email, password_hash, wallet_balance FROM users WHERE email = ? OR username = ? LIMIT 1');
    $statement->execute([$identity, $identity]);
    $user = $statement->fetch();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        respond(false, 'Invalid login credentials.', 401);
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = $user['username'];
    respond(true, 'Welcome back.', 200, ['user' => ['id' => (int) $user['id'], 'username' => $user['username'], 'email' => $user['email'], 'wallet_balance' => (float) $user['wallet_balance']]]);
} catch (Throwable $error) {
    database_error($error);
}
