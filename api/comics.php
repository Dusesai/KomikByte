<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

require_method('GET');
$search = trim((string) ($_GET['search'] ?? ''));
try {
    $sql = 'SELECT c.id, c.title, c.description, c.author, c.cover_image, c.status, c.created_at,
                   COUNT(ch.id) AS chapter_count,
                   MIN(CASE WHEN ch.price > 0 THEN ch.price END) AS starting_price
            FROM comics c
            LEFT JOIN chapters ch ON ch.comic_id = c.id';
    $params = [];
    if ($search !== '') {
        $sql .= ' WHERE c.title LIKE ? OR c.author LIKE ?';
        $term = '%' . $search . '%';
        $params = [$term, $term];
    }
    $sql .= ' GROUP BY c.id ORDER BY c.created_at DESC, c.id DESC';
    $statement = db()->prepare($sql);
    $statement->execute($params);
    $comics = array_map(static fn(array $comic): array => [
        'id' => (int) $comic['id'],
        'title' => $comic['title'],
        'description' => $comic['description'],
        'author' => $comic['author'],
        'cover_image' => $comic['cover_image'],
        'status' => $comic['status'],
        'chapter_count' => (int) $comic['chapter_count'],
        'starting_price' => $comic['starting_price'] === null ? null : (float) $comic['starting_price'],
    ], $statement->fetchAll());
    respond(true, 'Comics retrieved.', 200, ['comics' => $comics]);
} catch (Throwable $error) {
    database_error($error);
}
