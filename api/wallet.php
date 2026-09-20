<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

require_method('GET');
$userId = require_user_id();
try {
    $statement = db()->prepare('SELECT wallet_balance FROM users WHERE id = ?');
    $statement->execute([$userId]);
    $user = $statement->fetch();
    if (!$user) {
        respond(false, 'User not found.', 401);
    }
    respond(true, 'Wallet retrieved.', 200, ['wallet_balance' => (float) $user['wallet_balance'], 'currency' => 'PHP', 'simulation_only' => true]);
} catch (Throwable $error) {
    database_error($error);
}
