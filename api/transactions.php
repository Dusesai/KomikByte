<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

require_method('GET');
$userId = require_user_id();
try {
    $statement = db()->prepare('SELECT wt.id, wt.type, wt.amount, wt.balance_before, wt.balance_after, wt.status, wt.created_at,
                                       wt.chapter_id, ch.chapter_number, ch.title AS chapter_title, c.title AS comic_title
                                FROM wallet_transactions wt
                                LEFT JOIN chapters ch ON ch.id = wt.chapter_id
                                LEFT JOIN comics c ON c.id = ch.comic_id
                                WHERE wt.user_id = ?
                                ORDER BY wt.created_at DESC, wt.id DESC');
    $statement->execute([$userId]);
    $transactions = array_map(static fn(array $row): array => [
        'id' => (int) $row['id'], 'type' => $row['type'], 'amount' => (float) $row['amount'],
        'balance_before' => (float) $row['balance_before'], 'balance_after' => (float) $row['balance_after'],
        'status' => $row['status'], 'created_at' => $row['created_at'], 'chapter_id' => $row['chapter_id'] === null ? null : (int) $row['chapter_id'],
        'chapter_label' => $row['chapter_id'] === null ? null : $row['comic_title'] . ' — Ch. ' . $row['chapter_number'] . ': ' . $row['chapter_title'],
    ], $statement->fetchAll());
    respond(true, 'Transaction history retrieved.', 200, ['transactions' => $transactions]);
} catch (Throwable $error) {
    database_error($error);
}
