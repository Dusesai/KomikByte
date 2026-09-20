<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

require_method('POST');
$userId = require_user_id();
$amount = decimal_amount(body()['amount'] ?? null, 'Top-up amount');

try {
    $pdo = db();
    $pdo->beginTransaction();
    $wallet = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE');
    $wallet->execute([$userId]);
    $user = $wallet->fetch();
    if (!$user) {
        $pdo->rollBack();
        respond(false, 'User not found.', 401);
    }

    $before = $user['wallet_balance'];
    // Let MySQL perform the DECIMAL arithmetic; PHP never calculates a wallet balance.
    $update = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?');
    $update->execute([$amount, $userId]);
    $balanceQuery = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
    $balanceQuery->execute([$userId]);
    $after = $balanceQuery->fetchColumn();
    $record = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, chapter_id, status) VALUES (?, 'TOP_UP', ?, ?, ?, NULL, 'SUCCESS')");
    $record->execute([$userId, $amount, $before, $after]);
    $pdo->commit();

    respond(true, 'Simulated funds added successfully.', 200, ['amount' => (float) $amount, 'balance_before' => (float) $before, 'balance_after' => (float) $after, 'simulation_only' => true]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    database_error($error);
}
