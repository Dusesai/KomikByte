<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (current_user_id() === null) {
    respond(true, 'Guest session.', 200, ['authenticated' => false, 'user' => null]);
}

try {
    $statement = db()->prepare('SELECT id, username, email, wallet_balance FROM users WHERE id = ?');
    $statement->execute([current_user_id()]);
    $user = $statement->fetch();
    if (!$user) {
        $_SESSION = [];
        session_destroy();
        respond(true, 'Guest session.', 200, ['authenticated' => false, 'user' => null]);
    }
    respond(true, 'Authenticated session.', 200, ['authenticated' => true, 'user' => ['id' => (int) $user['id'], 'username' => $user['username'], 'email' => $user['email'], 'wallet_balance' => (float) $user['wallet_balance']]]);
} catch (Throwable $error) {
    database_error($error);
}
