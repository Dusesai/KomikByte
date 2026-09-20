<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

require_method('GET');
$comicId = input_id($_GET['id'] ?? null, 'comic ID');
try {
    $pdo = db();
    $comicQuery = $pdo->prepare('SELECT id, title, description, author, cover_image, status, created_at FROM comics WHERE id = ?');
    $comicQuery->execute([$comicId]);
    $comic = $comicQuery->fetch();
    if (!$comic) {
        respond(false, 'Comic not found.', 404);
    }

    $userId = current_user_id() ?? 0;
    $chapters = $pdo->prepare('SELECT ch.id, ch.chapter_number, ch.title, ch.price, ch.created_at,
                            CASE WHEN ch.price = 0 THEN 1 WHEN ca.id IS NOT NULL THEN 1 ELSE 0 END AS can_read,
                            CASE WHEN ca.id IS NOT NULL THEN 1 ELSE 0 END AS purchased
                            FROM chapters ch
                            LEFT JOIN chapter_access ca ON ca.chapter_id = ch.id AND ca.user_id = ?
                            WHERE ch.comic_id = ?
                            ORDER BY ch.chapter_number ASC, ch.id ASC');
    $chapters->execute([$userId, $comicId]);
    $chapterList = array_map(static fn(array $chapter): array => [
        'id' => (int) $chapter['id'],
        'chapter_number' => (float) $chapter['chapter_number'],
        'title' => $chapter['title'],
        'price' => (float) $chapter['price'],
        'is_free' => (float) $chapter['price'] === 0.0,
        'can_read' => (bool) $chapter['can_read'],
        'purchased' => (bool) $chapter['purchased'],
    ], $chapters->fetchAll());

    respond(true, 'Comic retrieved.', 200, ['comic' => [
        'id' => (int) $comic['id'], 'title' => $comic['title'], 'description' => $comic['description'],
        'author' => $comic['author'], 'cover_image' => $comic['cover_image'], 'status' => $comic['status'],
        'chapters' => $chapterList,
    ]]);
} catch (Throwable $error) {
    database_error($error);
}
