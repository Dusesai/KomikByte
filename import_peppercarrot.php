<?php

/*
 * KomikByte - Pepper & Carrot Importer
 *
 * Existing database structure:
 *
 * chapter_pages:
 * id
 * chapter_id
 * page_number
 * asset_path
 * scene_title
 * narration
 * accent_key
 * created_at
 */

$host = "localhost";
$dbname = "komikbyte";
$username = "root";
$password = "";

$comicId = 18;

$baseFolder = __DIR__ . "/assets/comics/pepper-carrot";
$baseWebPath = "assets/comics/pepper-carrot";

$chapters = [
    1  => "Potion of Flight",
    2  => "Rainbow Potions",
    3  => "The Secret Ingredients",
    4  => "Stroke of Genius",
    5  => "Special Holiday Episode",
    6  => "The Potion Contest",
    7  => "The Wish",
    8  => "Pepper's Birthday Party",
    9  => "The Remedy",
    10 => "Summer Special",
    11 => "The Witches of Chaosah",
    12 => "Autumn Clearout",
    13 => "The Pyjama Party",
    14 => "The Dragon's Tooth",
    15 => "The Crystal Ball",
    16 => "The Sage of the Mountain",
    17 => "A Fresh Start",
    18 => "The Encounter",
    19 => "Pollution",
    20 => "The Picnic",
    21 => "The Magic Contest",
    22 => "The Voting System",
    23 => "Take-a-Chance",
    24 => "The Unity Tree",
    25 => "There are no Shortcuts",
    26 => "Books Are Great",
    27 => "Coriander's Invention",
    28 => "The Festivities",
    29 => "Destroyer of Worlds",
    30 => "Need a Hug",
    31 => "The Fight",
    32 => "The Battlefield",
    33 => "Spell of War",
    34 => "The Knighting of Shichimi",
    35 => "The Reflection",
    36 => "The Surprise Attack",
    37 => "The Tears of the Phoenix",
    38 => "The Healer",
    39 => "The Tavern"
];

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    echo "<h2>KomikByte - Pepper & Carrot Import</h2>";

    /*
     * Verify comic
     */
    $checkComic = $pdo->prepare(
        "SELECT id, title
         FROM comics
         WHERE id = ?"
    );

    $checkComic->execute([$comicId]);

    $comic = $checkComic->fetch();

    if (!$comic) {
        die(
            "<p style='color:red'>
            Comic ID 18 was not found.
            </p>"
        );
    }

    echo "<p>Comic found: <strong>"
        . htmlspecialchars($comic["title"])
        . "</strong></p>";

    /*
     * Verify downloaded folder
     */
    if (!is_dir($baseFolder)) {
        die(
            "<p style='color:red'>
            Pepper & Carrot folder was not found:<br>"
            . htmlspecialchars($baseFolder)
            . "</p>"
        );
    }

    /*
     * Prepare chapter queries
     */
    $findChapter = $pdo->prepare(
        "SELECT id
         FROM chapters
         WHERE comic_id = ?
         AND chapter_number = ?"
    );

    $insertChapter = $pdo->prepare(
        "INSERT INTO chapters
        (
            comic_id,
            chapter_number,
            title,
            content,
            price
        )
        VALUES (?, ?, ?, ?, ?)"
    );

    /*
     * IMPORTANT:
     * Your actual table uses asset_path,
     * not image_path.
     */
    $findPage = $pdo->prepare(
        "SELECT id
         FROM chapter_pages
         WHERE chapter_id = ?
         AND page_number = ?"
    );

    $insertPage = $pdo->prepare(
        "INSERT INTO chapter_pages
        (
            chapter_id,
            page_number,
            asset_path,
            scene_title,
            narration,
            accent_key
        )
        VALUES (?, ?, ?, ?, ?, ?)"
    );

    /*
     * Process all 39 episodes
     */
    foreach ($chapters as $chapterNumber => $chapterTitle) {

        $folderName =
            "ch"
            . str_pad(
                $chapterNumber,
                2,
                "0",
                STR_PAD_LEFT
            );

        $chapterFolder =
            $baseFolder . "/" . $folderName;

        echo "<hr>";

        echo "<h3>
            Chapter "
            . $chapterNumber
            . ": "
            . htmlspecialchars($chapterTitle)
            . "
        </h3>";

        /*
         * Check physical folder
         */
        if (!is_dir($chapterFolder)) {

            echo "<p style='color:red'>
                Folder not found:
                "
                . htmlspecialchars($folderName)
                . "
            </p>";

            continue;
        }

        /*
         * Find existing chapter
         */
        $findChapter->execute([
            $comicId,
            $chapterNumber
        ]);

        $chapter = $findChapter->fetch();

        /*
         * Create chapter only if it does not exist
         */
        if (!$chapter) {

            /*
             * Chapter 1 is FREE.
             * Chapters 2-39 cost ₱10.
             */
            $price =
                ($chapterNumber == 1)
                ? 0.00
                : 10.00;

            $content =
                "Pepper & Carrot - Episode "
                . $chapterNumber
                . ": "
                . $chapterTitle
                . ". "
                . "Artwork by David Revoy. "
                . "Licensed under CC BY 4.0.";

            $insertChapter->execute([
                $comicId,
                $chapterNumber,
                $chapterTitle,
                $content,
                $price
            ]);

            $chapterId =
                $pdo->lastInsertId();

            echo "<p style='color:green'>
                Chapter created.
                ID: "
                . $chapterId
                . "
            </p>";

        } else {

            /*
             * Reuse existing chapter.
             *
             * This is important because Chapter 1
             * was already created during the first run.
             */
            $chapterId =
                $chapter["id"];

            echo "<p style='color:blue'>
                Chapter already exists.
                Reusing ID: "
                . $chapterId
                . "
            </p>";
        }

        /*
         * Find downloaded pages
         */
        $files = glob(
            $chapterFolder . "/page-*.jpg"
        );

        /*
         * Sort naturally
         */
        natsort($files);

        $pageCount = 0;

        foreach ($files as $filePath) {

            $fileName =
                basename($filePath);

            /*
             * Extract page number
             *
             * page-01.jpg
             * page-02.jpg
             * page-10.jpg
             */
            if (
                !preg_match(
                    '/page-(\d+)\.jpg$/i',
                    $fileName,
                    $matches
                )
            ) {
                continue;
            }

            $pageNumber =
                (int)$matches[1];

            /*
             * Web path stored in database
             */
            $assetPath =
                $baseWebPath
                . "/"
                . $folderName
                . "/"
                . $fileName;

            /*
             * Check existing page
             */
            $findPage->execute([
                $chapterId,
                $pageNumber
            ]);

            $existingPage =
                $findPage->fetch();

            if (!$existingPage) {

                /*
                 * These fields are optional metadata.
                 * The actual image is asset_path.
                 */
                $sceneTitle =
                    "Pepper & Carrot - Episode "
                    . $chapterNumber
                    . " - Page "
                    . $pageNumber;

                $narration =
                    "Pepper & Carrot Episode "
                    . $chapterNumber
                    . ", page "
                    . $pageNumber
                    . ".";

                $accentKey = "violet";

                $insertPage->execute([
                    $chapterId,
                    $pageNumber,
                    $assetPath,
                    $sceneTitle,
                    $narration,
                    $accentKey
                ]);

                echo "<div style='color:green'>
                    Page "
                    . $pageNumber
                    . " added
                </div>";

            } else {

                echo "<div style='color:gray'>
                    Page "
                    . $pageNumber
                    . " already exists
                </div>";
            }

            $pageCount++;
        }

        echo "<p>
            <strong>"
            . $pageCount
            . "</strong>
            JPG page(s) detected.
        </p>";
    }

    echo "<hr>";

    echo "<h2 style='color:green'>
        Import Complete!
    </h2>";

    echo "<p>
        Pepper & Carrot Episodes 1-39
        have been processed.
    </p>";

    echo "<p>
        Check the
        <strong>chapters</strong>
        and
        <strong>chapter_pages</strong>
        tables in phpMyAdmin.
    </p>";

} catch (PDOException $e) {

    echo "<h2 style='color:red'>
        Database Error
    </h2>";

    echo "<pre>"
        . htmlspecialchars(
            $e->getMessage()
        )
        . "</pre>";
}
?>