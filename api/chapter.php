<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

require_method('GET');
$chapterId = input_id($_GET['id'] ?? null, 'chapter ID');
try {
    $query = db()->prepare('SELECT ch.id, ch.chapter_number, ch.title, ch.content, ch.price,
                                   c.id AS comic_id, c.title AS comic_title,
                                   CASE WHEN ca.id IS NOT NULL THEN 1 ELSE 0 END AS purchased
                            FROM chapters ch
                            INNER JOIN comics c ON c.id = ch.comic_id
                            LEFT JOIN chapter_access ca ON ca.chapter_id = ch.id AND ca.user_id = ?
                            WHERE ch.id = ?');
    $query->execute([current_user_id() ?? 0, $chapterId]);
    $chapter = $query->fetch();
    if (!$chapter) {
        respond(false, 'Chapter not found.', 404);
    }

    $isFree = (float) $chapter['price'] === 0.0;
    $canRead = $isFree || (bool) $chapter['purchased'];
    respond(true, $canRead ? 'Chapter retrieved.' : 'This chapter is locked.', 200, ['chapter' => [
        'id' => (int) $chapter['id'], 'comic_id' => (int) $chapter['comic_id'], 'comic_title' => $chapter['comic_title'],
        'chapter_number' => (float) $chapter['chapter_number'], 'title' => $chapter['title'], 'price' => (float) $chapter['price'],
        'is_free' => $isFree, 'purchased' => (bool) $chapter['purchased'], 'can_read' => $canRead,
        'content' => $canRead ? $chapter['content'] : null,
    ]]);
} catch (Throwable $error) {
    database_error($error);
}
