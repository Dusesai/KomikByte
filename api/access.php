<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

require_method('GET');
$userId = require_user_id();
try {
    $statement = db()->prepare('SELECT ch.id, ch.chapter_number, ch.title, ch.price, c.id AS comic_id, c.title AS comic_title, ca.unlocked_at
                                FROM chapter_access ca
                                INNER JOIN chapters ch ON ch.id = ca.chapter_id
                                INNER JOIN comics c ON c.id = ch.comic_id
                                WHERE ca.user_id = ?
                                ORDER BY ca.unlocked_at DESC');
    $statement->execute([$userId]);
    $chapters = array_map(static fn(array $row): array => [
        'id' => (int) $row['id'], 'comic_id' => (int) $row['comic_id'], 'comic_title' => $row['comic_title'],
        'chapter_number' => (float) $row['chapter_number'], 'title' => $row['title'], 'price' => (float) $row['price'], 'unlocked_at' => $row['unlocked_at'],
    ], $statement->fetchAll());
    respond(true, 'Purchased chapters retrieved.', 200, ['chapters' => $chapters]);
} catch (Throwable $error) {
    database_error($error);
}
