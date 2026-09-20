<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

require_method('POST');
$userId = require_user_id();
$chapterId = input_id(body()['chapter_id'] ?? null, 'chapter ID');

try {
    $pdo = db();
    $pdo->beginTransaction();

    // Serializes all monetary operations for this user, including requests from other tabs.
    $wallet = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ? FOR UPDATE');
    $wallet->execute([$userId]);
    $user = $wallet->fetch();
    if (!$user) {
        $pdo->rollBack();
        respond(false, 'User not found.', 401);
    }

    // Lock the chapter row too, so a future admin price edit cannot interleave with this purchase.
    $chapterQuery = $pdo->prepare('SELECT id, price FROM chapters WHERE id = ? FOR UPDATE');
    $chapterQuery->execute([$chapterId]);
    $chapter = $chapterQuery->fetch();
    if (!$chapter) {
        $pdo->rollBack();
        respond(false, 'Chapter not found.', 404);
    }
    if ((string) $chapter['price'] === '0.00') {
        $pdo->rollBack();
        respond(false, 'Free chapters do not require a purchase.', 409);
    }

    $accessCheck = $pdo->prepare('SELECT id FROM chapter_access WHERE user_id = ? AND chapter_id = ? LIMIT 1');
    $accessCheck->execute([$userId, $chapterId]);
    if ($accessCheck->fetch()) {
        $pdo->rollBack();
        respond(false, 'Chapter already unlocked.', 409);
    }

    $before = $user['wallet_balance'];
    $price = $chapter['price'];
    // The conditional predicate is a second guard against a negative balance.
    $deduct = $pdo->prepare('UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ? AND wallet_balance >= ?');
    $deduct->execute([$price, $userId, $price]);
    if ($deduct->rowCount() !== 1) {
        $pdo->rollBack();
        respond(false, 'Insufficient wallet balance.', 422);
    }
    $balanceQuery = $pdo->prepare('SELECT wallet_balance FROM users WHERE id = ?');
    $balanceQuery->execute([$userId]);
    $after = $balanceQuery->fetchColumn();

    $grant = $pdo->prepare('INSERT INTO chapter_access (user_id, chapter_id) VALUES (?, ?)');
    $grant->execute([$userId, $chapterId]);
    $record = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, chapter_id, status) VALUES (?, 'CHAPTER_PURCHASE', ?, ?, ?, ?, 'SUCCESS')");
    $record->execute([$userId, $price, $before, $after, $chapterId]);
    $pdo->commit();

    respond(true, 'Chapter purchased successfully.', 200, ['chapter_id' => $chapterId, 'amount' => (float) $price, 'balance_before' => (float) $before, 'balance_after' => (float) $after]);
} catch (Throwable $error) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // A duplicate unique-key failure still means the request was safely not charged here.
    if ($error instanceof PDOException && $error->getCode() === '23000') {
        respond(false, 'Chapter already unlocked.', 409);
    }
    database_error($error);
}
